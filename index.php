<?php
$pageTitle = 'Arun Ice Creams – Handcrafted Happiness';
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Arun Ice Creams</h1>
        <p class="hero-tagline">
            Handcrafted happiness, one scoop at a time.<br>
            Over 30 flavours — cones, cups, bars, sundaes, popsicles &amp; sandwiches.
        </p>
        <a href="products.php" class="btn-shop-now">Shop Now</a>
    </div>
</section>

<section class="section" id="featured">
    <div class="container">
        <h2 class="section-title">Featured Flavours</h2>
        <p class="section-subtitle">Our most-loved picks from the freezer</p>
        <div class="product-grid" id="featured-grid">
            <p class="text-center" style="grid-column:1/-1;color:var(--muted)">Loading featured products…</p>
        </div>
        <div class="text-center" style="margin-top:1.5rem">
            <a href="products.php" class="btn-outline">View All Products</a>
        </div>
    </div>
</section>

<section class="section" style="background:var(--card);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
    <div class="container">
        <h2 class="section-title">Browse by Category</h2>
        <p class="section-subtitle">Cones, cups, bars and more — pick your favourite</p>
        <div class="category-grid">
            <a href="products.php?category=Cones" class="category-tile category-tile--cones">Cones</a>
            <a href="products.php?category=Cups" class="category-tile category-tile--cups">Cups</a>
            <a href="products.php?category=Bars" class="category-tile category-tile--bars">Bars</a>
            <a href="products.php?category=Sundaes" class="category-tile category-tile--sundaes">Sundaes</a>
            <a href="products.php?category=Popsicles" class="category-tile category-tile--popsicles">Popsicles</a>
            <a href="products.php?category=Sandwiches" class="category-tile category-tile--sandwiches">Sandwiches</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Why Arun Ice Creams?</h2>
        <p class="section-subtitle">We've been scooping smiles since 1995</p>
        <div class="features-grid">
            <div class="feature-card">
                <h3>100% Natural Milk</h3>
                <p>Made with fresh full-cream milk from local farms every morning.</p>
            </div>
            <div class="feature-card">
                <h3>No Artificial Colours</h3>
                <p>Colours come from real fruits, spices and natural extracts.</p>
            </div>
            <div class="feature-card">
                <h3>Same-Day Delivery</h3>
                <p>Order before 6 PM for evening delivery across Chennai.</p>
            </div>
            <div class="feature-card">
                <h3>Made with Love</h3>
                <p>Small batches, handcrafted daily for the best quality.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-section">
            <h2>About Arun Ice Creams</h2>
            <p>
                Founded in Chennai in 1995, Arun Ice Creams has brought joy to families across Tamil Nadu
                for nearly three decades. From a small neighbourhood parlour to 30+ handcrafted flavours —
                our recipe for happiness hasn't changed.
            </p>
            <p>
                Try classics like Vanilla Cone and Kesar Pista Cup, or adventurous picks like
                Mango Chilli Popsicle. Every scoop uses natural milk, real fruits and recipes
                passed down through generations.
            </p>
            <a href="products.php" class="btn-primary" style="width:auto;display:inline-block;margin-top:0.5rem;">
                Explore Our Menu
            </a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', async function () {
    const grid = document.getElementById('featured-grid');
    if (!grid) return;

    const data = await apiFetch('api/products.php?featured=1');
    if (!data || !Array.isArray(data) || data.length === 0) {
        grid.innerHTML = '<p class="text-center" style="grid-column:1/-1;color:var(--muted)">No featured products available.</p>';
        return;
    }

    grid.innerHTML = data.slice(0, 6).map(buildProductCard).join('');
    grid.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', async function () {
            const productId = this.dataset.productId;
            this.disabled = true;
            const orig = this.textContent;
            this.textContent = 'Adding...';

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            const res = await apiFetch('api/cart.php', { method: 'POST', body: formData });

            this.disabled = false;
            if (res && res.success) {
                updateCartBadge(res.cart_count);
                showToast(res.message || 'Added to cart!', 'success');
                this.textContent = 'Added!';
                setTimeout(() => { this.textContent = orig; }, 1200);
            } else {
                this.textContent = orig;
                if (res) showToast(res.error || 'Could not add item.', 'error');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
