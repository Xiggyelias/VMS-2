# Google Sign-In Troubleshooting Guide

## 🚨 Google Sign-In Button Not Showing

If the Google Sign-In button is not appearing on the login page, follow these steps:

### 1. Check Configuration Test
Visit: `http://localhost/system/frontend/test_google_config.php`
- Verify Google Client ID is correct
- Check database connection
- Ensure all required tables exist

### 2. Test Google Sign-In Directly
Visit: `http://localhost/system/frontend/test_google_signin.php`
- This page tests Google Sign-In in isolation
- Check browser console for errors
- Verify the button loads properly

### 3. Common Issues & Solutions

#### Issue: Button Not Loading
**Symptoms**: No Google Sign-In button appears
**Solutions**:
- Check internet connection (Google API needs internet)
- Verify Google Client ID is correct
- Check browser console for JavaScript errors
- Ensure HTTPS in production (Google requires it)

#### Issue: "Google Sign-In failed to load"
**Symptoms**: Fallback button appears
**Solutions**:
- Check if Google API is blocked by firewall
- Verify authorized origins in Google Console
- Try refreshing the page
- Check browser console for specific errors

#### Issue: "Invalid Client ID"
**Symptoms**: Error message about invalid client
**Solutions**:
- Verify Client ID in `frontend/config/app.php`
- Check Google Cloud Console settings
- Ensure authorized origins include your domain

### 4. Browser Console Debugging

Open browser console (F12) and look for:
```
✅ Expected: "Google Sign-In loaded successfully!"
❌ Error: "Google Sign-In not loaded, showing fallback button"
```

### 5. Google Cloud Console Checklist

1. **OAuth 2.0 Client ID** is created
2. **Authorized JavaScript origins** includes:
   - `http://localhost` (for development)
   - `https://yourdomain.com` (for production)
3. **Google+ API** is enabled
4. **Application type** is "Web application"

### 6. Development vs Production

**Development (localhost)**:
- HTTP is allowed
- Use `http://localhost` in authorized origins
- Client ID should work immediately

**Production**:
- HTTPS required
- Update authorized origins to production domain
- Set `SESSION_SECURE` to `true` in config

### 7. Quick Fixes

1. **Clear browser cache** and refresh
2. **Try incognito/private mode**
3. **Check if ad blockers** are interfering
4. **Verify Google API** is accessible: `https://accounts.google.com/gsi/client`

### 8. Test URLs

- **Configuration Test**: `http://localhost/system/frontend/test_google_config.php`
- **Google Sign-In Test**: `http://localhost/system/frontend/test_google_signin.php`
- **Main Login**: `http://localhost/system/frontend/login.php`

### 9. Emergency Fallback

If Google Sign-In still doesn't work:
1. The system includes a fallback button
2. Check the browser console for detailed error messages
3. Verify all configuration settings
4. Test with a different browser

### 10. Support Information

When reporting issues, include:
- Browser and version
- Console error messages
- Configuration test results
- Whether you're on localhost or production
- Screenshots of the issue

## 🎯 Success Indicators

The Google Sign-In is working correctly when:
- ✅ Button appears within 2-3 seconds
- ✅ Clicking button opens Google popup
- ✅ @africau.edu emails are accepted
- ✅ Non-AU emails are rejected
- ✅ Redirects to registration form after successful sign-in





















