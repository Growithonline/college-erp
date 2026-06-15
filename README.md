# College ERP

College ERP is a Laravel-based institute management system focused on admission, fee collection, fee rules, role-based access, and operational reporting.

This repository is not a generic Laravel starter anymore. It contains a multi-role ERP workflow for:

- Super admin onboarding institutes
- Institute-level academic and fee setup
- Student admission and subject mapping
- Fee charging, collection, receipts, and wallet ledger tracking
- Center, staff, and channel partner access
- Session-wise reports for admissions and collections

## Key Features

- Multi-role authentication for super admin, institute admin, center, staff, and channel partner
- Institute-specific master data and fee configuration
- Full admission flow with preview and confirmation
- Quick admission/registration flow
- Dynamic admission form builder and receipt layout configuration
- Course, stream, subject, and year-rule management
- Course-level and subject-level fee rule engine
- Student wallet and institute wallet ledger tracking
- Fee invoice generation, receipt printing, and cancellation support
- Payment mode and bank account permissions
- Fee due list, fee collection report, and admission report
- Student promotion workflow

## Roles and Login URLs

| Role | Login URL | Notes |
| --- | --- | --- |
| Super Admin | `/super-admin/login` | Creates institutes and sends credentials |
| Institute Admin | `/login` | Uses institute ID + email + password + OTP |
| Center | `/center/login` | Direct login with role-based permissions |
| Staff | `/staff/login` | Direct login with staff-role permissions |
| Channel Partner | `/partner/login` | Direct login with partner permissions |

## Main Modules

### 1. Institute Setup

Institute admins can configure:

- Academic sessions
- Courses and course parts
- Streams
- Subjects and stream-subject mapping
- Fee types
- Fee assignments
- Course fee rules
- Subject fee rules
- Centers
- Channel partners
- Staff roles and staff members
- Institute bank accounts
- Payment mode permissions
- Admission/quick/online/receipt form settings

### 2. Admissions

The admission module supports:

- Full admission form
- Quick registration flow
- Admission preview before final save
- Subject selection by stream/year
- Printable forms and combined print output
- Editing existing student records
- Promotion to next academic stage

### 3. Fees and Wallets

The fee system works in two parts:

- Fee calculation based on course rules, subject rules, session, semester, category, gender, student type, and admission source
- Ledger-based wallet updates when fees are charged or collected

Important outcomes:

- Admission can create fee charge entries
- Fee collection creates invoices and invoice items
- Student wallet balance shows due/clear status
- Institute wallet stores collection-side accounting entries

### 4. Reports

Available reports include:

- Fee due list
- Fee collection report
- Admission report

## Tech Stack

- PHP 8.2
- Laravel 12
- Blade templates
- Eloquent ORM
- Vite
- Tailwind CSS
- Alpine.js
- MySQL or another Laravel-supported SQL database

## Important Application Behavior

- Institute admin login uses OTP verification through email.
- Super admin can create institutes and issue institute login credentials.
- Student IDs and fee invoice numbers are generated with counter tables and database locking.
- Uploaded files such as institute images, proofs, and student photos use the `public` storage disk.
- Role-based routes are separated for institute, center, staff, partner, and super admin users.

## Local Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js and npm
- A configured database

### Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

If you prefer, you can also use the bundled setup script:

```bash
composer run setup
```

Before logging in, make sure your `.env` is configured for:

- Database connection
- Mail sending, because institute login OTP and institute credential emails are sent by email
- App URL if you want correct links in emails

### Run the app

```bash
npm run dev
php artisan serve
```

Or use the combined local development command:

```bash
composer run dev
```

## Seeded Data

The default seeders create:

- A super admin account
- Course types such as UG, PG, Diploma, PhD, Certificate, and ITI
- A sample institute
- Default staff roles for that institute

### Seeded Super Admin

- Email: `sy5674713@gmail.com`
- Password: `Suraj1234`

### Seeded Institute Admin

- Email: `cm5674715@gmail.com`
- Password: `Admin@123`
- Institute UID: generated during seeding and shown in console output

After seeding, change these passwords immediately if you use this data outside local development.

## Useful Commands

```bash
php artisan migrate
php artisan db:seed
php artisan test
php artisan config:clear
php artisan route:list
```

## Project Structure

```text
app/
  Http/Controllers/
    Institute/
    SuperAdmin/
    Center/
    Staff/
    Partner/
  Models/
  Services/
database/
  migrations/
  seeders/
resources/
  views/
routes/
  web.php
  super_admin.php
```

## Core Files Worth Reading First

- `routes/web.php`
- `routes/super_admin.php`
- `app/Services/FeeCalculatorService.php`
- `app/Services/WalletService.php`
- `app/Http/Controllers/Institute/Admission/AdmissionController.php`
- `app/Http/Controllers/Institute/Fee/FeeCollectionController.php`
- `app/Http/Controllers/Institute/Reports/ReportController.php`
- `app/Http/Controllers/Institute/Master/AdmissionFormController.php`

## Current Scope

This codebase is best understood as a working college ERP focused on:

- admissions,
- fee structure definition,
- fee collection,
- role-based operations,
- and institute-level reporting.

It already includes business-specific workflows beyond a starter Laravel app, and the README should be treated as a project overview derived from the codebase as it exists today.
