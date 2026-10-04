# Mini Marketplace — Group Project PHP

A small second-hand marketplace. Log in, buy an item, or post one of your own.

## The URL

```
http://localhost/marketplace/
```

Demo logins (after importing `database.sql`), password `password123` for all:

| Email | Sells |
|---|---|
| `aisyah@example.com` | jacket, AirPods |
| `weiming@example.com` | iPhone, MacBook, 50 office chairs |
| `siti@example.com` | IELTS book |

## What an account collects

Registration asks for more than a name now, because a marketplace is about
trust between strangers:

| Field | Required | Notes |
|---|---|---|
| First name, last name | yes | shown next to your listings |
| Email, password | yes | password is stored only as a hash |
| Date of birth | yes | checked at signup, **18+ only** |
| Phone, city, gender | no | greyed into an "optional" group |

The birthday is stored as a `DATE`, never as an age — an age column is wrong
the day after you write it. `age_from()` works it out when needed.

---

## Setup

1. **Import the database** — phpMyAdmin at <http://localhost/phpmyadmin>,
   **Import** tab, choose `database.sql`, **Go**. Creates the `marketplace`
   database, 4 tables, 3 users, 6 items.
2. **Check `config.php`** — Laragon defaults (`root`, blank password) are
   already filled in.
3. Open <http://localhost/marketplace/>.

---

## File structure

```
Marketplace/
│
├── index.php               Home page — search bar + every item with stock > 0
├── browse.php              Search results + the filter panel
├── item.php                One item's details + Buy now / Add to cart
├── login.php               Login form
├── register.php            Registration form
├── add-item.php            Form to post a new item
├── edit-item.php           Form to edit one of your items
├── my-items.php            Your items, with Edit / Delete
├── cart.php                Your shopping cart before checkout
├── my-orders.php           The things you purchased
│
├── process/                ── every file here CHANGES data ──
│   ├── login.php             checks the password
│   ├── register.php          saves a new account
│   ├── logout.php           destroys the session
│   ├── add-item.php          saves a new item + uploads the photo
│   ├── edit-item.php         saves the changes
│   ├── delete-item.php       deletes the row + the photo file
│   ├── buy.php               takes stock away + records a purchase
│   ├── add-cart.php          adds an item to the cart
│   ├── update-cart.php       changes a cart line's quantity
│   ├── remove-cart.php       drops a line from the cart
│   └── checkout.php          turns cart rows into purchases
│
├── includes/               ── shared code ──
│   ├── db.php                the PDO connection
│   ├── auth.php              login checks, CSRF, require_post()
│   ├── helpers.php           e(), money(), flash(), redirect(), age_from()
│   ├── icons.php             inline SVG icons (Lucide, ISC licence)
│   ├── search.php            search + filter SQL, all parameters bound
│   ├── upload.php            photo validation and saving
│   ├── item-fields.php       form fields shared by add + edit
│   ├── header.php            top of every page + navigation
│   ├── footer.php            bottom of every page
│   └── .htaccess             blocks direct access to this folder
│
├── assets/
│   ├── style.css              all the styling
│   └── app.js                 quantity stepper + toast timer
│
├── uploads/                ── photos uploaded by members ──
│   ├── .htaccess              blocks PHP from running here
│   └── sample-*.svg           placeholder pictures for the sample data
│
├── config.php              database credentials + settings
├── database.sql            tables + sample data
└── README.md
```

### Why it is split like this

- **Root = pages.** They only ever *display* things. Keeping them at the
  root means the URLs stay short and readable: `/login.php`, `/item.php?id=3`.
- **`process/` = actions.** They only ever *change* things. They never print
  HTML — each one either saves data or bounces back to a page with a message.

So the rule your group can state in one sentence:

> **Pages show. Process files act.**

If your teacher asks *"where does the saving happen?"*, the answer is the
seven files in `process/`.

Nothing in `process/` can be reached by typing its URL. Each one starts with:

```php
require_post();   // rejects a plain GET visit
check_csrf();     // rejects a forged request
require_login();  // rejects anonymous users
```

---

## The design

Everything visual comes from one file, `assets/style.css`, built on CSS custom
properties at the top. Change a value there and it changes everywhere.

**Currency** — Philippine peso, set in `config.php`:

```php
'currency'        => 'PHP',
'currency_symbol' => "\u{20B1}",   // ₱
```

`money()` reads that, so `config.php` is the only file you touch to switch
to another currency — it also drives the "Price (PHP)" label on the item
form. A lookup table in `helpers.php` covers the common ones (MYR, USD, EUR,
SGD, IDR, THB, JPY…), and an unknown code still prints readably rather than
blank. The symbol is written as a `\u{...}` escape so the file cannot be
broken by an editor that saves it in the wrong encoding.

**Palette** — Shopee orange on Keeby's neutrals:

| token | value | on white | used for |
|---|---|---|---|
| `--paper` | `#f5f5f5` | — | page background |
| `--ink` | `#171717` | 17.9:1 | headings |
| `--ink-2` | `#444444` | 9.7:1 | body text, meta |
| `--brand` | `#df280c` | 4.7:1 | buttons, focus rings |
| `--brand-dark` | `#cb240b` | 5.5:1 | button hover |
| `--brand-deep` | `#a81f08` | 7.6:1 | small orange text, links |
| `--brand-vivid` | `#f4511e` | 3.4:1 | prices (20px bold = large text) |
| `--accent` | `#ff8c17` | 2.3:1 | warm glow and shimmer — **never text** |

**Why the orange is not exactly Shopee's.** Shopee's brand colour is
`#ee4d2d`, but white text on it is only **3.66:1**, which fails WCAG AA for
normal-size text. `#df280c` is the *same hue* at lower lightness and reaches
4.72:1, so the buttons still read as Shopee orange but are readable. The
brighter `#f4511e` is kept for prices, which are 20px bold and therefore
count as large text where the bar is 3:1.

The neutrals, hairlines and every animation timing come from Keeby's
compiled stylesheet.

**Icons** — [Lucide](https://lucide.dev) 1.51.0, embedded as inline SVG in
`includes/icons.php` under the **ISC licence** (free for any use, including
commercial; the notice lives at the top of that file). No icon font, no CDN,
no extra HTTP request. Because every Lucide shape is a *stroke* rather than
a fill, the icons can be animated with CSS alone:

```php
<?= icon('shopping-bag', ['size' => 17]) ?>          // beside a text label
<?= icon('cake', ['title' => 'Date of birth']) ?>    // as the only label
```

`icon()` adds `pathLength="1"` to every shape, which normalises them all to
length 1 — that is what lets one CSS rule draw any icon with
`stroke-dashoffset`, instead of measuring each path. Icons default to
`aria-hidden` so they are silent next to a label; pass `title` when the icon
*is* the label.

**Motion** — the keyframes and timings are Keeby's, including
`cubic-bezier(0.4, 0, 0.2, 1)` and a 150ms tap:

| from Keeby | used here for |
|---|---|
| `fadeUp` (.7s, staggered 80ms) | the page-load entrance |
| `iosBlurIn` (.55s, `cubic-bezier(.22,1,.36,1)`) | the toast |
| `keebyShimmer` (4s linear) | the price on the item page |
| `keycapPop` (.3s, `cubic-bezier(.34,1.56,.64,1)`) | the quantity stepper |
| `rippleOut` | button press |
| `keebyLoaderPulse` | the "no photo" placeholder |
| `pulse` (2s) | the low-stock dot |

Every one of these answers a user action or marks arrival. There are no
entrance animations on scroll, and `prefers-reduced-motion` disables all of
them — including the shimmer, which falls back to solid `--brand` text so the
price never sits there invisible.

**Type** — two families with separate jobs. **Nunito** (rounded) for display
text and every price; **Inter** for the interface. Loaded from Google Fonts
with `display=swap`, and both fall back to the system rounded / system sans
stack, so the layout still reads correctly on school wifi with no internet.

**Radii are graded by size**, not one value everywhere: `6px` chips, `12px`
inputs, `16px` photos, `24px` the sheet, `999px` pills.

**The grid is cut from one clipped sheet** with a 2px white gap between
tiles, rather than being separate floating cards with drop shadows. The gap
uses the sheet's own colour on purpose — a hairline-coloured gap shows up as
a grey block wherever the final row is short, because there are no tiles to
cover the empty cells.

**Motion answers the user, it does not decorate.** The only transitions are
the press-scale on a button or photo and the toast arriving. There are no
fade-and-slide-up entrances on every section. `prefers-reduced-motion` is
respected.

**The whole site works with JavaScript off.** `assets/app.js` only adds the
quantity stepper, auto-submitting the filter form, the `/` shortcut and the
toast timer. Every purchase and every search still works without it.

---

## Search and filters

`includes/search.php` holds all the SQL. The home page has a search box;
`browse.php` adds the full filter panel.

| Filter | Field | Notes |
|---|---|---|
| Keyword | `q` | `LIKE %term%` on title **and** description |
| Min / max price | `min`, `max` | `0` means "not set", so the filter is skipped |
| Seller | `seller` | matches first name |
| Sort | `sort` | newest, cheapest, price high→low, name A→Z |

Everything is a `GET` form, so a search is a bookmarkable URL and the Back
button behaves.

**The injection defence worth explaining.** User input only ever reaches the
database through `?` placeholders, including the `%` wildcards — those are
bound as part of the *value*, so they cannot be read as SQL. The only things
interpolated into the statement are column and direction names, and those
come from a fixed list in `search.php`:

```php
function search_order_by(string $sort): string
{
    return match ($sort) {
        'cheapest'   => 'items.price ASC, items.id DESC',
        'price_desc' => 'items.price DESC, items.id ASC',
        'title'      => 'items.title ASC, items.id ASC',
        default      => 'items.created_at DESC, items.id DESC',
    };
}
```

An unknown `sort` falls through to `default` instead of reaching the
database. Try `?sort=price; DROP TABLE items` — it silently sorts by newest
and the table is still there.

---

## How stock works

One seller with 50 chairs should post **one** advert, not fifty. So
`items.stock` holds how many are available, and the home page only shows
`WHERE stock > 0` — sold-out items disappear on their own.

Buying is one statement:

```sql
UPDATE items SET stock = stock - :take
 WHERE id = :id AND stock >= :need
```

The check and the subtraction happen together, so two people clicking Buy at
the same instant cannot both get the last item. The loser gets
`rowCount() === 0` and sees "sorry, only N left".

> Gotcha that cost an hour once: `:quantity` cannot be **reused** twice in the
> SQL when `PDO::ATTR_EMULATE_PREPARES` is `false`. MySQL throws
> `SQLSTATE[HY093] Invalid parameter number`. Hence `:take` and `:need`.

---

## The five things to know for your viva

1. **`e()` on every echo.** Printing user input raw lets anyone run
   JavaScript in your visitors' browsers (XSS).
2. **Prepared statements everywhere.** `?` and `:name` placeholders instead of
   gluing variables into SQL (SQL injection).
3. **`password_hash()` / `password_verify()`.** Passwords are stored as
   hashes. Nobody, including you, can read them back.
4. **Ownership checks.** `WHERE user_id = ?` built from the session means you
   cannot edit someone else's item by editing the URL.
5. **CSRF tokens.** A hidden random value in every form stops other websites
   from submitting forms as your logged-in users.

### One subtlety worth demonstrating

`redirect()` in `includes/helpers.php` calls `base_url()`, which works out
the site prefix from `$_SERVER['SCRIPT_NAME']`:

- `http://localhost/marketplace/` → prefix `/marketplace`
- `http://marketplace.test/` → prefix `""`

That is why the same code works in both places without hard-coding anything,
and why a redirect out of `process/` still lands on a root-level page.

---

## If you want the `marketplace.test` URL

Laragon gives each project a domain like `http://project.test` by writing a
hosts-file entry (`#laragon magic!`) plus an Apache vhost. It does that when
it scans `C:\laragon\www`, which happened before this folder was renamed.

In Laragon: **Menu → Stop All → Start All**, then open
`http://marketplace.test/`.

Until then, `http://localhost/marketplace/` works and is all you need.

---

## Common problems

| Problem | Cause |
|---|---|
| "Database error: ... unknown database" | `database.sql` was not imported. |
| Blank white page | Set `'debug' => true` in `config.php`. |
| Login works then instantly logs out | Cookies blocked, or the session folder is not writable. |
| Photo upload fails | `uploads/.htaccess` needs `AllowOverride All`. |
| "403 Request rejected" | A CSRF token was missing or forged. Reload the page. |
| Redirect goes to `/marketplace/marketplace/...` | Something hard-coded a path. Use `redirect('page.php')`, never a leading `/`. |
