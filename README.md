# Vehicle Registration System

A comprehensive vehicle registration and management system for educational institutions.

## 🚀 Features

- Multi-user support (Students, Staff, Guests)
- Vehicle registration and management
- Camera-based plate scanning with OCR
- Manual plate search functionality
- Admin dashboard with reports
- Password reset via email
- Responsive mobile design
- Real-time notifications

## 📁 Project Structure

```
frontend/
├── config/                     # Configuration files
│   ├── app.php                # Application settings
│   └── database.php           # Database configuration
├── includes/                   # Core application files
│   ├── init.php               # Application initialization
│   └── functions/             # Function libraries
│       ├── auth.php           # Authentication functions
│       ├── utilities.php      # Utility functions
│       └── vehicle.php        # Vehicle management
├── assets/                     # Frontend assets
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   └── images/                # Image assets
├── views/                      # View templates
├── database/                   # Database scripts
└── uploads/                    # File uploads
```

## 🛠️ Installation

1. **Setup Database**
   - Create MySQL database: `vehicleregistrationsystem`
   - Import database scripts from `database/` folder

2. **Configure Application**
   - Update `config/database.php` with your database credentials
   - Update `config/app.php` with your application settings

3. **Install Dependencies**
   ```bash
   composer install
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   ```

5. **Access Application**
   - Default admin: `admin` / `admin123`

## 🔧 Usage

### For Users
- Register and login with email/password
- Add and manage vehicles
- Search vehicles manually or with camera
- Manage authorized drivers

### For Administrators
- Access admin dashboard
- View all users and vehicles
- Generate reports
- Manage system settings

## 🔒 Security Features

- Input sanitization and validation
- Password hashing with bcrypt
- CSRF protection
- SQL injection prevention
- XSS protection
- Secure session management

## 📱 Mobile Support

- Responsive design
- Touch-friendly interface
- Camera integration for plate scanning
- Mobile-optimized tables

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection**
   - Check credentials in `config/database.php`
   - Ensure MySQL is running

2. **Camera Not Working**
   - Use HTTPS for camera access
   - Check browser permissions

3. **Email Issues**
   - Verify SMTP settings in `config/app.php`
   - Check firewall settings

### Debug Mode

Enable in `config/app.php`:
```php
define('APP_ENV', 'development');
define('DISPLAY_ERRORS', true);
```

## 📄 License

MIT License

## 🤝 Support

- Email: support@au.ac.zw
- Documentation: See inline code comments
- Issues: Use GitHub issues

---

**Built for educational institutions with modern PHP practices** 