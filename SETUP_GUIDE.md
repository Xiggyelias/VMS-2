# Vehicle Registration System - Setup Guide

## 🚀 Quick Start

### 1. Prerequisites
- XAMPP (Apache + MySQL + PHP 8.x)
- Google Cloud Console account
- Africa University domain access

### 2. Database Setup
```sql
-- Run this in MySQL to create the registration_drafts table
-- File: frontend/database/create_registration_drafts_table.sql
```

### 3. Configuration
- Update `frontend/config/app.php` with your Google Client ID
- Ensure `ALLOWED_GOOGLE_DOMAIN` is set to `'africau.edu'`

### 4. Test the System
Visit: `http://localhost/system/frontend/test_google_config.php`

## 🔧 Detailed Setup

### Google OAuth Configuration

1. **Create Google Cloud Project**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select existing one

2. **Enable Google+ API**
   - Go to "APIs & Services" > "Library"
   - Search for "Google+ API" and enable it

3. **Create OAuth 2.0 Credentials**
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client IDs"
   - Application type: "Web application"
   - Authorized JavaScript origins: `http://localhost` (for development)
   - Authorized redirect URIs: `http://localhost/system/frontend/google_auth.php`

4. **Update Configuration**
   - Copy the Client ID to `frontend/config/app.php`
   - Replace `YOUR_GOOGLE_CLIENT_ID` with your actual Client ID

### Database Configuration

1. **Create Database**
   ```sql
   CREATE DATABASE vehicleregistrationsystem;
   ```

2. **Run SQL Scripts**
   ```bash
   # Navigate to database folder
   cd frontend/database
   
   # Run the registration drafts table script
   mysql -u root -p vehicleregistrationsystem < create_registration_drafts_table.sql
   ```

3. **Verify Tables**
   - `applicants` - User information
   - `vehicles` - Vehicle registrations
   - `authorized_driver` - Authorized drivers
   - `registration_drafts` - Form drafts (new)

### File Permissions

Ensure these directories are writable:
- `frontend/logs/`
- `frontend/uploads/` (if using file uploads)

## 🧪 Testing

### 1. Configuration Test
Visit: `http://localhost/system/frontend/test_google_config.php`

### 2. Google Sign-In Test
1. Visit: `http://localhost/system/frontend/login.php`
2. Click "Sign in with Google"
3. Try with @africau.edu email (should work)
4. Try with @gmail.com email (should be blocked)

### 3. Registration Flow Test
1. Sign in with AU email
2. Fill out registration form partially
3. Close browser/reload page
4. Sign in again - form should resume
5. Complete registration
6. Verify redirect to dashboard

### 4. Admin Test
1. Visit: `http://localhost/system/frontend/admin-login.php`
2. Use admin credentials
3. Verify admin dashboard access

## 🔒 Security Considerations

### Production Deployment

1. **HTTPS Required**
   - Google Sign-In requires HTTPS in production
   - Update `BASE_URL` to use HTTPS
   - Set `SESSION_SECURE` to `true`

2. **Environment Variables**
   - Move sensitive config to environment variables
   - Update `APP_ENV` to `'production'`

3. **Google OAuth Settings**
   - Update authorized origins to production domain
   - Add production redirect URIs

### Security Headers
The system includes:
- CSRF protection
- Session security
- Rate limiting
- Domain validation

## 📋 Troubleshooting

### Common Issues

1. **"Google Sign-In not working"**
   - Check Google Client ID in config
   - Verify authorized origins in Google Console
   - Ensure HTTPS in production

2. **"Database connection failed"**
   - Check MySQL is running
   - Verify database credentials
   - Ensure database exists

3. **"Domain restriction not working"**
   - Check `ALLOWED_GOOGLE_DOMAIN` setting
   - Verify email domain validation logic

4. **"Draft autosave not working"**
   - Check `registration_drafts` table exists
   - Verify user session is active
   - Check browser console for JavaScript errors

### Debug Mode
Set `APP_ENV` to `'development'` in `config/app.php` for detailed error messages.

## 📞 Support

For issues:
1. Check the error logs in `frontend/logs/`
2. Verify configuration in `frontend/config/app.php`
3. Test with the configuration test page
4. Review the TODO.md for implementation status

## 🎯 Success Criteria

The system is working correctly when:
- ✅ Google Sign-In accepts @africau.edu emails only
- ✅ Registration form auto-saves progress
- ✅ Users can resume registration after logout
- ✅ Complete registration redirects to dashboard
- ✅ Admin functionality remains intact
- ✅ All security features are active





















