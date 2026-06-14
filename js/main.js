// main.js - Shared utilities for Arun Ice Creams

'use strict';

// Update cart badge count in the nav
function updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;

    const n = parseInt(count, 10) || 0;
    badge.textContent = n;

    if (n === 0) {
        badge.classList.add('hidden');
    } else {
        badge.classList.remove('hidden');
    }

    // Update aria-label on the cart nav link for accessibility
    const cartLink = badge.closest('a');
    if (cartLink) {
        cartLink.setAttribute(
            'aria-label',
            `Shopping cart, ${n} item${n !== 1 ? 's' : ''}`
        );
    }
}

// Show a toast notification
function showToast(message, type = 'info', duration = 2500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    toast.setAttribute('role', 'alert');

    container.appendChild(toast);

    // Auto-dismiss
    setTimeout(() => {
        toast.classList.add('fade-out');
        // Remove from DOM after fade transition completes
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 350);
    }, duration);
}

// Format a price as Indian Rupees
function formatPrice(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

// Debounce helper
function debounce(fn, delay) {
    let timer = null;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Fetch wrapper with error handling
async function apiFetch(url, options = {}) {
    try {
        const response = await fetch(url, options);
        let data;
        try {
            data = await response.json();
        } catch (parseErr) {
            console.error('apiFetch JSON parse error:', parseErr);
            showToast('Something went wrong. Please try again.', 'error');
            return null;
        }

        return data;
    } catch (err) {
        console.error('apiFetch error:', err);
        showToast('Something went wrong. Please try again.', 'error');
        return null;
    }
}

// Returns the CSS class for a category badge
function categoryBadgeClass(category) {
    const map = {
        'Cones':      'badge-cones',
        'Cups':       'badge-cups',
        'Bars':       'badge-bars',
        'Sundaes':    'badge-sundaes',
        'Popsicles':  'badge-popsicles',
        'Sandwiches': 'badge-sandwiches',
    };
    return map[category] || '';
}

// Build a product card HTML string
function buildProductCard(product) {
    const desc = product.description.length > 100
        ? product.description.substring(0, 100) + '…'
        : product.description;

    const badgeClass = categoryBadgeClass(product.category);
    const imgSrc = product.image_filename.startsWith('http')
        ? product.image_filename
        : `assets/images/products/${product.image_filename}`;

    return `
        <article class="product-card" data-product-id="${product.id}">
            <div class="card-image-wrap">
                <img
                    src="${imgSrc}"
                    alt="${escapeHtml(product.name)}"
                    width="400"
                    height="300"
                    loading="lazy"
                    onerror="this.src='assets/images/placeholder.svg'; this.onerror=null;"
                >
            </div>
            <div class="card-body">
                <span class="category-badge ${badgeClass}">${escapeHtml(product.category)}</span>
                <h3 class="card-title">${escapeHtml(product.name)}</h3>
                <p class="card-desc">${escapeHtml(desc)}</p>
                <p class="card-price">${formatPrice(product.price)}</p>
                <button
                    class="btn-add-cart"
                    data-product-id="${product.id}"
                    data-product-name="${escapeHtml(product.name)}"
                    aria-label="Add ${escapeHtml(product.name)} to cart"
                >
                    Add to Cart
                </button>
            </div>
        </article>
    `;
}

// Escape HTML special characters
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

// Sync badge on page load
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        const count = parseInt(badge.textContent, 10) || 0;
        updateCartBadge(count);
    }
});
