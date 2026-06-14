// checkout.js - Checkout form validation and order submission

'use strict';

(function () {

    // ── DOM references ───────────────────────────────────────────────────────
    const form           = document.getElementById('checkout-form');
    const cartItemsInput = document.getElementById('cart-items-json');
    const summaryBody    = document.getElementById('order-summary-body');
    const summaryTotal   = document.getElementById('order-summary-total');

    // ── Validation helpers ────────────────────────────────────────────────────
    const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errEl = document.getElementById(`${fieldId}-error`);
        if (field)  field.classList.add('error');
        if (errEl)  errEl.textContent = message;
    }

    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errEl = document.getElementById(`${fieldId}-error`);
        if (field)  field.classList.remove('error');
        if (errEl)  errEl.textContent = '';
    }

    function clearAllErrors() {
        ['name', 'email', 'phone', 'address'].forEach(clearError);
        const paymentErr = document.getElementById('payment-error');
        if (paymentErr) paymentErr.textContent = '';
    }

    function validateForm() {
        clearAllErrors();
        let valid = true;

        const name    = document.getElementById('name')?.value.trim()    || '';
        const email   = document.getElementById('email')?.value.trim()   || '';
        const phone   = document.getElementById('phone')?.value.trim()   || '';
        const address = document.getElementById('address')?.value.trim() || '';
        const payment = form?.querySelector('input[name="payment_method"]:checked');

        if (!name) {
            showError('name', 'Full name is required.');
            valid = false;
        }

        if (!email) {
            showError('email', 'Email address is required.');
            valid = false;
        } else if (!EMAIL_REGEX.test(email)) {
            showError('email', 'Please enter a valid email address.');
            valid = false;
        }

        if (!phone) {
            showError('phone', 'Phone number is required.');
            valid = false;
        }

        if (!address) {
            showError('address', 'Delivery address is required.');
            valid = false;
        }

        if (!payment) {
            const paymentErr = document.getElementById('payment-error');
            if (paymentErr) paymentErr.textContent = 'Please select a payment method.';
            valid = false;
        }

        return valid;
    }

    // ── Populate order summary from cart API ──────────────────────────────────
    async function loadCartSummary() {
        const data = await apiFetch('api/cart.php');

        if (!data || !data.success || !data.items || data.items.length === 0) {
            // Cart is empty — redirect to cart page
            window.location.href = 'cart.php?msg=empty';
            return;
        }

        // Store minimal items JSON (product_id + quantity only) for order API
        if (cartItemsInput) {
            const orderItems = data.items.map(item => ({
                product_id: item.product_id,
                quantity:   item.quantity,
            }));
            cartItemsInput.value = JSON.stringify(orderItems);
        }

        // Render order summary rows
        if (summaryBody) {
            summaryBody.innerHTML = data.items.map(item => `
                <div class="order-summary-item">
                    <span>${escapeHtml(item.name)} × ${item.quantity}</span>
                    <span>${formatPrice(item.line_total)}</span>
                </div>
            `).join('');
        }

        // Render total
        if (summaryTotal) {
            summaryTotal.innerHTML = `
                <span>Total</span>
                <span style="color:var(--color-primary)">${formatPrice(data.cart_total)}</span>
            `;
        }

        updateCartBadge(data.cart_count);
    }

    // ── Handle form submission ────────────────────────────────────────────────
    async function handleSubmit(e) {
        e.preventDefault();

        if (!validateForm()) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Placing Order...';
        }

        const formData = new FormData(form);
        // Ensure cart items JSON is included
        if (cartItemsInput) {
            formData.set('items', cartItemsInput.value);
        }

        const data = await apiFetch('api/order.php', {
            method: 'POST',
            body:   formData,
        });

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Place Order';
        }

        if (!data) return; // apiFetch already showed error toast

        if (data.success) {
            // Redirect to confirmation page
            window.location.href = `confirmation.php?order_id=${data.order_id}`;
        } else {
            showToast(data.error || 'Failed to place order. Please try again.', 'error');
        }
    }

    // ── Real-time field validation (clear errors on input) ────────────────────
    function initLiveValidation() {
        ['name', 'email', 'phone', 'address'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => clearError(fieldId));
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        loadCartSummary();
        initLiveValidation();

        if (form) {
            form.addEventListener('submit', handleSubmit);
        }
    });

})();
