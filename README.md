
# LASUFit - Gym Management System

**LASUFit** is a modern, responsive Gym Management System designed for local fitness centers. It provides a robust backend for administrative operations while offering an engaging, gamified portal for gym members to track their progress and stay connected with the community.

## 🚀 Features

### Admin Portal
* **Member Management**: Onboard new members, generate unique membership codes, and manage profiles.
* **Subscription Tracking**: Create and manage custom membership plans and track outstanding debts.
* **Attendance Tracking**: A streamlined check-in system for front-desk operations.
* **Financial Oversight**: Log payments (Cash/Transfer) and monitor monthly revenue.
* **Class Scheduling**: Manage gym session capacities and schedules.

### Member Portal
* **Daily Workout Logs**: Track exercises, sets, reps, and volume.
* **Progress Tracking**: Monitor body weight and personal bests over time.
* **Community Leaderboards**: Compete with other members on "Heavy Lifters" and "Iron Addicts" boards.
* **Class Booking**: Reserve spots in upcoming gym sessions.
* **Theme Switching**: Seamlessly toggle between Light and Dark modes.

---

## 🛠️ Technical Stack

* **Backend**: PHP 8.x (utilizing OOP and Singleton Design Pattern)
* **Database**: MariaDB / MySQL
* **Frontend**: Bootstrap 5, Vanilla JavaScript, CSS3
* **Security**: CSRF Protection, Role-Based Access Control (RBAC), and password hashing.
* **Environment**: Developed in WSL (Ubuntu) / XAMPP.

---

## 📦 Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/lasufit-manager.git
cd lasufit-manager
```

### 2. Install Dependencies
Ensure you have **Composer** installed.
```bash
composer install
```

### 3. Database Configuration
1. Import the provided `backup.sql` (or your latest SQL dump) into your MySQL/MariaDB server.
2. Create a `config.local.php` file in the root directory:
```php
<?php
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'gym_management',
    'db_user' => 'your_username',
    'db_pass' => 'your_password'
];
```

### 4. Run the Application
If using PHP's built-in server:
```bash
php -S localhost:8000 -t public/
```
Or move the folder to your XAMPP `htdocs` directory and access via `http://localhost/lasufit-manager/public/`.

---

## 🔒 Security
* **Environment Isolation**: Sensitive database credentials are kept out of version control via `.gitignore`.
* **CSRF Protection**: All POST requests are validated with unique session tokens.
* **Session Management**: Secure user sessions with role-specific redirection.

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).

---

## 👤 Author
**Samuel Alawode**
*Backend Engineer*