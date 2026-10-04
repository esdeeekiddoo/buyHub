<?php
/**
 * includes/search.php
 * ---------------------------------------------------------------------
 * Builds the SQL for browsing and searching items, and reads the filter
 * values out of $_GET.
 *
 * Everything here is built with placeholders and bound parameters. The
 * only strings ever concatenated into SQL are column and direction names,
 * and those come from a fixed list in this file - never from the query
 * string. That is what stops "?sort=DROP TABLE items" doing any damage.
 */

require_once __DIR__ . '/db.php';

/** The filter values currently in the URL, tidied up. */
function search_filters(): array
{
    return [
        'q'      => trim((string) ($_GET['q'] ?? '')),
        'sort'   => (string) ($_GET['sort'] ?? 'newest'),
        'min'    => max(0, (int) ($_GET['min'] ?? 0)),
        'max'    => max(0, (int) ($_GET['max'] ?? 0)),
        'seller' => trim((string) ($_GET['seller'] ?? '')),
    ];
}

/** The sort options offered, in the order they are shown. */
function search_sort_options(): array
{
    return [
        'newest' => 'Newest first',
        'cheapest' => 'Cheapest first',
        'price_desc' => 'Price: high to low',
        'title'  => 'Name: A to Z',
    ];
}

/**
 * Builds the WHERE clause and the bound values for the current filters.
 *
 * @return array{0:string, 1:array} [$whereSql, $params]
 */
function search_where(array $f): array
{
    $where  = ['items.stock > 0'];   // sold out items never show up
    $params = [];

    // --- keyword ---
    // LIKE with % around the term does a substring match, so "phone"
    // finds "iPhone". The % signs are part of the VALUE, so they are
    // bound as a parameter - that keeps them from being read as SQL.
    if ($f['q'] !== '') {
        $where[] = '(items.title LIKE ? OR items.description LIKE ?)';
        $like    = '%' . $f['q'] . '%';
        $params[] = $like;
        $params[] = $like;
    }

    // --- price range ---
    // A max of 0 means "not set", otherwise every item would vanish.
    if ($f['min'] > 0) {
        $where[]  = 'items.price >= ?';
        $params[] = $f['min'];
    }

    if ($f['max'] > 0) {
        $where[]  = 'items.price <= ?';
        $params[] = $f['max'];
    }

    // --- seller ---
    // matched on first name, so people can find one person's listings
    if ($f['seller'] !== '') {
        $where[]  = 'users.first_name LIKE ?';
        $params[] = '%' . $f['seller'] . '%';
    }

    return [implode(' AND ', $where), $params];
}

/**
 * Turns the sort key into a safe ORDER BY.
 *
 * The key is looked up in a list, so an unknown value falls back to the
 * default instead of reaching the database.
 */
function search_order_by(string $sort): string
{
    return match ($sort) {
        'cheapest'  => 'items.price ASC, items.id DESC',
        'price_desc'=> 'items.price DESC, items.id ASC',
        'title'     => 'items.title ASC, items.id ASC',
        default     => 'items.created_at DESC, items.id DESC',
    };
}

/**
 * Runs the search and returns the rows plus the total count.
 *
 * @return array{items:array, total:int}
 */
function search_items(array $f, int $limit = 24, int $offset = 0): array
{
    [$where, $params] = search_where($f);

    // $where and the ORDER BY come from search_where()/search_order_by(),
    // never straight from $_GET, so interpolating them is safe here.
    $sql = 'SELECT items.*, users.first_name, users.last_name, users.city
              FROM items
              JOIN users ON users.id = items.user_id
             WHERE ' . $where . '
          ORDER BY ' . search_order_by($f['sort']) . '
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // A second query for the count, so the total is known before paging.
    $countSql = 'SELECT COUNT(*)
                   FROM items
                   JOIN users ON users.id = items.user_id
                  WHERE ' . $where;

    $countStmt = db()->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    return ['items' => $items, 'total' => $total];
}

/**
 * The current filters as a query string, for building "next page" and
 * "clear" links without losing the other filters by hand.
 *
 * @param array $overrides values to change, e.g. ['page' => 2]
 */
function search_query(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);

    // Drop anything empty so the URL stays readable: ?q=&sort=newest
    // is noise when the defaults would do exactly the same thing.
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);

    unset($params['page']);            // page is rebuilt by the caller

    return $params === [] ? '' : '?' . http_build_query($params);
}

/** True when the visitor has narrowed anything down at all. */
function search_has_filters(array $f): bool
{
    return $f['q'] !== '' || $f['min'] > 0 || $f['max'] > 0 || $f['seller'] !== '';
}

/** How many items are in the whole catalogue, ignoring filters. */
function catalogue_count(): int
{
    $stmt = db()->query('SELECT COUNT(*) FROM items WHERE stock > 0');
    return (int) $stmt->fetchColumn();
}

/**
 * The lowest and highest price in the catalogue, for the filter inputs.
 *
 * @return array{0:float, 1:float} [lowest, highest]
 */
function price_bounds(): array
{
    $row = db()->query(
        'SELECT MIN(price) AS low, MAX(price) AS high FROM items WHERE stock > 0'
    )->fetch();

    return [
        (float) ($row['low'] ?? 0),
        (float) ($row['high'] ?? 0),
    ];
}
