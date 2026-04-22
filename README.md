# 🏥 IRB Digital System - Research Approval Management

A secure, role-based web application built with object-oriented PHP and MySQL to manage the research approval lifecycle for the Institutional Review Board (IRB) at the Faculty of Medicine.

## docs https://docs.google.com/document/d/1wLQDK7zc70B3Z7_dANCbzopchhjF9jLEzQO6J_lIqTI/edit?tab=t.0#heading=h.506q4t6lbijn

## 🚀 Prerequisites
Before you begin, ensure you have the following installed on your machine:
* A local web server: **XAMPP**, **MAMP**, or **WAMP** (Running Apache & MySQL)
* **Git** installed on your terminal

---

## 🛠️ Installation & Setup Guide

Follow these steps carefully to get the project running on your local machine.

### Step 1: Clone the Repository
Open your terminal, navigate to your local server's public directory (usually `htdocs` for XAMPP/MAMP, or `www` for WAMP), and clone the repository:

```bash
cd /path/to/your/htdocs
git clone https://github.com/amiresaye6/irb-digital-system
cd irb-digital-system
```

### Step 2: Configure Environment Variables (Crucial!)
To protect our database credentials, we do not upload them to GitHub. You must create your own local configuration file.
1. Navigate to the `includes/` folder.
2. Duplicate the `env.example.php` file and rename the copy to **`env.php`**.
3. Open `env.php` and update the database password and port to match your local MySQL setup (e.g., MAMP usually uses port 3306 or 8889, XAMPP uses 3306).

### Step 3: Database Setup & Seeding
We have prepared a complete database structure with realistic dummy data for testing.
1. Open your browser and go to **PhpMyAdmin** (usually `http://localhost/phpmyadmin`).
2. Click on the **Import** tab at the top.
3. Choose the file located at `setup/db.sql` in your cloned project folder.
4. Click **Import / Go** at the bottom of the page.

### Step 4: Run the Application
The setup is complete! Open your browser and navigate to the project folder:
👉 **http://localhost/irb-digital-system/**

*(Note: Depending on your router, this will automatically take you to the login page).*

---

## 🔑 Test Accounts (Seed Data)
The database has been pre-seeded with users for every role. You can log in using any of the following accounts to test different features.

**Universal Password for all test accounts:** `123456`

| Role | Name | Email |
| :--- | :--- | :--- |
| **Student** | Dr. Omar Al-Farouq | `omar@med.edu` |
| **Admin** | Mr. Mahmoud (Admin) | `admin@irb.edu` |
| **Sample Officer**| Eng. Hossam | `sample1@irb.edu` |
| **Reviewer** | Prof. Khaled (Reviewer) | `khaled.rev@irb.edu` |
| **Manager** | Prof. Tarek (Director) | `manager@irb.edu` |

---

## 📂 Project Architecture (Feature-Based)
To prevent Git merge conflicts and keep our code organized during the sprint, we are using a Feature-Based Architecture. Please write your code in the appropriate folder:

* `classes/`: Core OOP logic (Database, Auth, Models).
* `features/`: The actual pages, divided by user role (Admin, Student, Reviewer, Auth).
* `includes/`: Shared components (`header.php`, `footer.php`, `sidebar.php`, `env.php`).
* `assets/`: Frontend styling (`global.css`, images, JS).
* `uploads/`: Secure directory for uploaded research protocols and ID cards.

**⚠️ Reminder:** Never push `env.php` or actual uploaded user documents to GitHub. They are ignored in the `.gitignore` file by default. Happy coding!