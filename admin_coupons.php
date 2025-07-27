<?php
// Note: This file is now included in admin_dashboard.php
// The session check and HTML structure are handled by the parent file.
?>
<div class="content-card">
    <h2 class="card-title">All Coupons</h2>
    <div id="coupons-container"></div>
</div>
<div class="content-card">
    <h2 class="card-title">Create Coupon</h2>
    <form id="create-coupon-form">
        <div class="form-group">
            <label for="coupon-code">Coupon Code</label>
            <div style="display: flex;">
                <input type="text" id="coupon-code" style="flex-grow: 1;">
                <button type="button" id="generate-random-code">Generate Random</button>
            </div>
        </div>
        <div class="form-group">
            <label for="category-select">Category</label>
            <select id="category-select" required>
                <option value="">Select a category</option>
            </select>
        </div>
        <div class="form-group">
            <label for="product-select">Products</label>
            <select id="product-select" multiple required>
                <option value="">Select a category first</option>
            </select>
        </div>
        <div class="form-group">
            <label for="discount-type">Discount Type</label>
            <select id="discount-type">
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
            </select>
        </div>
        <div class="form-group">
            <label for="discount-value">Discount Value</label>
            <input type="number" id="discount-value" required>
        </div>
        <button type="submit">Create New Coupon</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch and display existing coupons
    fetch('get_coupons.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('coupons-container');
            if (data.error) {
                container.innerHTML = `<p>${data.error}</p>`;
                return;
            }
            if (data.length === 0) {
                container.innerHTML = '<p>No coupons to display.</p>';
                return;
            }
            let html = '<table>';
            html += '<tr><th>Code</th><th>Discount</th><th>Product IDs</th><th>Category</th><th>Action</th></tr>';
            data.forEach(coupon => {
                html += `
                    <tr>
                        <td>${coupon.code}</td>
                        <td>${coupon.discount_value}${coupon.discount_type === 'percentage' ? '%' : ' Taka'}</td>
                        <td>${coupon.product_ids ? coupon.product_ids.join(', ') : 'All'}</td>
                        <td>${coupon.category || 'All'}</td>
                        <td>
                            <button onclick="deleteCoupon('${coupon.code}')">Delete</button>
                        </td>
                    </tr>
                `;
            });
            html += '</table>';
            container.innerHTML = html;
        });

    // Populate category dropdown
    const categorySelect = document.getElementById('category-select');
    const productSelect = document.getElementById('product-select');
    let allProducts = [];

    fetch('get_categories.php')
        .then(response => response.json())
        .then(categories => {
            categories.forEach(category => {
                const option = new Option(category.name, category.name);
                categorySelect.add(option);
            });
        });

    fetch('get_products.php')
        .then(response => response.json())
        .then(products => {
            allProducts = products;
        });

    categorySelect.addEventListener('change', function() {
        const selectedCategory = this.value;
        productSelect.innerHTML = '';
        const filteredProducts = allProducts.filter(product => product.category === selectedCategory);
        filteredProducts.forEach(product => {
            const option = new Option(product.name, product.id);
            productSelect.add(option);
        });
    });

    // Random code generation
    document.getElementById('generate-random-code').addEventListener('click', function() {
        const randomCode = Math.random().toString(36).substring(2, 10).toUpperCase();
        document.getElementById('coupon-code').value = randomCode;
    });

    const createCouponForm = document.getElementById('create-coupon-form');

    createCouponForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const code = document.getElementById('coupon-code').value;
        const discount_type = document.getElementById('discount-type').value;
        const discount_value = document.getElementById('discount-value').value;
        const category = categorySelect.value;
        const product_ids = Array.from(productSelect.selectedOptions).map(option => option.value);

        fetch('create_coupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code,
                discount_type,
                discount_value,
                product_ids: product_ids.length > 0 ? product_ids : null,
                category: category_ids.length > 0 ? category_ids : null
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (reload) {
                    location.reload();
                } else {
                    createCouponForm.reset();
                    updatePreview();
                    // Re-fetch coupons to show the newly created one
                    fetch('get_coupons.php')
                        .then(response => response.json())
                        .then(data => {
                            const container = document.getElementById('coupons-container');
                            if (data.error) {
                                container.innerHTML = `<p>${data.error}</p>`;
                                return;
                            }
                            if (data.length === 0) {
                                container.innerHTML = '<p>No coupons to display.</p>';
                                return;
                            }
                            let html = '<table>';
                            html += '<tr><th>Code</th><th>Discount</th><th>Product IDs</th><th>Category</th><th>Action</th></tr>';
                            data.forEach(coupon => {
                                html += `
                                    <tr>
                                        <td>${coupon.code}</td>
                                        <td>${coupon.discount_value}${coupon.discount_type === 'percentage' ? '%' : ' Taka'}</td>
                                        <td>${coupon.product_ids ? coupon.product_ids.join(', ') : 'All'}</td>
                                        <td>${coupon.category ? coupon.category.join(', ') : 'All'}</td>
                                        <td>
                                            <button onclick="deleteCoupon('${coupon.code}')">Delete</button>
                                        </td>
                                    </tr>
                                `;
                            });
                            html += '</table>';
                            container.innerHTML = html;
                        });
                    alert('Coupon created successfully!');
                }
            } else {
                alert('Failed to create coupon.');
            }
        });
    }

    createCouponForm.addEventListener('submit', function(event) {
        event.preventDefault();
        submitForm();
    });

    document.getElementById('save-and-create-another').addEventListener('click', function() {
        submitForm(false);
    });
});

function deleteCoupon(couponCode) {
    fetch('delete_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ code: couponCode }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete coupon.');
        }
    });
}
</script>
