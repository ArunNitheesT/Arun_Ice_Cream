<?php
/**
 * Database initialisation script.
 * Creates schema and seeds product data if not already present.
 * Safe to call multiple times (idempotent).
 */
require_once __DIR__ . '/../includes/db.php';

function migrateOrdersTable(PDO $pdo): void {
    $cols = $pdo->query('PRAGMA table_info(orders)')->fetchAll(PDO::FETCH_ASSOC);
    $names = array_column($cols, 'name');

    if (!in_array('status', $names, true)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN status TEXT NOT NULL DEFAULT 'pending'");
    }
    if (!in_array('user_id', $names, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN user_id INTEGER');
    }
}

function seedUsers(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $users = [
        ['Admin', 'admin@arunicecreams.in', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
        ['Demo Customer', 'customer@example.com', password_hash('customer123', PASSWORD_DEFAULT), 'customer'],
    ];

    $stmt = $pdo->prepare('
        INSERT INTO users (name, email, password_hash, role, created_at)
        VALUES (:name, :email, :password_hash, :role, :created_at)
    ');

    foreach ($users as $u) {
        $stmt->execute([
            ':name'          => $u[0],
            ':email'         => $u[1],
            ':password_hash' => $u[2],
            ':role'          => $u[3],
            ':created_at'    => date('c'),
        ]);
    }
}

function initDB(): void {
    $pdo = getDB();

    // Create products table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            name           TEXT    NOT NULL,
            category       TEXT    NOT NULL,
            description    TEXT    NOT NULL,
            price          REAL    NOT NULL CHECK(price > 0),
            image_filename TEXT    NOT NULL,
            is_featured    INTEGER NOT NULL DEFAULT 0
        )
    ");

    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            email         TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            role          TEXT    NOT NULL CHECK(role IN ('admin', 'customer')),
            created_at    TEXT    NOT NULL
        )
    ");

    // Create orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name    TEXT    NOT NULL,
            customer_email   TEXT    NOT NULL,
            customer_phone   TEXT    NOT NULL,
            delivery_address TEXT    NOT NULL,
            payment_method   TEXT    NOT NULL,
            items_json       TEXT    NOT NULL,
            total_amount     REAL    NOT NULL CHECK(total_amount > 0),
            created_at       TEXT    NOT NULL,
            status           TEXT    NOT NULL DEFAULT 'pending',
            user_id          INTEGER
        )
    ");

    migrateOrdersTable($pdo);
    seedUsers($pdo);

    // Guard: only seed products if table is empty
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $products = [
        ['Classic Vanilla Cone',        'Cones',      'A timeless scoop of creamy vanilla ice cream nestled in a crispy golden waffle cone. Pure perfection in every lick.',                45.00, 'vanilla-cone.jpg',          1],
        ['Chocolate Fudge Cone',        'Cones',      'Rich dark chocolate ice cream swirled with hot fudge ribbons, served in a chocolate-dipped waffle cone.',                            55.00, 'choco-fudge-cone.jpg',      1],
        ['Strawberry Dream Cone',       'Cones',      'Fresh strawberry ice cream bursting with real fruit pieces, topped with a strawberry drizzle in a sugar cone.',                     50.00, 'strawberry-cone.jpg',       0],
        ['Butterscotch Crunch Cone',    'Cones',      'Smooth butterscotch ice cream loaded with caramel crunch bits, served in a toasted waffle cone.',                                   52.00, 'butterscotch-cone.jpg',     0],
        ['Rainbow Sprinkle Cone',       'Cones',      'Vanilla and strawberry swirl ice cream generously coated with rainbow sprinkles in a classic sugar cone. Kids love it!',            48.00, 'rainbow-cone.jpg',          0],
        ['Mint Choco Chip Cone',        'Cones',      'Cool and refreshing mint ice cream studded with dark chocolate chips, served in a crispy waffle cone.',                             53.00, 'mint-choco-cone.jpg',       0],
        ['Mango Tango Cup',             'Cups',       'Luscious Alphonso mango ice cream with real mango pulp swirls. A tropical delight served in a generous cup.',                       50.00, 'mango-cup.jpg',             1],
        ['Choco Brownie Cup',           'Cups',       'Velvety chocolate ice cream layered with chunks of warm fudge brownie and a drizzle of chocolate sauce.',                           65.00, 'choco-brownie-cup.jpg',     0],
        ['Kesar Pista Cup',             'Cups',       'Royal saffron-infused ice cream topped with crushed pistachios and silver varq. A regal Indian classic.',                           70.00, 'kesar-pista-cup.jpg',       1],
        ['Tender Coconut Cup',          'Cups',       'Delicate coconut milk ice cream with tender coconut pieces and a hint of cardamom. Refreshingly light.',                            55.00, 'coconut-cup.jpg',           0],
        ['Blueberry Cheesecake Cup',    'Cups',       'Creamy cheesecake-flavoured ice cream swirled with blueberry compote and graham cracker crumbles.',                                 68.00, 'blueberry-cup.jpg',         0],
        ['Rose Gulkand Cup',            'Cups',       'Fragrant rose ice cream with gulkand (rose petal preserve) swirls and dried rose petals. A floral indulgence.',                    60.00, 'rose-cup.jpg',              0],
        ['Choco Almond Bar',            'Bars',       'Creamy vanilla ice cream coated in a thick layer of dark chocolate and roasted almond slivers. A classic bar treat.',              40.00, 'choco-almond-bar.jpg',      1],
        ['Mango Kulfi Bar',             'Bars',       'Traditional dense mango kulfi on a stick, made with reduced milk and real Alphonso mango. Authentically Indian.',                   35.00, 'mango-kulfi-bar.jpg',       0],
        ['Strawberry Cream Bar',        'Bars',       'Strawberry ice cream bar with a white chocolate coating and freeze-dried strawberry pieces on the outside.',                        42.00, 'strawberry-bar.jpg',        0],
        ['Dark Chocolate Truffle Bar',  'Bars',       'Intense dark chocolate ice cream enrobed in 70% cocoa chocolate. For the serious chocolate lover.',                                 48.00, 'dark-choco-bar.jpg',        0],
        ['Caramel Crunch Bar',          'Bars',       'Salted caramel ice cream bar coated in milk chocolate and crispy rice puffs. Sweet, salty, and crunchy.',                          45.00, 'caramel-bar.jpg',           0],
        ['Paan Ice Cream Bar',          'Bars',       'Unique betel leaf-flavoured ice cream with gulkand filling, coated in white chocolate. A desi twist on a classic bar.',            38.00, 'paan-bar.jpg',              0],
        ['Hot Fudge Sundae',            'Sundaes',    'Three scoops of vanilla ice cream smothered in warm hot fudge sauce, whipped cream, and a maraschino cherry on top.',              95.00, 'hot-fudge-sundae.jpg',      1],
        ['Banana Split Sundae',         'Sundaes',    'A classic banana split with strawberry, chocolate, and vanilla scoops, topped with three sauces, nuts, and whipped cream.',       120.00, 'banana-split.jpg',          0],
        ['Brownie Blast Sundae',        'Sundaes',    'Warm chocolate brownie topped with two scoops of vanilla ice cream, hot fudge, caramel drizzle, and crushed walnuts.',            110.00, 'brownie-sundae.jpg',        0],
        ['Mango Fiesta Sundae',         'Sundaes',    'Mango ice cream with fresh mango chunks, mango coulis, coconut flakes, and a sprinkle of chaat masala for a tangy kick.',          90.00, 'mango-sundae.jpg',          0],
        ['Oreo Crumble Sundae',         'Sundaes',    'Cookies and cream ice cream loaded with crushed Oreos, chocolate sauce, whipped cream, and a whole Oreo on top.',                 100.00, 'oreo-sundae.jpg',           0],
        ['Watermelon Popsicle',         'Popsicles',  'Refreshing watermelon juice popsicle with real watermelon pieces and a hint of lime. Perfect for hot summer days.',                25.00, 'watermelon-pop.jpg',        0],
        ['Mango Chilli Popsicle',       'Popsicles',  'Sweet Alphonso mango popsicle with a surprising chilli-lime kick. A bold and adventurous flavour combination.',                    28.00, 'mango-chilli-pop.jpg',      0],
        ['Lychee Rose Popsicle',        'Popsicles',  'Delicate lychee and rose water popsicle with real lychee pieces. Light, floral, and utterly refreshing.',                          30.00, 'lychee-pop.jpg',            0],
        ['Kiwi Mint Popsicle',          'Popsicles',  'Tangy kiwi popsicle swirled with fresh mint. Bright green, vibrant, and packed with vitamin C.',                                   28.00, 'kiwi-pop.jpg',              0],
        ['Chocolate Fudge Popsicle',    'Popsicles',  'Rich chocolate fudge popsicle made with real cocoa and a touch of sea salt. Intensely chocolatey and satisfying.',                 32.00, 'choco-pop.jpg',             0],
        ['Classic Vanilla Sandwich',    'Sandwiches', 'Creamy vanilla ice cream pressed between two soft chocolate wafer cookies. A timeless after-school treat.',                         35.00, 'vanilla-sandwich.jpg',      0],
        ['Choco Mint Sandwich',         'Sandwiches', 'Cool mint chocolate chip ice cream sandwiched between two dark cocoa cookies. Refreshing and indulgent.',                           40.00, 'mint-sandwich.jpg',         0],
        ['Strawberry Wafer Sandwich',   'Sandwiches', 'Strawberry ice cream with real fruit pieces between two crispy vanilla wafers. Light and fruity.',                                  38.00, 'strawberry-sandwich.jpg',   0],
        ['Butterscotch Cookie Sandwich','Sandwiches', 'Rich butterscotch ice cream with caramel swirls between two oatmeal cookies. Warm, cosy flavours.',                                42.00, 'butterscotch-sandwich.jpg', 0],
        ['Mango Coconut Sandwich',      'Sandwiches', 'Tropical mango ice cream with coconut flakes sandwiched between two coconut-flavoured wafers.',                                     40.00, 'mango-sandwich.jpg',        0],
        ['Double Choco Sandwich',       'Sandwiches', 'Intense chocolate ice cream between two thick chocolate brownie cookies. For the ultimate chocolate lover.',                         45.00, 'double-choco-sandwich.jpg', 0],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO products (name, category, description, price, image_filename, is_featured)
        VALUES (:name, :category, :description, :price, :image_filename, :is_featured)
    ");

    foreach ($products as $p) {
        $stmt->execute([
            ':name'           => $p[0],
            ':category'       => $p[1],
            ':description'    => $p[2],
            ':price'          => $p[3],
            ':image_filename' => $p[4],
            ':is_featured'    => $p[5],
        ]);
    }
}

// Auto-run when this file is included
initDB();
