// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // Mobile Navbar Toggle
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const navLinks = document.getElementById('nav-links');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
        });
    }

    // Refresh cart count on load
    updateCartCount();
});

// Toast notification helper
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let iconClass = 'fa-info-circle';
    if (type === 'success') iconClass = 'fa-check-circle text-success';
    if (type === 'error') iconClass = 'fa-exclamation-circle text-danger';

    toast.innerHTML = `
        <i class="fa-solid ${iconClass}"></i>
        <div style="flex-grow: 1;">${message}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Fetch Cart Count from API
function updateCartCount() {
    fetch('api/cart_actions.php?action=count')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.innerText = data.count !== undefined ? data.count : 0;
            }
        })
        .catch(err => {
            console.debug('Cart count sync:', err);
        });
}

// Add Item To Cart
function addToCart(foodId, quantity = 1) {
    const formData = new FormData();
    formData.append('food_id', foodId);
    formData.append('quantity', quantity);

    fetch('api/cart_actions.php?action=add', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            updateCartCount();
        } else {
            showToast(data.message || 'Please log in to add items to cart', 'error');
            if (data.redirect) {
                setTimeout(() => window.location.href = data.redirect, 1200);
            }
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Something went wrong. Please try again.', 'error');
    });
}

