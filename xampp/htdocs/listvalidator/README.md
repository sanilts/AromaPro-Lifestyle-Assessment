PHP/MySQL Email List Validator with Postmark Integration
This application allows users to upload contact lists, validate email addresses using the Postmark API, and download the validated lists. It includes user management with admin capabilities.
Features

User Authentication: Secure login/logout functionality with user roles (admin/user)
List Management: Create, view, download, and delete contact lists
Email Validation: Validate email addresses using Postmark API
CSV Processing: Upload and download contact lists in CSV format
Download Tracking: Track list downloads with user information and timestamps
User Management: Admin users can create, edit, and delete other users

Requirements

PHP 7.4 or higher
MySQL 5.7 or higher
Composer (for dependency management)
Postmark account with API key

Installation

Clone the repository
bashCopygit clone https://github.com/yourusername/list-validator.git
cd list-validator

Create the database
Import the database schema:
bashCopymysql -u username -p < database/schema.sql

Monitor Lists: View all lists and their validation statistics
Download Reports: Export validated email lists for users

Regular Users

Create Lists: Create a new list with a descriptive name
Upload Contacts: Upload a CSV file with contact information

CSV must include columns: First Name, Last Name, and Email


Validate Emails: Process email validation using Postmark API
View Results: See validation results with status indicators
Filter Lists: Filter contacts by validation status (valid, invalid, pending)
Download Lists: Download validated lists for use in marketing campaigns

CSV Format
Your CSV file should follow this format:
CopyFirst Name,Last Name,Email
John,Doe,john.doe@example.com
Jane,Smith,jane.smith@example.com
Email Validation Status
Emails are categorized into three statuses:

Valid: Email passed all Postmark validation checks
Invalid: Email failed one or more validation checks
Pending: Email has not been validated yet

Postmark Integration
This application uses Postmark's Email Validation API to verify email addresses. The validation checks include:

Syntax validation
Domain existence check
SMTP server check
Disposable email detection
Role-based email detection

Security Features

CSRF protection
Password hashing
Input sanitization
Session management
Role-based access control

Folder Structure
Copylistvalidator/
│
├── config/                  # Configuration files
├── includes/                # Core functionality
├── vendor/                  # Composer packages
├── public/                  # Publicly accessible files
│   ├── index.php            # Main entry point
│   ├── assets/              # CSS, JS, images
│   └── uploads/             # Temporary uploads
├── views/                   # HTML templates
├── api/                     # API endpoints
├── controllers/             # Application controllers
├── models/                  # Data models
└── database/                # Database scripts
Troubleshooting
Common Issues

Upload Errors

Ensure your PHP upload_max_filesize and post_max_size are set high enough
Check that the uploads directory is writable


Validation Errors

Verify your Postmark API key is correctly set in the configuration
Check your Postmark account has sufficient API credits


Database Connection Issues

Verify database credentials in config/database.php
Ensure MySQL server is running and accessible



Support
For issues, questions, or feature requests, please contact:

Email: support@example.com
GitHub Issues: https://github.com/yourusername/list-validator/issues Configure the application
Copy the example configuration file and edit it with your settings:
bashCopycp config/config.example.php config/config.php
Update the following in config/config.php:

Database connection details
Base URL
Postmark API key




Set up directory permissions
Make sure the uploads directory is writable:
bashCopychmod -R 755 public/uploads

Create admin user
Run the setup script to create the initial admin user:
bashCopyphp setup.php


Usage
Administrator

Login with your admin credentials
Manage Users: Add, edit, or delete users from the admin panel