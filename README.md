# Transport Management System (PHP + MySQL)

A lightweight PHP 8 + MySQL 8 application to manage parcel movements, delivery notes, dues collections, expenses, and salaries across multiple branches.

## Tech Stack
- PHP 8+
- MySQL 8+
- Bootstrap 5

## Setup
1. Create database:
   - Create a MySQL database named `tms_db` (or update `config/config.php`).
   - Import `database/schema.sql` to create tables and seed sample data.

2. Configure app:
   - Update `config/config.php` with your DB credentials.

3. Run on WAMP/XAMPP:
   - Place the project in your web root. With WAMP, the public entry is `public/index.php`.
   - If using Apache with mod_rewrite enabled, `.htaccess` in `public/` will route all requests to `index.php`.

4. Login:
   - Username: `admin`
   - Password: `admin123`


   'kjljrelgerotlkr4

## Next Steps
- Implement CRUD modules for Branches, Customers, Suppliers, Parcels.
- Add Delivery Notes generation and printing.
- Add Due Collections, Expenses, Employees & Salaries pages.
- Add Reports and Search by phone number.

## Security Notes
- CSRF tokens added for POST forms.
- Use prepared statements everywhere (PDO).
- Implement role checks for admin/staff/accountant.

