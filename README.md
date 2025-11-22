# 📊 Visitor Statistics

> **A simple, lightweight visitor tracking system.**
>
> *Real-time analytics, detailed device intelligence, and beautiful visualizations.*

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

---

## 📖 Overview

**Visitor Statistics** is a **simple** analytics solution. It features a responsive dashboard built with **Chart.js** that visualizes traffic trends, device breakdowns, and detailed visitor logs in real-time.

## ✨ Key Features

- **🚀 Real-Time Tracking**: Instantly capture page views, unique visitors, and session duration.
- **📱 Deep Device Intelligence**:
  - **Hardware**: Detects CPU cores, Device Memory (RAM), and Connection Type (4G, WiFi, etc.).
  - **Software**: Identifies OS (Windows, macOS, iOS, Android), Browser, and specific Device Models.
- **🌍 Geolocation**: Automatically resolves IP addresses to Country, City, Region, and ISP using `ipinfo.io`.
- **🔒 Privacy-First**: Uses `localStorage` for visitor identification instead of invasive cookies.
- **⚡ Lightweight**: The client-side `tracker.js` is optimized for performance and zero dependencies.

## 🛠️ Tech Stack

- **Backend**: PHP (PDO for database abstraction)
- **Database**: MySQL (Auto-migrating schema)
- **Frontend**: HTML5, CSS3 (Variables), Vanilla JavaScript
- **Visualization**: Chart.js 3.9.1

## 🚀 Installation

### 1. Prerequisites
- A PHP-enabled web server (Apache/Nginx).
- MySQL database.
- (Optional) `ipinfo.io` token for geolocation.

### 2. Setup
1.  **Clone the repository**:
    ```bash
    git clone https://github.com/yourusername/visitor-statistics.git
    ```
2.  **Configure Database**:
    Edit `config.php` with your database credentials:
    ```php
    <?php
    $host = 'localhost';
    $dbname = 'your_db_name';
    $username = 'your_db_user';
    $password = 'your_db_password';
    
    // Don't forget to set your IPInfo token for geolocation!
    $ipinfo_token = 'your_ipinfo_token'; 
    ?>
    ```
    > **Note**: You need a free token from [ipinfo.io](https://ipinfo.io) to enable Geolocation features.
    ```
3.  **Configure Tracker Endpoint**:
    Open `tracker.js` and edit line 3 to point to your `collect.php` URL:
    ```javascript
    // CHANGE THIS to your actual domain/path
    var endpoint = 'https://your-domain.com/analytics/collect.php';
    ```

4.  **Upload Files**: Upload all files to your server directory (e.g., `/analytics/`).

### 3. Integration
Add the tracking script to the `<head>` or `<body>` of any page you want to track:

```html
<script src="https://your-domain.com/analytics/tracker.js"></script>
```

> **Note**: Ensure `tracker.js` points to the correct `collect.php` endpoint. Edit line 3 of `tracker.js` if necessary:
> ```javascript
> var endpoint = 'https://your-domain.com/analytics/collect.php';
> ```

## 🖥️ Dashboard

Access the dashboard by navigating to `index.php`.

![Dashboard Overview](http://yehongliang.com/github/visitor_tracking/1-1.jpg)
![Visitor Log](http://yehongliang.com/github/visitor_tracking/1-2.jpg)

- **Overview Cards**: Total PV, UV, and Average Time on Page.
- **Traffic Chart**: Smooth area chart showing hourly visits.
- **Visitor Log**: Detailed table of the last 50 visits, including:
    - IP & Location (City, Country, ISP)
    - Device Specs (OS, Model, CPU, RAM)
    - Browser & Referrer

## 📂 File Structure

```
├── index.php        # Main Dashboard UI
├── collect.php      # Data Collection Endpoint (API)
├── tracker.js       # Client-side Tracking Script
├── auth.php         # Simple Authentication Logic
├── login.php        # Login Page
├── config.php       # Database Configuration
└── debug_db.php     # Database Debugging Tool
```

## 📅 Roadmap

- [ ] **Pagination**: Support for paginated visitor logs.
- [ ] **Data Export**: Export statistics to CSV/Excel.
- [ ] **Deep Analysis**: More granular behavioral analytics.
- [ ] **Security**: Enhanced login protection and rate limiting.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
