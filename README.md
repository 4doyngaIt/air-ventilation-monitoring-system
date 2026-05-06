git# 💨 AirVent Monitoring System
### Farm Air Ventilation Monitoring & Automated Control
**Live URL:** https://ics-dev.io/hangin

---

## 📖 What Is This System?

The **AirVent Monitoring System** is a web-based farm ventilation management platform designed to help farmers monitor and control air ventilation across greenhouse bays or farm enclosures. The system reads data from physical sensors and can automatically open or close ventilation based on environmental conditions.

### How the Automation Works
- 🌡️ **High Temperature Detected** → Vents automatically **open** to cool the environment
- 🌧️ **Rainfall Detected** → Vents automatically **close** to protect crops from rain
- 🤖 **Manual Override** → Users can force vents open or closed at any time, then return to automatic mode

---



### How Files Are Served
This FTP account maps directly to `https://ics-dev.io/hangin/` — files uploaded to the FTP root `/` are served at that URL. **Do NOT put files inside `public_html/`** — upload directly to the root `/`.

---

## 🗄️ Database

| Field | Value |
|-------|-------|
| phpMyAdmin URL | `https://auth-db1981.hstgr.io` |
| Database Name | `u442411629_hangin` |
| DB Username | `u442411629_dev_hangin` |
| DB Password | `9O\4qo]t'{9+` |
| DB Host (in PHP) | `localhost` |

### Running the Schema
1. Go to `https://auth-db1981.hstgr.io`
2. Log in with the DB credentials above
3. Select `u442411629_hangin` from the left panel
4. Click the **SQL** tab
5. Open `db/schema.sql`, copy everything **except** the first line (`USE u442411629_hangin;`)
6. Paste into the SQL tab and click **Go**

This creates all 5 tables and inserts sample data automatically.

---

## 👤 Role Permissions

| Feature | User | Manager | Admin |
|---------|------|---------|-------|
| Live sensor dashboard | ✅ | ✅ | ✅ |
| Weather monitor | ✅ | — | — |
| Ventilation manual control | ✅ | — | — |
| Sensor map (Leaflet) | — | ✅ | — |
| Monitor all sensors | — | ✅ | ✅ |
| View all users | — | ✅ | ✅ |
| Add / Edit / Delete users | — | Partial* | ✅ |
| View activity logs | — | — | ✅ |
| View per-user sensor data | — | — | ✅ |

> *Managers can add and edit User-role accounts only. They cannot touch Admin accounts.

---

## 📁 File Structure

```
/ (FTP root — maps to https://ics-dev.io/hangin/)
│
├── index.php                    ← Root redirect (login or dashboard by role)
│
├── assets/
│   └── style.css                ← Full design system (Syne + Epilogue fonts,
│                                   clay/forest color palette, all components)
│
├── auth/
│   ├── login.php                ← Login page with farm-themed UI
│   └── logout.php               ← Clears session → redirects to login
│
├── config/
│   └── config.php               ← DB connection, helper functions, auth guard,
│                                   BASE_URL, APP constants
│
├── db/
│   └── schema.sql               ← Creates all tables + inserts seed data
│
├── images/
│   ├── bg.png                   ← (Optional) Login page background image
│   └── logo.png                 ← (Optional) Login page logo
│
└── app/
    ├── sensor_data.php          ← JSON API endpoint (GET ?action=sensors)
    ├── sensor_control.php       ← JSON API endpoint (POST — vent commands)
    │
    ├── admin/
    │   ├── header.php           ← Admin sidebar + topbar layout shell (included by all admin pages)
    │   ├── footer.php           ← Closing HTML tags
    │   ├── dashboard.php        ← Stats, pie chart, activity bar chart, quick actions
    │   ├── user-management.php  ← Full CRUD: add, edit, delete users with modals
    │   ├── view-user.php        ← View sensor readings assigned to a specific user
    │   └── activity-logs.php    ← Full activity log table (last 100 entries)
    │
    ├── manager/
    │   └── dashboard.php        ← Single-page app (SPA) with 4 sections:
    │                               Dashboard stats, Leaflet sensor map,
    │                               Sensor monitor (grid/table toggle), User list
    │
    └── user/
        ├── header.php           ← User sidebar + topbar layout shell
        ├── footer.php           ← Closing HTML tags
        ├── dashboard.php        ← Live sensor cards with auto-refresh (5s),
        │                           stats, search/filter
        ├── weather.php          ← Weather conditions per sensor (5s refresh)
        └── ventilation.php      ← Manual vent control: Open / Close / Set Auto
```

---

## 🗃️ Database Tables

### `users`
Stores all system accounts.

| Column | Type | Description |
|--------|------|-------------|
| user_id | INT PK | Auto-increment ID |
| username | VARCHAR(50) | Unique display name |
| email | VARCHAR(100) | Login email (unique) |
| password | VARCHAR(255) | bcrypt hash (or plain for seed data) |
| role | ENUM | `user`, `manager`, or `admin` |
| created_at | DATETIME | Account creation timestamp |

### `sensors`
Physical sensor units registered in the system.

| Column | Type | Description |
|--------|------|-------------|
| sensor_id | INT PK | Auto-increment ID |
| location | VARCHAR(100) | Human-readable location name e.g. "Greenhouse Bay A" |
| latitude | DECIMAL(9,6) | GPS latitude for map display |
| longitude | DECIMAL(9,6) | GPS longitude for map display |
| status | ENUM | `Active` or `Inactive` |
| created_at | DATETIME | Registration timestamp |

### `sensor_readings`
Every data point recorded from a sensor, including vent state.

| Column | Type | Description |
|--------|------|-------------|
| reading_id | INT PK | Auto-increment ID |
| sensor_id | INT FK | References `sensors.sensor_id` |
| temperature | FLOAT | °C reading |
| humidity | FLOAT | % relative humidity |
| rainfall | FLOAT | mm of rainfall (0 = none) |
| wind_speed | FLOAT | m/s |
| sunlight_intensity | FLOAT | Lux or raw sensor value |
| weather_condition | ENUM | `sunny`, `cloudy`, `rainy`, `storm`, `unknown` |
| vent_state | ENUM | `on` (open) or `off` (closed) |
| mode | ENUM | `automatic` or `manual` |
| created_at | DATETIME | Reading timestamp |

### `ventilation_logs`
Tracks every manual vent control action.

| Column | Type | Description |
|--------|------|-------------|
| log_id | INT PK | Auto-increment ID |
| user_id | INT FK | Who triggered it |
| sensor_id | INT FK | Which sensor was controlled |
| action | ENUM | `on` or `off` |
| mode | ENUM | `manual` or `automatic` |
| timestamp | DATETIME | When the action occurred |

### `activity_logs`
General system audit trail.

| Column | Type | Description |
|--------|------|-------------|
| log_id | INT PK | Auto-increment ID |
| user_id | INT FK | Who performed the action |
| action_type | VARCHAR(50) | e.g. `login`, `create_user`, `edit_user`, `sensor` |
| description | TEXT | Human-readable description |
| created_at | DATETIME | Timestamp |

---

## 🔌 API Endpoints

### GET `/hangin/app/sensor_data.php?action=sensors`
Returns all sensors with their latest reading as JSON.

**Example response:**
```json
[
  {
    "sensor_id": "1",
    "location": "Greenhouse Bay A",
    "status": "Active",
    "temperature": 38.5,
    "humidity": 42,
    "rainfall": 0,
    "wind_speed": 2.1,
    "sunlight_intensity": 950,
    "weather_condition": "sunny",
    "vent_state": "on",
    "mode": "automatic",
    "created_at": "2026-04-03 14:32:00"
  }
]
```

### POST `/hangin/app/sensor_control.php`
Sends a vent command to a sensor. Requires an active PHP session.

**Request body (JSON):**
```json
{
  "sensor_id": 1,
  "action": "on",
  "mode": "manual"
}
```

**Response:**
```json
{ "status": "ok", "sensor_id": 1, "action": "on", "mode": "manual" }
```

| Field | Values |
|-------|--------|
| action | `on` (open vent) or `off` (close vent) |
| mode | `manual` or `automatic` |

---

## ⚙️ Configuration (`config/config.php`)

Key constants and settings:

```php
define('BASE_URL', '/hangin');      // URL prefix — change if deployment path changes
define('DB_HOST',  'localhost');    // Always localhost on Hostinger shared hosting
define('DB_NAME',  'u442411629_hangin');
define('APP_NAME', 'AirVent Monitor');
define('APP_VER',  '2.0');
date_default_timezone_set('Asia/Manila');
```

### Helper Functions

| Function | Description |
|----------|-------------|
| `db_q($sql, $types, $params)` | Run SELECT, returns array of rows |
| `db_one($sql, $types, $params)` | Run SELECT, returns single row or null |
| `db_run($sql, $types, $params)` | Run INSERT/UPDATE/DELETE, returns insert ID or affected rows |
| `log_act($uid, $type, $desc)` | Insert a row into `activity_logs` |
| `auth_guard(...$roles)` | Redirect to login if not logged in or wrong role |
| `e($string)` | HTML-safe output — always use for user data |

---

## 🎨 Design System

**Aesthetic:** Industrial-Organic — warm farm equipment meets clean dashboard.

| Token | Value | Usage |
|-------|-------|-------|
| `--clay` | `#c4622d` | Primary buttons, accents, active nav |
| `--forest-mid` | `#4a7c28` | Success states, active vents, open indicators |
| `--rain` | `#3b6ea5` | Rain detection indicators |
| `--sun` | `#d4920a` | Auto mode, warning states |
| `--earth` | `#3d2b1f` | Sidebar background, dark text |
| `--warm-white` | `#faf7f4` | Page background |
| `--font-head` | Syne | Headings, stat values, brand name |
| `--font` | Epilogue | Body text, labels, form inputs |
| `--font-mono` | JetBrains Mono | Sensor values, badges, code |

---

## 🚀 Deployment Steps (Fresh Setup)

1. **Set up the database** — Run `db/schema.sql` in phpMyAdmin (see Database section above)
2. **Connect via FTP** — Use FileZilla with the FTP credentials above
3. **Upload files** — Extract the zip, open the `hangin2/` folder, select all contents, drag to FTP root `/`
4. **Verify** — Visit `https://ics-dev.io/hangin/` — you should see the login page
5. **Log in** — Use `admin@gmail.com` / `admin123`
6. **Change passwords** — Admin → User Management → Edit each user

---

## 🐛 Troubleshooting

| Problem | Likely Cause | Fix |
|---------|-------------|-----|
| "Page does not exist" | Files in wrong FTP location | Upload to `/` root, NOT inside `public_html/` |
| "Database connection failed" | Wrong DB host | Make sure `DB_HOST` is `localhost` not the FTP hostname |
| Login redirects to `/auth/login.php` without `/hangin` | `BASE_URL` empty or wrong | Set `define('BASE_URL', '/hangin')` in config.php |
| Sensor data not loading | JS fetch failing | Check browser console — likely a path issue in `sensor_data.php` |
| Logout goes to wrong page | Old file still on server | Re-upload `auth/logout.php` — it should redirect to `/hangin/auth/login.php` |
| Map not showing (manager) | No lat/lng in sensors table | Add latitude and longitude values to sensors in phpMyAdmin |
| Vent control button spins forever | Session expired or sensor_control.php path wrong | Re-login, check network tab in browser DevTools |

---

## 📝 Notes

- **PHP 8.0+** required (uses match expressions and named arguments)
- **No `.htaccess` needed** — this hosting setup serves PHP directly without URL rewriting
- **Sensor data is inserted by hardware** — the `sensor_readings` table is designed to receive data from physical IoT sensors via the API. The ventilation control page lets users manually override while the hardware handles automatic readings.
- **Images are optional** — the login page works without `bg.png` and `logo.png`. Upload your own via FTP to `images/` if desired.
