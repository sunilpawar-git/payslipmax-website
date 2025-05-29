# PayslipMax API

This API provides backend services for the PayslipMax application, enabling web uploads of payslip PDFs to the mobile app.

## Installation Guide

### Prerequisites

- Web server with PHP 7.4+ (8.0+ recommended)
- MySQL/MariaDB database
- SSL certificate (required for production)
- PHP extensions: mysqli, json, fileinfo, openssl

### Setup Instructions

1. **Upload Files**
   - Upload all API files to your web server in a directory named `api` (e.g., `https://payslipmax.com/api/`)

2. **Create Required Directories**
   - Ensure these directories exist and are writable by the web server:
     ```
     /uploads
     /logs
     ```

3. **Database Configuration**
   - Create a MySQL database for the application
   - Edit `db_config.php` with your database credentials:
     ```php
     $db_config = [
         'host' => 'your_database_host',
         'username' => 'your_database_username',
         'password' => 'your_secure_password',
         'database' => 'your_database_name'
     ];
     ```

4. **Web Server Configuration**
   - Ensure mod_rewrite is enabled if using Apache
   - If using Nginx, configure URL rewriting to direct all requests to index.php

5. **SSL Certificate**
   - Install an SSL certificate on your domain
   - Configure your web server to force HTTPS

6. **Set Up Cron Job for Cleanup**
   - Add the following cron job to run daily:
     ```
     0 2 * * * /usr/bin/php /path/to/your/api/cleanup.php >> /path/to/your/api/logs/cron.log 2>&1
     ```

## API Endpoints

### Device Registration
- **URL**: `/api/devices/register`
- **Method**: `POST`
- **Description**: Registers a device for receiving web uploads
- **Request Body**: JSON with device information
  ```json
  {
    "deviceName": "iPhone 13",
    "deviceType": "iPhone",
    "osVersion": "15.0",
    "appVersion": "1.0.0"
  }
  ```

### File Upload
- **URL**: `/api/upload_file.php`
- **Method**: `POST`
- **Description**: Uploads a PDF file
- **Form Data**:
  - `file`: PDF file
  - `device_token`: Optional device token
  - `password`: Optional password for PDF protection

### Pending Uploads
- **URL**: `/api/uploads/pending`
- **Method**: `GET`
- **Description**: Gets pending uploads for a registered device
- **Headers**: `Authorization: Bearer DEVICE_TOKEN`

### File Download
- **URL**: `/api/download`
- **Method**: `GET`
- **Description**: Downloads a specific file
- **Parameters**:
  - `id`: Upload ID or string ID
  - `token`: Secure token for this file

## Security Considerations

- All API endpoints require proper authentication
- Web uploads are protected against direct file access
- Passwords for PDFs are stored securely using password_hash
- Files are automatically cleaned up after processing
- Input validation is performed on all endpoints

## Troubleshooting

- **Check Logs**: Examine the logs directory for detailed error information
- **Database Connection Issues**: Verify your database credentials in db_config.php
- **Permission Errors**: Ensure the uploads and logs directories are writable
- **API Not Found**: Check that mod_rewrite is enabled and .htaccess is being processed

## Support

For any questions or issues, please contact support@payslipmax.com 
