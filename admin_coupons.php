<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="content-card">
    <h2 class="card-title">Create or Edit Coupon</h2>
    <form id="create-coupon-form">
        <input type="hidden" id="edit-coupon-code" value="">
        <div class="form-row">
            <div class="form-group">
                <label for="coupon-code">Coupon Code</label>
                <div class="input-group">
                    <input type="text" id="coupon-code" required>
                    <button type="button" id="generate-random-code">Generate</button>
                </div>
            </div>
            <div class="form-group">
                <label for="discount-type">Discount Type</label>
                <select id="discount-type">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (Taka)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="discount-value">Discount Value</label>
                <input type="number" id="discount-value" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category-select">Applicable Categories</label>
                <select id="category-select" multiple>
                </select>
            </div>
            <div class="form-group">
                <label for="product-select">Applicable Products</label>
                <select id="product-select" multiple>
                </select>
            </div>
        </div>
        <div class="form-group">
            <small>Leave categories and products blank to apply the coupon to all products.</small>
        </div>

        <div class="form-buttons">
            <button type="submit" id="save-coupon-btn">Save Coupon</button>
            <button type="button" id="save-and-create-another-btn">Save and Create Another</button>
            <button type="button" id="clear-form-btn" style="display: none;">Clear Form</button>
        </div>
    </form>
</div>

<div class="content-card">
    <h2 class="card-title">Existing Coupons</h2>
    <div id="coupons-container" class="orders-table-container"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#category-select, #product-select').select2({
        width: '100%',
        placeholder: "Select...",
        allowClear: true
    });

    let allProducts = [];
    let allCategories = [];

    function fetchAndDisplayCoupons() {
        fetch('get_coupons.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('coupons-container');
                if (data.error) {
                    container.innerHTML = `<p class="alert-message alert-danger">${data.error}</p>`;
                    return;
                }
                if (data.length === 0) {
                    container.innerHTML = '<p class="no-orders-message">No coupons created yet.</p>';
                    return;
                }
                let html = '<table class="orders-table"><thead>';
                html += '<tr><th>Code</th><th>Discount</th><th>Applies To</th><th>Actions</th></tr>';
                html += '</thead><tbody>';
                data.forEach(coupon => {
                    let appliesTo = 'All Products';
                    if (coupon.category && coupon.category.length > 0) {
                        appliesTo = `Categories: ${coupon.category.join(', ')}`;
                    }
                    if (coupon.product_ids && coupon.product_ids.length > 0) {
                        const productNames = coupon.product_ids.map(id => {
                            const product = allProducts.find(p => p.id == id);
                            return product ? product.name : `ID: ${id}`;
                        }).join(', ');
                        appliesTo += `<br>Products: ${productNames}`;
                    }

                    html += `
                        <tr>
                            <td data-label="Code"><strong>${coupon.code}</strong></td>
                            <td data-label="Discount">${coupon.discount_value}${coupon.discount_type === 'percentage' ? '%' : ' Taka'}</td>
                            <td data-label="Applies To">${appliesTo}</td>
                            <td data-label="Actions">
                                <div class="action-buttons-group">
                                    <button class="action-btn" onclick="editCoupon('${coupon.code}')">Edit</button>
                                    <button class="action-btn action-btn-delete" onclick="deleteCoupon('${coupon.code}')">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            });
    }

    function populateSelectors() {
        const categorySelect = $('#category-select');
        const productSelect = $('#product-select');
        categorySelect.empty();
        productSelect.empty();

        allCategories.forEach(category => {
            const option = new Option(category.name, category.name);
            categorySelect.append(option);
        });

        allProducts.forEach(product => {
            const option = new Option(`${product.name} (${product.category})`, product.id);
            productSelect.append(option);
        });

        categorySelect.trigger('change');
        productSelect.trigger('change');
    }

    Promise.all([
        fetch('get_categories.php').then(res => res.json()),
        fetch('get_products.php').then(res => res.json())
    ]).then(([categories, products]) => {
        allCategories = categories;
        allProducts = products;
        populateSelectors();
        fetchAndDisplayCoupons();
    });

    document.getElementById('generate-random-code').addEventListener('click', function() {
        document.getElementById('coupon-code').value = Math.random().toString(36).substring(2, 10).toUpperCase();
    });

    function resetForm() {
        document.getElementById('create-coupon-form').reset();
        $('#category-select').val(null).trigger('change');
        $('#product-select').val(null).trigger('change');
        document.getElementById('edit-coupon-code').value = '';
        document.getElementById('coupon-code').disabled = false;
        document.getElementById('clear-form-btn').style.display = 'none';
        document.getElementById('save-coupon-btn').textContent = 'Save Coupon';
    }

    document.getElementById('clear-form-btn').addEventListener('click', resetForm);

    function submitForm(reload = true) {
        const isEdit = document.getElementById('edit-coupon-code').value !== '';
        const code = isEdit ? document.getElementById('edit-coupon-code').value : document.getElementById('coupon-code').value;

        const payload = {
            code: code,
            discount_type: document.getElementById('discount-type').value,
            discount_value: parseFloat(document.getElementById('discount-value').value),
            category: $('#category-select').val(),
            product_ids: $('#product-select').val(),
            is_edit: isEdit
        };

        if (!payload.code || !payload.discount_value) {
            alert('Please fill in Code and Discount Value.');
            return;
        }

        fetch('create_coupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Coupon ${isEdit ? 'updated' : 'created'} successfully!`);
                if (reload) {
                    location.reload();
                } else {
                    resetForm();
                    fetchAndDisplayCoupons();
                }
            } else {
                alert('Failed to save coupon: ' + data.message);
            }
        });
    }

    document.getElementById('create-coupon-form').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm(true);
    });

    document.getElementById('save-and-create-another-btn').addEventListener('click', function() {
        submitForm(false);
    });

    window.editCoupon = function(code) {
        fetch('get_coupons.php')
            .then(res => res.json())
            .then(coupons => {
                const coupon = coupons.find(c => c.code === code);
                if (coupon) {
                    document.getElementById('edit-coupon-code').value = coupon.code;
                    document.getElementById('coupon-code').value = coupon.code;
                    document.getElementById('coupon-code').disabled = true;
                    document.getElementById('discount-type').value = coupon.discount_type;
                    document.getElementById('discount-value').value = coupon.discount_value;
                    $('#category-select').val(coupon.category || []).trigger('change');
                    $('#product-select').val(coupon.product_ids || []).trigger('change');

                    document.getElementById('save-coupon-btn').textContent = 'Update Coupon';
                    document.getElementById('clear-form-btn').style.display = 'inline-block';
                    window.scrollTo(0, 0);
                }
            });
    }

    window.deleteCoupon = function(code) {
        if (!confirm(`Are you sure you want to delete the coupon "${code}"?`)) return;

        fetch('delete_coupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Coupon deleted successfully!');
                fetchAndDisplayCoupons();
            } else {
                alert('Failed to delete coupon: ' + data.message);
            }
        });
    }
});
</script>

<style>
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        margin-bottom: .5rem;
        font-weight: 500;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 0.65rem;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
    }
    .input-group {
        display: flex;
    }
    .input-group input {
        flex-grow: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-group button {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border: 1px solid var(--border-color);
        border-left: 0;
        background: #f5f7fa;
        padding: 0 1rem;
        cursor: pointer;
    }
    .form-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    .form-buttons button {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-weight: 500;
    }
    .form-buttons button[type="submit"], .form-buttons button#save-and-create-another-btn {
        background-color: var(--primary-color);
        color: white;
    }
     .form-buttons button#clear-form-btn {
        background-color: #6c757d;
        color: white;
    }
    .select2-container .select2-selection--multiple {
        min-height: 45px;
        border: 1px solid var(--border-color) !important;
        padding: 0.3rem;
    }
</style>
