/* assets/js/app.js */
/* Interactive UI & AJAX E-Commerce Controller - Kigali TechHub */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Header scroll effect
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // 2. Mobile Menu Toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            menuToggle.innerHTML = navMenu.classList.contains('open') 
                ? '✕' 
                : '☰';
        });
    }

    // 3. Combined Category & Search filtering (on homepage)
    const categoryTabs = document.querySelectorAll('.category-tab');
    const productCards = document.querySelectorAll('.product-card');
    const searchInput = document.querySelector('.hero-search-input');
    const searchBtn = document.querySelector('.hero-search-btn');

    if (productCards.length > 0) {
        const updateFilters = () => {
            const activeTab = document.querySelector('.category-tab.active');
            const categorySlug = activeTab ? activeTab.getAttribute('data-category') : 'all';
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

            let matchedCount = 0;
            productCards.forEach(card => {
                const productCat = card.getAttribute('data-category-slug');
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const productDescription = card.querySelector('.product-desc').textContent.toLowerCase();

                const matchesCategory = (categorySlug === 'all' || productCat === categorySlug);
                const matchesSearch = (productName.includes(query) || productDescription.includes(query));

                if (matchesCategory && matchesSearch) {
                    const isAlreadyVisible = card.style.display === 'flex';
                    card.style.display = 'flex';
                    if (!isAlreadyVisible) {
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transition = 'opacity 0.4s ease';
                        }, 50);
                    } else {
                        card.style.opacity = '1';
                    }
                    matchedCount++;
                } else {
                    card.style.display = 'none';
                    card.style.opacity = '0';
                }
            });

            // Toggle no-products-message visibility
            const noMatchMessage = document.getElementById('no-products-message');
            if (noMatchMessage) {
                noMatchMessage.style.display = (matchedCount === 0) ? 'block' : 'none';
            }
        };

        if (categoryTabs.length > 0) {
            categoryTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    categoryTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    updateFilters();
                });
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', updateFilters);
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateFilters();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                updateFilters();
            });
        }
    }

    // 5. Quantity selector controls (on product detail or cart)
    const qtySelectors = document.querySelectorAll('.qty-selector');
    qtySelectors.forEach(selector => {
        const minusBtn = selector.querySelector('.qty-btn.minus');
        const plusBtn = selector.querySelector('.qty-btn.plus');
        const qtyInput = selector.querySelector('.qty-input');
        
        if (minusBtn && plusBtn && qtyInput) {
            minusBtn.addEventListener('click', () => {
                let currentVal = parseInt(qtyInput.value) || 1;
                if (currentVal > 1) {
                    qtyInput.value = currentVal - 1;
                    qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            plusBtn.addEventListener('click', () => {
                let currentVal = parseInt(qtyInput.value) || 1;
                qtyInput.value = currentVal + 1;
                qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });

    // 6. Payment Selection cards in Checkout
    const paymentCards = document.querySelectorAll('.payment-option-card');
    const momoDetailsField = document.getElementById('momo-details-field');
    const phoneInput = document.getElementById('customer_phone');
    if (paymentCards.length > 0) {
        paymentCards.forEach(card => {
            card.addEventListener('click', () => {
                // Uncheck all other radios, check current one
                paymentCards.forEach(c => c.classList.remove('selected'));
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    card.classList.add('selected');
                    
                    // Show MoMo instructions if MTN MoMo or Airtel Money is selected
                    if (momoDetailsField) {
                        if (radio.value === 'MTN Mobile Money' || radio.value === 'Airtel Money') {
                            momoDetailsField.style.display = 'block';
                            const serviceName = radio.value;
                            document.getElementById('momo-provider-name').textContent = serviceName;
                            
                            // Synchronize checkout phone number as the default payment phone
                            const paymentPhone = document.getElementById('payment_phone');
                            if (paymentPhone && phoneInput) {
                                paymentPhone.value = phoneInput.value;
                            }
                        } else {
                            momoDetailsField.style.display = 'none';
                        }
                    }
                }
            });
        });
    }

    // Synchronize phones for MoMo convenience
    if (phoneInput) {
        phoneInput.addEventListener('input', () => {
            const paymentPhone = document.getElementById('payment_phone');
            if (paymentPhone) {
                paymentPhone.value = phoneInput.value;
            }
        });
    }

    // 7. Dynamic Cart Actions via AJAX / Fetch
    // We will build an internal gateway endpoint at `cart-action.php`
    window.cartActions = {
        add: function(productId, quantity = 1) {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('cart-action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.updateBadge(data.cart_count);
                    showToast('Successfully added item to cart!', 'success');
                } else {
                    showToast(data.message || 'Error adding item.', 'error');
                }
            })
            .catch(err => {
                console.error('Cart Action Error:', err);
                showToast('Failed to connect to cart system.', 'error');
            });
        },

        update: function(productId, quantity) {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            return fetch('cart-action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.updateBadge(data.cart_count);
                    this.refreshCartPageData(data);
                    showToast('Shopping cart updated!', 'success');
                } else {
                    showToast(data.message || 'Error updating quantity.', 'error');
                }
            });
        },

        remove: function(productId) {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);

            fetch('cart-action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.updateBadge(data.cart_count);
                    
                    // Animate card removal in DOM
                    const row = document.querySelector(`.cart-item-row[data-product-id="${productId}"]`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-30px)';
                        row.style.transition = 'all 0.3s ease';
                        setTimeout(() => {
                            row.remove();
                            this.refreshCartPageData(data);
                        }, 300);
                    }
                    showToast('Item removed from cart.', 'success');
                } else {
                    showToast(data.message || 'Error removing item.', 'error');
                }
            });
        },

        updateBadge: function(count) {
            const badges = document.querySelectorAll('.cart-badge');
            badges.forEach(badge => {
                badge.textContent = count;
                if (count > 0) {
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            });
        },

        refreshCartPageData: function(data) {
            // Check if we are on the main cart page
            const subtotalEl = document.getElementById('cart-subtotal');
            const totalEl = document.getElementById('cart-total');
            
            if (subtotalEl && totalEl) {
                // Update pricing displays
                subtotalEl.textContent = formatRWF(data.subtotal);
                totalEl.textContent = formatRWF(data.total);
                
                // Update individual item totals
                if (data.items) {
                    Object.keys(data.items).forEach(prodId => {
                        const itemTotalEl = document.getElementById(`item-total-${prodId}`);
                        if (itemTotalEl) {
                            itemTotalEl.textContent = formatRWF(data.items[prodId].subtotal);
                        }
                    });
                }
                
                // If cart is empty, reload page to display "Cart is empty" state
                if (data.cart_count === 0) {
                    window.location.reload();
                }
            }
        }
    };

    // Helper functions
    function formatRWF(number) {
        return new Intl.NumberFormat('en-US').format(number) + ' RWF';
    }

    // Set up Toast Notification system
    function showToast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Add tiny icon depending on type
        let icon = '✓';
        if (type === 'error') icon = '✕';
        if (type === 'info') icon = 'ℹ';
        
        toast.innerHTML = `<span class="toast-icon">${icon}</span> <span class="toast-message">${message}</span>`;
        container.appendChild(toast);

        // Animation trigger
        setTimeout(() => toast.classList.add('show'), 10);

        // Autoclose toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Attach click events to "Add to Cart" triggers
    document.body.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.add-cart-btn-trigger');
        if (addBtn) {
            e.preventDefault();
            const productId = addBtn.getAttribute('data-product-id');
            const qtyInput = document.querySelector('.qty-input');
            const quantity = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;
            window.cartActions.add(productId, quantity);
        }

        const removeBtn = e.target.closest('.remove-cart-btn-trigger');
        if (removeBtn) {
            e.preventDefault();
            const productId = removeBtn.getAttribute('data-product-id');
            window.cartActions.remove(productId);
        }
    });

    // Attach change events to Cart quantity fields
    document.body.addEventListener('change', (e) => {
        const qtyField = e.target.closest('.cart-qty-trigger');
        if (qtyField) {
            const productId = qtyField.getAttribute('data-product-id');
            const quantity = parseInt(qtyField.value) || 1;
            window.cartActions.update(productId, quantity);
        }
    });
});
