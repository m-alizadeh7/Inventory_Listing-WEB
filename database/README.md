# Database Schema

This directory contains the complete database schema for the Inventory Management System.

## Files

- `db_complete.sql` - Complete database schema including all tables, relationships, and default data

## Usage

To set up the database:

1. Create a new MySQL database
2. Import `db_complete.sql` using phpMyAdmin or command line:
   ```bash
   mysql -u username -p database_name < db_complete.sql
   ```

## Schema Overview

The database includes:
- **Inventory Management**: Tables for inventory items, categories, transactions
- **Production Management**: Tables for devices, BOM, production orders
- **Supplier Management**: Tables for suppliers and their information
- **User Management**: Tables for users, roles, permissions, and activity logs

## Notes

- All tables use `IF NOT EXISTS` to prevent errors on re-import
- Default data includes user roles, permissions, and product categories
- Foreign key constraints ensure data integrity
