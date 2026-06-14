// cart.js - Shopping cart page logic

'use strict';

(function () {

    // ── DOM references ───────────────────────────────────────────────────────
    const cartBody    = document.getElementById('cart-body');
    const cartContent = document.getElementById('cart-content');
    const cartEmpty   = document.getElementById('cart-empty');
    const cartTable   = document.getElementById('cart-table');
    const subtotalEl  = document.getElementById('cart-subtotal');
    const clearBtn    = document.getElementById('btn-clear-cart');
    const checkoutBtn = document.getElementById('btn-checkout');

    // ── In-memory cart state (product_id → item) ─────────────────────────────
    let cartItems = {};

    // ── Render the full cart table ────────────────────────────────────────────
    function renderCart() {
        if (!cartBody) return;

        const items = Object.values(cartItems);

        if (items.length === 0) {
            showEmptyState();
            return;
        }

        hideEmptyState();

        cartBody.innerHTML = items.map(item => `
            <tr data-product-id="${item.product_id}">
                <td>
                    <img
                        class="cart-item-img"
                        src="${item.image_filename.startsWith('http') ? item.image_filename : 'assets/images/products/' + escapeHtml(item.image_filename)}"
                        alt="${escapeHtml(item.name)}"
                        width="60" height="60"
                        onerror="this.src='assets/images/placeholder.svg'; this.onerror=null;"
                    >
                </td>
                <td>
                    <strong>${escapeHtml(item.name)}</strong><br>
                    <small style="color:var(--color-muted)">${escapeHtml(item.category)}</small>
                </td>
                <td class="hide-mobile">${formatPrice(item.price)}</td>
                <td>
                    <div class="qty-controls">
                        <button class="qty-btn btn-decrease"
                                data-product-id="${item.product_id}"
                                aria-label="Decrease quantity of ${escapeHtml(item.name)}">−</button>
                        <span class="qty-display" id="qty-${item.product_id}">${item.quantity}</span>
                        <button class="qty-btn btn-increase"
                                data-product-id="${item.product_id}"
                                aria-label="Increase quantity of ${escapeHtml(item.name)}">+</button>
                    </div>
                </td>
                <td id="line-${item.product_id}">${formatPrice(item.line_total)}</td>
                <td>
                    <button class="btn-remove"
                            data-product-id="${item.product_id}"
                            aria-label="Remove ${escapeHtml(item.name)} from cart"
                            title="Remove item">Remove</button>
                </td>
            </tr>
        `).join('');

        updateSubtotal();
        attachRowListeners();
    }

    // ── Attach event listeners to cart row buttons ────────────────────────────
    function attachRowListeners() {
        if (!cartBody) return;

        cartBody.querySelectorAll('.btn-decrease').forEach(btn => {
            btn.addEventListener('click', () => handleDecrease(parseInt(btn.dataset.productId, 10)));
        });

        cartBody.querySelectorAll('.btn-increase').forEach(btn => {
            btn.addEventListener('click', () => handleIncrease(parseInt(btn.dataset.productId, 10)));
        });

        cartBody.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', () => handleRemove(parseInt(btn.dataset.productId, 10)));
        });
    }

    // ── Quantity decrease ─────────────────────────────────────────────────────
    async function handleDecrease(productId) {
        const item = cartItems[productId];
        if (!item) return;

        if (item.quantity <= 1) {
            await handleRemove(productId);
            return;
        }

        const newQty = item.quantity - 1;
        await updateQuantity(productId, newQty);
    }

    // ── Quantity increase ─────────────────────────────────────────────────────
    async function handleIncrease(productId) {
        const item = cartItems[productId];
        if (!item) return;

        await updateQuantity(productId, item.quantity + 1);
    }

    // ── Update quantity via API ───────────────────────────────────────────────
    async function updateQuantity(productId, newQty) {
        const formData = new FormData();
        formData.append('action',     'update');
        formData.append('product_id', productId);
        formData.append('quantity',   newQty);

        const data = await apiFetch('api/cart.php', { method: 'POST', body: formData });
        if (!data || !data.success) return;

        if (newQty <= 0) {
            delete cartItems[productId];
            removeRow(productId);
        } else {
            cartItems[productId].quantity  = newQty;
            cartItems[productId].line_total = cartItems[productId].price * newQty;

            // Update DOM in-place (no full re-render)
            const qtyEl  = document.getElementById(`qty-${productId}`);
            const lineEl = document.getElementById(`line-${productId}`);
            if (qtyEl)  qtyEl.textContent  = newQty;
            if (lineEl) lineEl.textContent = formatPrice(cartItems[productId].line_total);
        }

        updateSubtotal();
        updateCartBadge(data.cart_count);

        if (Object.keys(cartItems).length === 0) {
            showEmptyState();
        }
    }

    // ── Remove item via API ───────────────────────────────────────────────────
    async function handleRemove(productId) {
        const formData = new FormData();
        formData.append('action',     'remove');
        formData.append('product_id', productId);

        const data = await apiFetch('api/cart.php', { method: 'POST', body: formData });
        if (!data || !data.success) return;

        delete cartItems[productId];
        removeRow(productId);
        updateSubtotal();
        updateCartBadge(data.cart_count);

        if (Object.keys(cartItems).length === 0) {
            showEmptyState();
        }
    }

    // ── Remove a table row from the DOM ──────────────────────────────────────
    function removeRow(productId) {
        const row = cartBody ? cartBody.querySelector(`tr[data-product-id="${productId}"]`) : null;
        if (row) row.remove();
    }

    // ── Clear entire cart ─────────────────────────────────────────────────────
    async function handleClearCart() {
        const formData = new FormData();
        formData.append('action', 'clear');

        const data = await apiFetch('api/cart.php', { method: 'POST', body: formData });
        if (!data || !data.success) return;

        cartItems = {};
        if (cartBody) cartBody.innerHTML = '';
        updateSubtotal();
        updateCartBadge(0);
        showEmptyState();
    }

    // ── Recalculate and display subtotal ──────────────────────────────────────
    function updateSubtotal() {
        if (!subtotalEl) return;

        const total = Object.values(cartItems).reduce((sum, item) => {
            return sum + (parseFloat(item.price) * parseInt(item.quantity, 10));
        }, 0);

        subtotalEl.textContent = formatPrice(total);
    }

    // ── Enable / disable checkout link (disabled attr is invalid on <a>) ─────
    function setCheckoutEnabled(enabled) {
        if (!checkoutBtn) return;
        if (enabled) {
            checkoutBtn.classList.remove('is-disabled');
            checkoutBtn.removeAttribute('aria-disabled');
            checkoutBtn.removeAttribute('tabindex');
        } else {
            checkoutBtn.classList.add('is-disabled');
            checkoutBtn.setAttribute('aria-disabled', 'true');
            checkoutBtn.setAttribute('tabindex', '-1');
        }
    }

    // ── Show / hide empty state ───────────────────────────────────────────────
    function showEmptyState() {
        if (cartContent) cartContent.setAttribute('hidden', '');
        if (cartEmpty)   cartEmpty.removeAttribute('hidden');
        setCheckoutEnabled(false);
        if (clearBtn)   clearBtn.disabled = true;
        if (subtotalEl) subtotalEl.textContent = formatPrice(0);
    }

    function hideEmptyState() {
        if (cartContent) cartContent.removeAttribute('hidden');
        if (cartEmpty)   cartEmpty.setAttribute('hidden', '');
        setCheckoutEnabled(true);
        if (clearBtn) clearBtn.disabled = false;
    }

    // ── Load cart from API on page load ───────────────────────────────────────
    async function loadCart() {
        const data = await apiFetch('api/cart.php');
        if (!data || !data.success) {
            showEmptyState();
            return;
        }

        // Build in-memory map keyed by product_id
        cartItems = {};
        (data.items || []).forEach(item => {
            cartItems[item.product_id] = item;
        });

        renderCart();
        updateCartBadge(data.cart_count);
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        loadCart();

        if (clearBtn) {
            clearBtn.addEventListener('click', handleClearCart);
        }

        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', (e) => {
                if (checkoutBtn.classList.contains('is-disabled')) {
                    e.preventDefault();
                }
            });
        }
    });

})();
