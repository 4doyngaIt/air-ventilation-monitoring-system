# AirVent Monitoring System
## https://ics-dev.io/hangin2

### FTP Upload
Host: ftp.ics-dev.io | User: u442411629.hangin | Pass: 9O\4qo]t'{9+ | Port: 21
Upload the hangin2/ folder into public_html/

### phpMyAdmin Database Setup
URL: https://auth-db1981.hstgr.io
DB: u442411629_hangin | User: u442411629_dev_hangin | Pass: 9O\4qo]t'{9+
→ Select the DB → SQL tab → paste db/schema.sql (skip the USE line) → Go

### Default Logins
admin@gmail.com   / admin123  → Admin
jane@gmail.com    / pass123   → Manager
john@gmail.com    / 123456    → User

### File Structure
hangin2/
├── index.php
├── assets/style.css
├── auth/login.php, logout.php
├── config/config.php
├── db/schema.sql
├── images/bg.png, logo.png   ← upload your own images via FTP
└── app/
    ├── sensor_data.php        ← GET API: ?action=sensors
    ├── sensor_control.php     ← POST API: vent commands
    ├── admin/  (dashboard, user-management, view-user, activity-logs, header, footer)
    ├── manager/ (dashboard — SPA with map, sensors, users)
    └── user/   (dashboard, weather, ventilation, header, footer)
