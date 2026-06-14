<?php
// Products page
$pageTitle = 'Products – Arun Ice Creams';
require_once 'includes/header.php';

// Valid categories for filter buttons
$categories = ['Cones', 'Cups', 'Bars', 'Sundaes', 'Popsicles', 'Sandwiches'];

// Read initial category from URL param (case-insensitive match)
$initialCategory = '';
if (isset($_GET['category'])) {
    $requested = trim($_GET['category']);
    foreach ($categories as $cat) {
        if (strcasecmp($requested, $cat) === 0) {
            $initialCategory = $cat;
            break;
        }
    }
}
?>

<section class="page-hero">
    <div class="container">
        <h1>Our Products</h1>
        <p>30+ handcrafted flavours — cones, cups, bars, sundaes &amp; more</p>
    </div>
</section>

<section class="section" style="padding-top: 2rem;">
    <div class="container">

        <div class="search-filter-bar">
            <!-- Search bar -->
            <div class="search-wrap">
                <input
                    type="search"
                    id="search-bar"
                    class="search-input"
                    placeholder="Search ice creams…"
                >
            </div>

            <!-- Category filter buttons -->
            <div class="filter-buttons">
                <button
                    class="filter-btn <?= $initialCategory === '' ? 'active' : '' ?>"
                    data-category=""
                >
                    All
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button
                        class="filter-btn <?= $initialCategory === $cat ? 'active' : '' ?>"
                        data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid"
             id="product-grid"
             data-initial-category="<?= htmlspecialchars($initialCategory, ENT_QUOTES, 'UTF-8') ?>">
            <p class="text-center" style="grid-column:1/-1; padding:2rem; color:var(--color-muted)">
                Loading products…
            </p>
        </div>

        <!-- Empty state (hidden by default) -->
        <div id="empty-state" class="empty-state" hidden>
            <h2 class="empty-title">No products found</h2>
            <p>Try adjusting your search or category filter.</p>
        </div>

    </div>
</section>

<!-- Page-specific JS -->
<script src="assets/js/products.js"></script>

<?php require_once 'includes/footer.php'; ?>
