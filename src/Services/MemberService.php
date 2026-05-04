<?php

namespace App\Services;

use App\Core\Database;
use Exception;
use PDO;

/**
 * Class MemberService
 * Handles business logic and database interactions for gym members.
 */
class MemberService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get paginated and searchable members.
     * 
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param string $search Search query (name, email, phone)
     * @return array Contains 'data', 'total', and 'pages'
     */
    public function getMembers(int $page = 1, int $limit = 10, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "deleted_at IS NULL";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR member_code LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }

        // Get total count for pagination
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM members WHERE $whereClause");
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        // Get actual data
        $query = "SELECT * FROM members WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        
        // PDO needs integers for LIMIT/OFFSET bound explicitly when using emulated prepares = false
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Fetch a single member by ID.
     */
    public function getMemberById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM members WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $member = $stmt->fetch();
        return $member ?: null;
    }

    /**
     * Create a new member.
     */
    public function createMember(array $data, ?array $photoFile): bool
    {
        $photoPath = $this->handlePhotoUpload($photoFile);
        $memberCode = $this->generateMemberCode();

        $stmt = $this->db->prepare("
            INSERT INTO members (member_code, full_name, email, phone, emergency_contact, join_date, photo_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $memberCode,
            trim($data['full_name']),
            trim($data['email']) ?: null,
            trim($data['phone']) ?: null,
            trim($data['emergency_contact']) ?: null,
            $data['join_date'],
            $photoPath
        ]);
    }

    /**
     * Update an existing member.
     */
    public function updateMember(int $id, array $data, ?array $photoFile): bool
    {
        $currentMember = $this->getMemberById($id);
        if (!$currentMember) {
            throw new Exception("Member not found.");
        }

        $photoPath = $currentMember['photo_path'];
        if ($photoFile && $photoFile['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handlePhotoUpload($photoFile);
            // Optionally: Delete old photo from disk here
        }

        $stmt = $this->db->prepare("
            UPDATE members 
            SET full_name = ?, email = ?, phone = ?, emergency_contact = ?, photo_path = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            trim($data['full_name']),
            trim($data['email']) ?: null,
            trim($data['phone']) ?: null,
            trim($data['emergency_contact']) ?: null,
            $photoPath,
            $id
        ]);
    }

    /**
     * Soft delete a member.
     */
    public function deleteMember(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE members SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Validates and moves uploaded photo. Returns the relative file path.
     */
    private function handlePhotoUpload(?array $file): ?string
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Strict MIME type checking
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and WEBP are allowed.");
        }

        // Max size: 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception("File size exceeds 2MB limit.");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('mbr_', true) . '.' . $extension;
        
        $uploadDir = __DIR__ . '/../../public/uploads/photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to save uploaded file.");
        }

        return 'uploads/photos/' . $filename;
    }

    /**
     * Generates a unique member code (e.g., GYM-2410-A1B2)
     */
    private function generateMemberCode(): string
    {
        return 'GYM-' . date('ym') . '-' . strtoupper(substr(uniqid(), -4));
    }

    /**
     * Handle public self-registration.
     */
    public function selfRegister(array $data, string $password): bool
    {
        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM members WHERE email = ?");
        $stmt->execute([trim($data['email'])]);
        if ($stmt->fetch()) {
            throw new Exception("An account with this email already exists.");
        }

        $memberCode = $this->generateMemberCode();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO members (member_code, full_name, email, phone, password, join_date)
            VALUES (?, ?, ?, ?, ?, CURRENT_DATE)
        ");

        return $stmt->execute([
            $memberCode,
            trim($data['full_name']),
            trim($data['email']),
            trim($data['phone']) ?: null,
            $hashedPassword
        ]);
    }

    /**
     * Handle a member updating their own profile.
     */
    public function updateOwnProfile(int $memberId, array $data, ?array $photoFile): bool
    {
        $currentMember = $this->getMemberById($memberId);
        if (!$currentMember) {
            throw new Exception("Member not found.");
        }

        $photoPath = $currentMember['photo_path'];
        if ($photoFile && $photoFile['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handlePhotoUpload($photoFile);
        }

        $stmt = $this->db->prepare("
            UPDATE members 
            SET phone = ?, emergency_contact = ?, photo_path = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            trim($data['phone']) ?: null,
            trim($data['emergency_contact']) ?: null,
            $photoPath,
            $memberId
        ]);
    }
}