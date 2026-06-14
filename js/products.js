// products.js - Product catalogue page logic

'use strict';

(function () {

    // State
    let allProducts      = [];   // full product list from API
    let activeCategory   = '';   // currently selected category filter
    let searchQuery      = '';   // current search string

    // DOM refs
    const grid       = document.getElementById('product-grid');
    const emptyState = document.getElementById('empty-state');
    const searchBar  = document.getElementById('search-bar');
    const filterBtns = document.querySelectorAll('.filter-btn');

    // Render products into the grid
    function renderProducts(products) {
        if (!grid) return;

        if (products.length === 0) {
            grid.innerHTML = '';
            if (emptyState) emptyState.removeAttribute('hidden');
            return;
        }

        if (emptyState) emptyState.setAttribute('hidden', '');
        grid.innerHTML = products.map(buildProductCard).join('');

        // Attach add-to-cart listeners to newly rendered buttons
        grid.querySelectorAll('.btn-add-cart').forEach(btn => {
            btn.addEventListener('click', handleAddToCart);
        });
    }

    // ── Filter products based on active category + search query ──────────────
    function applyFilters() {
        let filtered = allProducts;

        if (activeCategory) {
            filtered = filtered.filter(p =>
                p.category.toLowerCase() === activeCategory.toLowerCase()
            );
        }

        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            filtered = filtered.filter(p =>
                p.name.toLowerCase().includes(q) ||
                p.description.toLowerCase().includes(q)
            );
        }

        renderProducts(filtered);
    }

    // ── Fetch all products from API ───────────────────────────────────────────
    async function loadProducts() {
        if (!grid) return;

        grid.innerHTML = '<p class="text-center" style="padding:2rem;color:var(--color-muted)">Loading products…</p>';

        const data = await apiFetch('api/products.php');

        if (!data || !Array.isArray(data)) {
            grid.innerHTML = '<p class="text-center" style="padding:2rem;color:#E74C3C">Failed to load products. Please refresh the page.</p>';
            return;
        }

        allProducts = data;
        applyFilters();
    }

    // ── Handle "Add to Cart" button click ────────────────────────────────────
    async function handleAddToCart(e) {
        const btn       = e.currentTarget;
        const productId = btn.dataset.productId;

        // Disable button during request to prevent double-clicks
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Adding...';

        const formData = new FormData();
        formData.append('action',     'add');
        formData.append('product_id', productId);

        const data = await apiFetch('api/cart.php', {
            method: 'POST',
            body:   formData,
        });

        btn.disabled = false;
        btn.textContent = originalText;

        if (data && data.success) {
            updateCartBadge(data.cart_count);
            showToast(data.message || 'Added to cart!', 'success');

            // Brief visual feedback on the button
            btn.textContent = 'Added';
            btn.style.background = 'var(--color-secondary)';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
            }, 1200);
        } else if (data) {
            showToast(data.error || 'Could not add item to cart.', 'error');
        }
    }

    // ── Category filter button clicks ─────────────────────────────────────────
    function initCategoryFilters() {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active state
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                activeCategory = btn.dataset.category || '';
                applyFilters();
            });
        });
    }

    // ── Search bar (debounced) ────────────────────────────────────────────────
    function initSearch() {
        if (!searchBar) return;

        const debouncedSearch = debounce(() => {
            searchQuery = searchBar.value.trim();
            applyFilters();
        }, 300);

        searchBar.addEventListener('input', debouncedSearch);

        // Clear search on Escape key
        searchBar.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                searchBar.value = '';
                searchQuery = '';
                applyFilters();
            }
        });
    }

    // ── Pre-apply category from URL param (set by PHP as data attribute) ──────
    function applyInitialCategory() {
        if (!grid) return;

        const initialCategory = grid.dataset.initialCategory || '';
        if (!initialCategory) return;

        activeCategory = initialCategory;

        // Activate the matching filter button
        filterBtns.forEach(btn => {
            btn.classList.remove('active');
            if ((btn.dataset.category || '') === initialCategory) {
                btn.classList.add('active');
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        applyInitialCategory();
        initCategoryFilters();
        initSearch();
        loadProducts();
    });

})();
