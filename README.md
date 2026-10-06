# BuyHub

BuyHub is a small second-hand marketplace web app. People create an
account, list things they no longer need with a photo and a price, and
other people buy them outright.

## What you can do

- **Create an account** and log in.
- **Browse and search** listings by keyword, price and seller.
- **List an item** with a title, description, price, stock and one photo.
- **Manage your items** — edit or delete anything you posted.
- **Buy an item** straight away, or add it to a cart and check out.
- **See your orders** and open a receipt for each one.

## How it works

- Every listing has a stock count, so one seller can offer many of the
  same thing in a single advert. Sold-out items drop off the home page
  automatically.
- Photos are stored in the database, so they stay with their listing.
- Pages only display things; every action that changes data is a separate
  POST form, so nothing changes from a plain link or a refresh.

## Running it

1. Create a MySQL database and import `database.sql` (kept locally, not
   committed — see the note below).
2. Point `config.php` at the database (host, name, user, password).
3. Open the site in a browser and create an account.

## Tech

PHP, MySQL (via PDO), and plain HTML/CSS/JavaScript — no frameworks.

## About the database files

The SQL schema and seed files (`database.sql`, and the variants) are kept
**locally only** and are excluded from the repository and the Docker image,
because they contain the sample accounts' password hashes. If you clone this
repo you will not get them — the deployed site does not need them.
