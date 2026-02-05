# Iseki Scan - Inventory & Rack Management System

## Overview

**Iseki Scan** is a specialized inventory tracking and warehouse management system designed to monitor parts across physical storage racks. It provides a digital twin of the warehouse floor, allowing for real-time scanning of items, rack assignment validation, and comprehensive audit trails for material movement.

The system is organized into three distinct operational modules: **Admin** (Infrastructure & Reporting), **User** (Field Operations), and **MC** (Management & Control Validation).

## Key Features

### 1. Infrastructure Management (Admin)
*   **Rack & Item Management**: Full CRUD operations for physical racks and inventory items.
*   **Bulk Operations**:
    *   **Excel Import**: Quickly populate the warehouse layout by importing rack configurations.
    *   **Excel Export**: Detailed exports of rack and item master data.

### 2. Field Operations (User)
*   **Scanning Workflow**: Mobile-optimized interface for scanning items and racks.
*   **Record Submission**: Capture snapshots of inventory movements.
*   **Request System**: Initiate and track requests for material or rack changes.

### 3. Management & Control (MC)
*   **Validation Workflow**: Specialized access for MC personnel to validate field records against physical inventory.
*   **Missing Process Tracking**: Identify gaps where material/rack scans were expected but not recorded.
*   **Audit Reports**: Dedicated MC exports for operational compliance.

### 4. Advanced Reporting
*   **Daily & Monthly Analytics**: Consolidated views of warehouse activity over time.
*   **High-Performance Grids**: Powered by **Yajra DataTables** for navigating large datasets efficiently.

## Technology Stack

### Backend
*   **Framework**: [Laravel 12.x](https://laravel.com)
*   **Language**: PHP ^8.2
*   **Database**: SQLite (Local) / MariaDB (Production)
*   **Key Libraries**:
    *   `phpoffice/phpspreadsheet`: Industrial-grade Excel processing.
    *   `yajra/laravel-datatables-oracle`: Advanced server-side data grid processing.

### Frontend
*   **Build Tool**: [Vite](https://vitejs.dev)
*   **Styling**: [Tailwind CSS v4.0](https://tailwindcss.com)
*   **HTTP Client**: Axios

## Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone <repository-url>
    cd iseki_scan
    ```

2.  **Install PHP Dependencies**
    ```bash
    composer install
    ```

3.  **Install Node Dependencies**
    ```bash
    npm install
    ```

4.  **Environment Setup**
    *   Copy the example environment file:
        ```bash
        cp .env.example .env
        ```
    *   Configure database and application settings in `.env`.

5.  **Initialize Application**
    ```bash
    php artisan key:generate
    php artisan migrate
    ```

6.  **Build Frontend Assets**
    ```bash
    npm run build
    ```

7.  **Run Development Server**
    ```bash
    php artisan serve
    ```
    The application will be accessible at `http://localhost:8000`.

## License

This project is proprietary.
