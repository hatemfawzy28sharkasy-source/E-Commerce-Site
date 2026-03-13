# Simple E-Commerce Computer Shop

## Overview

This project is a **Simple E-Commerce Web Application** for selling computer components online.
It allows users to browse products, add them to a shopping cart, and complete purchases through a basic checkout system.

The system is developed using **PHP for the backend**, **MySQL for the database**, and **HTML + Tailwind CSS for the interface**.
It also uses **PHP sessions** to manage user login and shopping cart functionality.

---

# Features

## Product Catalog

* Display available computer components
* Show product information including:

  * Product name
  * Category
  * Description
  * Price
  * Stock quantity
* Automatically display product images if available

---

## User Authentication

* User registration and login system
* Session-based authentication
* Restrict shopping actions to logged-in users

---

## Shopping Cart

Users can:

* Add products to the cart
* Increase product quantity automatically
* Remove products from the cart
* View items currently in their cart
* See the total cart price

---

## Checkout System

* Secure checkout process
* Automatically decreases product stock after purchase
* Prevents checkout if product stock is insufficient
* Clears the cart after successful checkout

---

## Notifications and Messages

* Success messages for completed actions
* Error messages for failed operations
* User feedback for login, cart actions, and checkout

---

# Technologies Used

## Backend

* PHP

## Database

* MySQL

## Frontend

* HTML
* Tailwind CSS

## Additional Tools

* PHP Sessions
* Prepared SQL Statements
* Responsive UI design

---

# Database Structure

The system uses the following main tables:

* **products** – stores product information such as name, price, category, description, and stock quantity.
* **cart_items** – stores the products added to the user's cart.
* **users** – stores user account information.

---

# How to Run the Project

1. Install a local server environment such as:

   * XAMPP
   * WAMP
   * Laragon

2. Create a MySQL database named:

```
computer_shop
```

3. Import the SQL database tables into the database.

4. Place the project folder inside the server directory:

```
htdocs
```

5. Open the project in your browser:

```
http://localhost/project-folder
```

---

# Project Purpose

This project was created as a **learning project for web development**.
It demonstrates:

* Building a dynamic website using PHP
* Connecting PHP with a MySQL database
* Implementing a shopping cart system
* Managing user sessions
* Handling product inventory during checkout

---

# Author

Developed as a practice project for learning **PHP, MySQL, and basic e-commerce system design**.
