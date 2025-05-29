# PayslipMax Deployment Guide - Hostinger Production

## 🚀 **Pre-Deployment Checklist**

✅ **Database credentials updated** in `api/db_config.php`  
✅ **Production .htaccess** configured for Hostinger  
✅ **Security headers** optimized for production  
✅ **File permissions** will be set correctly  

---

## 📋 **Step-by-Step Deployment Process**

### **Step 1: Prepare Files for Upload**

**Files to Upload via FileZilla:**
```
📁 Your Local Project Directory
├── 📄 index.html
├── 📄 error.html
├── 📄 .htaccess
├── 📄 README.md
├── 📁 css/
│   └── 📄 style.css
├── 📁 js/
│   ├── 📄 main.js
│   └── 📄 upload.js
├── 📁 api/
│   ├── 📄 db_config.php (✅ Updated with Hostinger credentials)
│   ├── 📄 upload_file.php
│   └── 📄 helpers.php
├── 📁 uploads/ (empty directory)
└── 📁 .well-known/
    ├── 📄 apple-app-site-association
    └── 📄 assetlinks.json
```

### **Step 2: FileZilla Upload Process**

1. **Connect to Hostinger:**
   - Host: Your domain or Hostinger FTP server
   - Username: Your Hostinger FTP username
   - Password: Your Hostinger FTP password
   - Port: 21 (or 22 for SFTP)

2. **Navigate to public_html:**
   - Go to `/public_html/` directory on the server
   - This is where your website files should be uploaded

3. **Upload All Files:**
   - Select all files and folders from your local project
   - Drag and drop to `/public_html/` directory
   - **Important:** Maintain the folder structure exactly as shown above

### **Step 3: Set File Permissions**

After uploading, set these permissions via Hostinger File Manager or FileZilla:

```
📁 Directories: 755
📄 PHP files: 644
📄 HTML/CSS/JS files: 644
📁 uploads/ directory: 755 (writable)
📄 .htaccess: 644
```

**Critical:** The `uploads/` directory must be writable (755) for file uploads to work.

### **Step 4: Database Setup**

Your database is already configured with these credentials:
- **Database:** `u795274726_payslipmax_db`
- **Username:** `u795274726_payslipmax_usr`
- **Password:** `dubpa5pycHyqdifwac@`

The database tables will be created automatically when you first access the website.

### **Step 5: Test the Deployment**

1. **Visit your website:** `https://yourdomain.com`
2. **Test upload functionality:** Try uploading a PDF file
3. **Check mobile responsiveness:** Test on different devices
4. **Verify security headers:** Use tools like securityheaders.com

---

## 🔧 **Post-Deployment Configuration**

### **SSL Certificate Setup**

Once your SSL certificate is active on Hostinger:

1. **Edit `.htaccess`** and uncomment these lines:
```apache
# Force HTTPS (uncomment when SSL certificate is installed)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

2. **Enable HSTS** by uncommenting:
```apache
# HSTS (uncomment when SSL is configured)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

### **Domain Configuration**

If you want to force www subdomain, uncomment these lines in `.htaccess`:
```apache
# Force www (uncomment if you want to force www)
RewriteCond %{HTTP_HOST} !^www\. [NC]
RewriteRule ^(.*)$ https://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🛡️ **Security Considerations**

### **File Upload Security**
- ✅ Only PDF files are allowed
- ✅ File size limited to 15MB
- ✅ Virus scanning implemented
- ✅ PHP execution blocked in uploads directory

### **Database Security**
- ✅ Prepared statements prevent SQL injection
- ✅ Input validation and sanitization
- ✅ Error logging for monitoring

### **Server Security**
- ✅ Security headers configured
- ✅ Directory browsing disabled
- ✅ Sensitive files protected
- ✅ Rate limiting implemented

---

## 📊 **Monitoring & Maintenance**

### **Log Files to Monitor**
- **Error logs:** Check Hostinger control panel for PHP errors
- **Access logs:** Monitor for unusual traffic patterns
- **Upload logs:** Track file upload activities

### **Regular Maintenance**
- **Weekly:** Check error logs
- **Monthly:** Review upload statistics
- **Quarterly:** Update security configurations

---

## 🚨 **Troubleshooting Common Issues**

### **Upload Not Working**
1. Check `uploads/` directory permissions (should be 755)
2. Verify PHP upload limits in Hostinger control panel
3. Check error logs for specific error messages

### **Database Connection Errors**
1. Verify database credentials in `api/db_config.php`
2. Check if database exists in Hostinger control panel
3. Ensure database user has proper permissions

### **CSS/JS Not Loading**
1. Check file paths are correct
2. Verify .htaccess is working
3. Clear browser cache

### **Mobile App Deep Links Not Working**
1. Verify `.well-known/` files are uploaded
2. Check file permissions (644)
3. Test with actual mobile devices

---

## 📞 **Support Contacts**

- **Hostinger Support:** Available 24/7 via live chat
- **Technical Issues:** Check error logs first
- **Performance Issues:** Monitor via Hostinger analytics

---

## ✅ **Deployment Verification Checklist**

After deployment, verify these items:

- [ ] Website loads correctly at your domain
- [ ] All CSS and JavaScript files load properly
- [ ] Upload functionality works with PDF files
- [ ] Mobile responsiveness works on all devices
- [ ] Security headers are active (check with securityheaders.com)
- [ ] Database connection is working
- [ ] Error pages display correctly
- [ ] SSL certificate is active (if configured)
- [ ] Mobile app deep links work (if applicable)

---

## 🎉 **Congratulations!**

Your PayslipMax website is now live on Hostinger with:
- ✨ Modern glassmorphism UI
- 🔒 Bank-level security
- 📱 Mobile-first design
- 🚀 Optimized performance
- 🎮 Gamification features

Your users can now securely upload and manage their payslips with a world-class experience! 