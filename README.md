# Distributed 3-Tier Client-Server Application

## 1. Title and Introduction
This project is a containerized 3-tier distributed client-server application built to demonstrate state management, user authentication, data persistence, and inter-service container networking. The system provides secure user registration, session-based logins, profile dashboards, contact data collection, text-based data querying, and an automated mock transactional email flow for asynchronous password resets.

---

## 2. Architecture
The application adheres strictly to a decoupled 3-tier distributed architecture pattern, utilizing an isolated virtual network to bridge services:

* **Presentation Tier (Client):** The Web Browser acting as the front-end user interface, handling client-side rendering of HTML5 forms and modern CSS layout views.
* **Application Tier (Server Logic):** An Apache web server running PHP 8.2 runtime logic to process state operations, session cookies, database queries, and SMTP email dispatches.
* **Data Tier (Storage Engine):** A discrete MySQL 8.0 relational database engine running inside an isolated backend layer to store structured system tables, handles input persistence, and runs indices for data retrieval.
* **Asynchronous Integration Service:** A Mailpit SMTP catch-all engine decoupled from the application logic to safely intercept, log, and parse transactional password reset routing blocks.