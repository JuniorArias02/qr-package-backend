De una, señor Stark 🔥
Aquí tienes el **Markdown listo para enviar a Gemini 3 Pro**, con **todo el contexto**, sin huecos y bien estructurado. Al final te respondo lo del **enum vs tabla** (spoiler: te digo cuál usar y por qué).

---

```md
# QR Package Delivery System – Technical Redesign Brief

## Context
This project is already implemented at a base level.  
The goal is to **refactor and reorganize the system logic and database** according to a new real-world workflow involving a **QR scanning machine** that automates package assignment to drivers.

This document explains the **full business logic, system flow, and database structure** that must be respected during refactoring.

---

## Roles & Responsibilities

### Admin
- Manages coordinators (CRUD)
- Views productivity statistics per coordinator
- Views statistics related to drivers’ deliveries

### Coordinator
- Manages drivers (CRUD)
- Operates or supervises the QR scanning machine
- Assigns packages to drivers (automatic or manual)
- Views driver statistics:
  - Total packages
  - Monthly statistics

### Driver
- Only responsible for delivery
- Views assigned packages
- Scans delivery QR
- Adds delivery observation
- Marks package as delivered

---

## Core Concept

The **QR scanning machine** is the heart of the system.

There are **two different QR codes** in the lifecycle:

1. **Original Package QR**
   - Comes from logistics
   - Only identifies the package (package_reference)

2. **System-Generated Delivery QR**
   - Generated after assignment
   - Represents the delivery process
   - Is physically attached to the package

---

## Assignment Modes

The system has a global configuration with two modes:

### 1. Automatic Mode
- The system selects the most suitable driver based on:
  - Package address / zone
  - Driver’s assigned zone
  - Driver availability
  - (Future-ready for geolocation or AI logic)
- The package is automatically assigned to the driver

### 2. Assign-To Mode
- The coordinator explicitly selects the driver
- The package is assigned manually

This configuration is stored in the database and can be changed without modifying code.

---

## Delivery Creation Flow (Step-by-Step)

### Step 1: Scan Original Package QR
- Machine scans the QR
- The system retrieves:
  - `package_reference`
  - Package address and zone

---

### Step 2: Decide Assignment Mode
- System reads `assignment_mode`
- Automatic → system selects driver
- Assign-To → coordinator selects driver

---

### Step 3: Create Daily Identifier
- Each day has a unique identifier:
  - Example: `20260303`
- Stored as `daily_code`
- Used to group deliveries by day

---

### Step 4: Create Delivery Record
A **delivery** is created with:
- Unique delivery ID
- Linked package
- Assigned driver
- Coordinator reference
- Daily batch reference
- Delivery order number for that day:
  - Calculated as:
    - Count deliveries for that `daily_code`
    - Increment by 1

---

### Step 5: Generate Delivery QR
The system generates a QR containing a URL like:

```

[https://app.com/delivery](https://app.com/delivery)?
delivery_id=123
&package_id=456
&daily_code=20260303
&order=7

````

This QR:
- Represents the delivery
- Is printed and attached to the package
- Locks the package from reassignment

---

### Step 6: Driver Delivery
- Driver scans the QR
- System loads delivery info
- Driver:
  - Writes observation
  - Clicks "Delivered"
- System stores:
  - Delivered timestamp
  - Final status

---

## Database Design (DBML)

```dbml
Table users {
  id int [pk, increment]
  name varchar
  email varchar [unique]
  password varchar
  role enum('admin', 'coordinator', 'driver')
  created_at datetime
}

Table coordinators {
  id int [pk, increment]
  user_id int [ref: > users.id]
}

Table drivers {
  id int [pk, increment]
  user_id int [ref: > users.id]
  coordinator_id int [ref: > coordinators.id]
  active boolean
}

Table packages {
  id int [pk, increment]
  package_reference varchar [unique]
  address varchar
  zone varchar
  status enum('pending', 'assigned', 'delivered')
  created_at datetime
}

Table daily_batches {
  id int [pk, increment]
  daily_code varchar [unique]
  date date
}

Table deliveries {
  id int [pk, increment]
  package_id int [ref: > packages.id]
  driver_id int [ref: > drivers.id]
  coordinator_id int [ref: > coordinators.id]
  daily_batch_id int [ref: > daily_batches.id]

  delivery_order int
  qr_url varchar

  status enum('assigned', 'delivered')
  observation text

  assigned_at datetime
  delivered_at datetime
}

Table system_settings {
  id int [pk, increment]
  assignment_mode enum('automatic', 'assign_to')
}
````

---

## Key Rules

* A package can only have **one active delivery**
* Once a delivery is created, the package is locked
* Drivers cannot reassign or edit deliveries
* All statistics are derived from `deliveries`

---

## Goal of Refactor

* Align database with real-world machine flow
* Centralize delivery logic
* Make system scalable for:

  * Route intelligence
  * AI-based assignment
  * Geolocation tracking

