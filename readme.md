# Rest Store Core API

Backend reference project built with Laravel to demonstrate real‑world e‑commerce backend skills such as authentication, orders, payments, and API design.

This project is designed as a **production‑style API** that can be reused as a base for future systems.

---

## Project Overview

Rest Store Core is an API backend for an online store.

It focuses on:

- Clean architecture
- Secure authentication
- Order lifecycle handling
- Payment gateway integration (Paymob)
- Scalable structure

The project is built as a reference system that reflects how backend services work in real products.

---

## What This Project Demonstrates

- API design using REST principles
- Real payment integration using Paymob
- Order and payment separation
- Transaction safety
- Validation and error handling
- Scalable service structure

It is not a demo CRUD only project.
It reflects real backend flows used in production.

---

## Key Features

- User authentication system
- Multi payment methods (Card, Wallet, Fawry)
- Orders management
- Order items handling
- Address management
- Payment processing with Paymob
- Payment callbacks verification (HMAC)
- Transaction logging
- API ready for frontend or mobile apps

---

## Payment Flow

1. Client sends order_id and payment method.
2. Backend authenticates with Paymob.
3. Creates Paymob order.
4. Generates payment key.
5. Stores payment record in database.
6. Returns iframe URL or wallet response.
7. Paymob calls callback URL.
8. Backend verifies HMAC.
9. Updates payment and order status.

This flow mimics real production payment systems.

---

## Tech Stack

- Laravel
- MySQL
- REST API
- Paymob API
- Postman for testing
- GitHub for version control

---

## Architecture Style

- Controllers handle request flow
- Services handle external integrations
- Models represent business entities
- Payments separated from orders
- Config driven integrations

This structure allows easy scaling and reuse.

---

## Database Design

Main tables:

- users
- orders
- order_items
- order_addresses
- payments

Payments are separated from orders to support:

- Multiple payment attempts
- Refund logic
- Auditing
- Gateway tracking

---

## Skills Demonstrated

- Backend API design
- Payment gateway integration
- Secure callbacks handling
- Clean code practices
- Error handling
- Database relations
- Transaction management

This project reflects backend skills needed in real products.

---

## How To Run

1. Clone repository
2. Install dependencies
3. Configure .env
4. Run migrations
5. Serve project

---

## API Example

Start payment

POST /api/user/payments/pay

Parameters

- order_id
- pay_method

Response

Returns Paymob iframe URL or wallet response.

---

## Why This Project Matters

Instead of simple CRUD examples, this project shows:

- Business logic
- External services integration
- Real payment handling
- Production‑style backend flow

It can be reused as a core backend template for other projects.

---

## Author

Abdulrahman Awad

Backend Developer

GitHub: https://github.com/abdulrahman-awad1

