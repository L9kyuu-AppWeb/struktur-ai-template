# Admin Panel - Start Project Dinamis

## 📁 Struktur Folder (Simplified)

```
admin-panel/
│
├── index.php                      # Entry point aplikasi
├── config.php                     # Konfigurasi database & konstanta
│
├── includes/
│   ├── header.php                 # Header HTML & Navigation
│   ├── footer.php                 # Footer HTML
│   ├── sidebar.php                # Sidebar navigation
│   ├── functions.php              # Fungsi-fungsi helper
│   ├── db_connect.php             # Koneksi database
│   └── auth_check.php             # Cek authentication & authorization
│
├── pages/
│   ├── dashboard/
│   │   └── index.php              # Dashboard
│   │
│   ├── profile/
│   │   └── index.php              # Profile
│   │
│   ├── users/
│   │   ├── index.php              # List users
│   │   ├── create.php             # Create user
│   │   ├── edit.php               # Edit user
│   │   └── delete.php             # Delete user
│   │
│   ├── settings/
│   │   └── index.php              # Settings
│   │
│   ├── auth/
│   │   ├── login.php              # Login
│   │   └── logout.php             # Logout
│   │
│   └── errors/
│       └── 403.php                # Access Denied
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   ├── images/
│   │   └── logo.png
│   └── uploads/
│       └── avatars/
│
└── database/
    └── schema.sql