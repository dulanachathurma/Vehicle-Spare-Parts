/**
 * assets/js/cart.js - quantity controls, live totals and checkout
 * validation for orders/cart.php and orders/checkout.php.
 *
 * The quantity inputs on cart.php still submit a real form to
 * orders/cart_action.php (server is always the source of truth for
 * stock limits), but this recalculates the displayed line/subtotal
 * instantly so the page doesn't feel like it's waiting on a reload.
 */

document.addEventListener('DOMContentLoaded', function () {
    var cartTable = document.querySelector('.ord-cart-table');

    if (cartTable) {
        var subtotalEl = document.querySelector('[data-ord-subtotal]');

        function recalculateCart() {
            var total = 0;

            cartTable.querySelectorAll('tbody tr').forEach(function (row) {
                var priceCell = row.querySelector('[data-ord-unit-price]');
                var qtyInput = row.querySelector('[data-ord-qty-input]');
                var lineCell = row.querySelector('[data-ord-line-subtotal]');

                if (!priceCell || !qtyInput || !lineCell) {
                    return;
                }

                var price = parseFloat(priceCell.dataset.ordUnitPrice) || 0;
                var qty = parseInt(qtyInput.value, 10) || 0;
                var lineTotal = price * qty;

                lineCell.textContent = formatMoney(lineTotal);
                total += lineTotal;
            });

            if (subtotalEl) {
                subtotalEl.textContent = 'Subtotal: ' + formatMoney(total);
            }
        }

        function formatMoney(amount) {
            return (window.CURRENCY_SYMBOL || 'Rs') + ' ' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        cartTable.querySelectorAll('[data-ord-qty-input]').forEach(function (input) {
            input.addEventListener('input', recalculateCart);
            input.addEventListener('change', function () {
                var form = input.closest('[data-ord-qty-form]');
                if (form) {
                    form.submit();
                }
            });
        });
    }

    var checkoutForm = document.getElementById('ordCheckoutForm');

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (event) {
            var address = checkoutForm.querySelector('textarea[name="shipping_address"]');
            var gatewaySelected = checkoutForm.querySelector('input[name="gateway_id"]:checked');

            if (address && !address.value.trim()) {
                event.preventDefault();
                address.classList.add('is-invalid');
                address.focus();
                return;
            }

            if (!gatewaySelected) {
                event.preventDefault();
                window.alert('Please choose a payment method.');
            }
        });
    }

    // Simulated card form (payment/mock_gateway.php) - restrict each
    // field to what a real card form would accept, formatting as the
    // customer types rather than only rejecting on submit. Also mirrors
    // what's typed onto a live card preview above the form, and detects
    // the card network (Visa/Mastercard) from the leading digits, the
    // way a real checkout page would.
    var previewNumber = document.querySelector('[data-preview-number]');
    var previewName = document.querySelector('[data-preview-name]');
    var previewExpiry = document.querySelector('[data-preview-expiry]');
    var brandVisa = document.querySelector('[data-brand-visa]');
    var brandMastercard = document.querySelector('[data-brand-mastercard]');

    function detectCardBrand(digits) {
        if (/^4/.test(digits)) {
            return 'visa';
        }
        if (/^5[1-5]/.test(digits) || /^2[2-7]/.test(digits)) {
            return 'mastercard';
        }
        return null;
    }

    var cardHolderInput = document.getElementById('card_holder');
    if (cardHolderInput && previewName) {
        cardHolderInput.addEventListener('input', function () {
            previewName.textContent = cardHolderInput.value.trim() || 'YOUR NAME';
        });
    }

    var cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function () {
            var digits = cardNumberInput.value.replace(/\D/g, '').slice(0, 16);
            cardNumberInput.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');

            if (previewNumber) {
                var groups = [];
                for (var i = 0; i < 16; i += 4) {
                    var group = digits.slice(i, i + 4);
                    groups.push(group ? group.padEnd(4, '•') : '••••');
                }
                previewNumber.textContent = groups.join(' ');
            }

            if (brandVisa && brandMastercard) {
                var brand = detectCardBrand(digits);
                brandVisa.hidden = brand !== 'visa';
                brandMastercard.hidden = brand !== 'mastercard';
            }
        });
    }

    var expiryInput = document.getElementById('card_expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function () {
            var digits = expiryInput.value.replace(/\D/g, '').slice(0, 4);
            expiryInput.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;

            if (previewExpiry) {
                previewExpiry.textContent = expiryInput.value || 'MM/YY';
            }
        });
    }

    var cvvInput = document.getElementById('card_cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function () {
            cvvInput.value = cvvInput.value.replace(/\D/g, '').slice(0, 3);
        });
    }
});
