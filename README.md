Containerized Client-Server Application (PHP + MySQL + Mailpit)
Unit: Distributed Systems, 
Kabarak University Activity 3:
Assignment: Praise Njenga(BBIT/N/0225/01/25)

1. Introduction
This project is a simple client-server web application that runs entirely on my localhost environment using Docker and Docker Compose. It lets a user register, log in, save contact details, search for saved contacts, and recover a forgotten password by email.
The application is split into separate services that run in their own containers and communicate over a Docker network. This demonstrates the interaction between client and server components, a layered architecture, and successful communication between the system components.

2. Architecture
The system follows a 3-tier (layered) architecture, plus one supporting service.
flowchart TD
    A["Client / Web Browser<br/>(Presentation layer)"] -->|"HTTP requests<br/>localhost:8080"| B["Web container: PHP 8.2 + Apache<br/>(Application / logic layer)"]
    B -->|"HTTP responses<br/>(HTML pages)"| A
    B -->|"SQL queries via PDO<br/>host: db, port 3306"| C[("MySQL 8.0 container<br/>(Data layer)")]
    C -->|"Query results"| B
    B -->|"SMTP<br/>host: mail, port 1025"| D["Mailpit container<br/>(Email testing service)"]
    A -.->|"View captured emails<br/>localhost:8025"| D
Layer	Component	Responsibility
Presentation (client)	Web browser	Displays the HTML/CSS pages and sends user input as HTTP requests
Application (server)	PHP 8.2 + Apache container (web)	Handles requests, authentication, sessions, validation and business logic
Data	MySQL 8.0 container (db)	Stores users and contacts, with a Docker volume (db_data) for persistence
Supporting service	Mailpit container (mail)	Captures test emails (for example, password-reset messages) instead of sending them to real inboxes
Each layer only talks to its neighbour. The browser talks to the PHP application, and the PHP application talks to MySQL and Mailpit. The browser never connects to the database directly.
The containers find each other by service name (db, mail) through the Docker Compose network, not by IP address.

3. Technologies Used
?PHP 8.2 with Apache
?MySQL 8.0
?Mailpit (email testing)
?Docker and Docker Compose
?HTML and CSS
?Visual Studio Code, PowerShell

4. Project Structure
client-server-app/
©À©¤©¤ Dockerfile              # Builds the PHP + Apache web container
©À©¤©¤ docker-compose.yml      # Defines the web, db and mail services
©À©¤©¤ init.sql                # Creates the database tables on first start
©À©¤©¤ index.php               # Login page
©À©¤©¤ register.php            # Create a new account
©À©¤©¤ dashboard.php           # Protected page: save contact details
©À©¤©¤ search.php              # Search contacts by registration number
©À©¤©¤ forgot-password.php     # Request a password reset email
©À©¤©¤ reset-password.php      # Set a new password using the emailed token
©À©¤©¤ logout.php              # End the session
©¸©¤©¤ Screenshots/ # Evidence of the working system

5. How the Client and Server Interact
Example: logging in
1.The browser sends a POST request with the username and password to index.php on the web container.
2.PHP connects to the MySQL container (host db) and looks up the user with a prepared statement.
3.PHP checks the submitted password against the stored bcrypt hash using password_verify().
4.If it matches, PHP starts a session and redirects the browser to dashboard.php.
5.The browser displays the dashboard returned by the server.
Example: password recovery (communication between three containers)
1.The user enters their email on forgot-password.php.
2.The web container checks the email in the MySQL container and saves a random reset token.
3.The web container sends an email containing the reset link to the Mailpit container over SMTP (port 1025).
4.The user opens Mailpit at localhost:8025, clicks the link, and reset-password.php verifies the token in the database.
5.The new password is hashed and saved, and the token is deleted so it cannot be reused.

6. Features
Task	Feature	What it demonstrates
A	Forgot password with email reset link	Communication between the web, database and email services
B	User registration and login with sessions	Request processing in the application layer and authentication against the data layer
C	Save contact details	A database write operation (client to server to database)
D	Search contact by registration number	A database read operation (database to server to client)
Security and design points:
?Passwords are hashed with bcrypt before being stored.
?Database queries use prepared statements to prevent SQL injection.
?Output is escaped with htmlspecialchars().
?Protected pages (dashboard.php, search.php) redirect to the login page when there is no active session.
?UNIQUE constraints in MySQL prevent duplicate usernames, emails and registration numbers.
?A Docker volume keeps the database data when containers are restarted.

7. How to Run
Requirements: Docker Desktop (with Docker Compose), and free ports 8080, 8025 and 3306.
# 1. Clone the repository
git clone https://github.com/praisenjenga1-collab/client-server-app.git
cd client-server-app

# 2. Build and start all three services
docker compose up --build

# 3. In a second terminal, confirm the containers are running
docker compose ps
Then open:
Service	URL
Web application	http://localhost:8080/index.php
Mailpit inbox	http://localhost:8025
To stop the application:
docker compose down
Do not use docker compose down -v unless you want to delete the database data.
Test steps
1.Click Create New Account and register a user (use a real-looking email).
2.Log in with the new account to open the dashboard.
3.Enter contact details and click Save Contact Data.
4.Click Go to Task D Search and search for the saved registration number.
5.Log out, click Forgot Password?, and enter the registered email.
6.Open Mailpit at localhost:8025, open the email, and click the reset link.
7.Set a new password and log in with it.
To check the stored data directly in the database container:
docker compose exec db mysql -uroot -psecret_pass -e "SELECT * FROM contact_db.contacts;"

8. Screenshots
Containers running (web, db and mail)
docker compose ps
Login page
Login page
Registration (Task B)
Registration
Dashboard after login
Dashboard
Contact saved successfully (Task C)
Contact saved
Search result (Task D)
Search result
Password reset email captured in Mailpit (Task A)
Mailpit inbox
Password successfully updated
Password updated
Data stored in the MySQL container
Database contents

9. Conclusion
This project shows how a client-server application can be split into layers and packaged as separate containers. The browser (client) sends requests to the PHP application server, which applies the application logic and communicates with a separate MySQL database service and a Mailpit email service. Docker Compose defines and runs the services together, and the services communicate over a shared network using their service names. The result is a portable application that runs the same way on any machine with Docker installed.
