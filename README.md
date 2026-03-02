# Sharurah Hardware Ltd Management System

A comprehensive management system for Sharurah Hardware Ltd Uganda, featuring inventory management, sales tracking, customer management, accounting, and payment processing with MTN and Airtel mobile money integration.

## Features

### 📦 Inventory Management
- Complete product catalog with categories
- Stock tracking and low stock alerts
- Supplier management
- Barcode support
- Stock movement history
- Product search and filtering

### 🛒 Sales Management
- Create and manage sales orders
- Customer order history
- Multiple payment methods
- Order status tracking
- Receipt generation
- Sales analytics and reporting

### 👥 Customer Management
- Customer registration and profiles
- Customer types (Retail, Wholesale, Corporate)
- Credit limit management
- Customer balance tracking
- Customer search and filtering

### 💳 Payment Processing
- Multiple payment methods:
  - Cash
  - MTN Mobile Money (0773586844)
  - Airtel Money (0704467880)
  - Bank Transfer
  - Credit
- Payment status tracking
- Transaction history
- Mobile money integration ready

### 📊 Accounting Module
- Chart of accounts
- Journal entries
- Expense tracking
- Financial reports
- Profit and loss statements
- Balance sheet

### 🔐 User Authentication
- Secure login system
- Role-based access control
- User activity logging
- Session management

## Product Categories

The system includes all hardware products sold by Sharurah Hardware Ltd:
- Laps
- Mixer
- Toilet
- Basins
- Bathroom Cabinet
- Sinks
- Pedestal Basins
- Shataf
- Shower Mixer
- Squatting Toilet Pan
- Soap Dish
- Tooth Brush Holder
- TP Holder
- Instant Water Heater
- Concealed Toilet
- Angle Value
- Mirror
- PPR Machine
- Toilet Seat
- Wall Mount
- Water Meter
- Other

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

### Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE sharurah_hardware_db;
```

2. Import the database schema:
```bash
mysql -u root -p sharurah_hardware_db < database_schema.sql
```

3. Update database credentials in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'sharurah_hardware_db';
private $username = 'root';
private $password = 'your_password';
```

### File Structure

```
sharurah-hardware-system/
├── config/
│   └── database.php          # Database configuration
├── classes/
│   ├── Auth.php              # Authentication class
│   ├── Inventory.php         # Inventory management
│   ├── Sales.php             # Sales management
│   ├── Customer.php          # Customer management
│   ├── Payment.php           # Payment processing
│   └── Accounting.php        # Accounting module
├── css/
│   └── style.css             # Main stylesheet
├── js/
│   └── app.js                # Frontend JavaScript
├── database_schema.sql       # Database schema
├── index.html                # Main dashboard
├── login.html                # Login page
└── README.md                 # This file
```

### Default Login Credentials

- **Username:** admin
- **Password:** admin123

⚠️ **Important:** Change the default password after first login!

## Usage

### 1. Login
- Navigate to `login.html` in your browser
- Enter your username and password
- Click "Login" to access the dashboard

### 2. Dashboard
- View overview statistics
- Monitor recent orders and activity
- Access sales charts and analytics

### 3. Inventory Management
- Add new products
- Update stock levels
- View product details
- Manage suppliers
- Track stock movements

### 4. Sales Management
- Create new sales orders
- Add products to orders
- Process payments
- Generate receipts
- Track order status

### 5. Customer Management
- Register new customers
- View customer profiles
- Track customer balances
- Manage customer credit

### 6. Payment Processing
- Process payments via multiple methods
- Accept MTN Mobile Money
- Accept Airtel Money
- Track payment status
- View transaction history

### 7. Accounting
- Create journal entries
- Track expenses
- Generate financial reports
- View profit and loss statements

## Mobile Money Integration

### MTN Mobile Money
- **Phone Number:** 0773586844
- Integration ready for MTN Mobile Money API

### Airtel Money
- **Phone Number:** 0704467880
- Integration ready for Airtel Money API

## Security Features

- Password hashing using bcrypt
- SQL injection prevention using prepared statements
- Session management
- Role-based access control
- Activity logging
- CSRF protection (to be implemented)

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Opera

## Support

For support and inquiries:
- **Phone:** 0773586844 | 0704467880
- **Email:** info@sharurahhardware.ug
- **Location:** Sharurah, Uganda

## License

This system is proprietary software for Sharurah Hardware Ltd.

## Credits

Developed for Sharurah Hardware Ltd Uganda
© 2024 All Rights Reserved

---

**Note:** This is a comprehensive management system designed specifically for Sharurah Hardware Ltd. All features are tailored to meet the business needs of a hardware store in Uganda, including local payment methods and currency support.