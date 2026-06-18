# WanderPlan Travel Planner

WanderPlan is a PHP and MySQL travel planning web app for organizing group trips, bookings, activities, expenses, payments, travel documents, emergency contacts, notifications, and admin reporting.

## Features

- User registration and sign in
- Role-based redirects for admin and users
- Trip creation and management
- Booking and payment flow
- Activity and itinerary planning
- Expense tracking
- Travel document uploads
- Notifications
- Admin dashboard
- PHPUnit test suite and project reports

## Screenshots

### Homepage

![Homepage screen](screenshots/homepage.png)

### Sign In

![Sign in screen](screenshots/signin.png)

### Register

![Register screen](screenshots/register.png)

## Requirements

- PHP, for example the PHP executable included with XAMPP
- MySQL or MariaDB
- Composer, optional for installing dev dependencies

The app is configured to use this local database:

```php
host: localhost
database: travel_planner1
user: root
password:
```

## Run Locally

Start MySQL, create/import the `travel_planner1` database, then run the PHP development server from the project root:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

Open:

```text
http://127.0.0.1:8000/Views/Auth/signin.php
```

The database-backed pages, including the user homepage and dashboards, require MySQL to be running with the expected schema.

## Tests

Run the PHPUnit PHAR included in the project:

```powershell
C:\xampp\php\php.exe phpunit-9.phar
```

Latest local result with XAMPP/MySQL running:

```text
OK (31 tests, 57 assertions)
```

## Project Structure

```text
config/        Database configuration
Controllers/   Request handling and business logic
Models/        Domain models
Views/         PHP views and static assets
sql/           Database migration/fix scripts
tests/         PHPUnit tests and performance test assets
uploads/       Uploaded demo images/documents
screenshots/   README screenshots
```
