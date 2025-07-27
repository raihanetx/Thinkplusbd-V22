<div class="content-card">
    <h2 class="card-title">Apply Direct Discount</h2>
    <form id="generate-discount-form">
        <div class="form-row">
            <div class="form-group">
                <label for="discount-scope">Apply Discount To:</label>
                <select id="discount-scope" name="discount_scope">
                    <option value="all" selected>All Products</option>
                    <option value="category">A Specific Category</option>
                    <option value="specific">Specific Products</option>
                </select>
            </div>
            <div class="form-group">
                <label for="discount-type">Discount Type</label>
                <select id="discount-type" name="discount_type">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (Taka)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="discount-value">Discount Value</label>
                <input type="number" id="discount-value" name="discount_value" required>
            </div>
        </div>

        <div id="category-selection" class="form-row" style="display:none;">
            <div class="form-group">
                <label for="discount-category-select">Category</label>
                <select id="discount-category-select" name="category_name"></select>
            </div>
        </div>

        <div id="product-selection" class="form-row" style="display:none;">
            <div class="form-group">
                <label for="discount-product-select">Products</label>
                <select id="discount-product-select" name="product_ids[]" multiple></select>
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit">Apply Discount</button>
        </div>
    </form>
</div>

<div class="content-card">
    <h2 class="card-title">Discount Preview</h2>
    <div id="discount-preview" class="orders-table-container"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#discount-category-select, #discount-product-select').select2({
        width: '100%',
        placeholder: "Select...",
        allowClear: true
    });

    const scopeSelect = document.getElementById('discount-scope');
    const categorySelection = document.getElementById('category-selection');
    const productSelection = document.getElementById('product-selection');
    const categorySelect = $('#discount-category-select');
    const productSelect = $('#discount-product-select');
    let allProducts = [];
    let allCategories = [];

    function populateSelectors() {
        categorySelect.empty();
        productSelect.empty();

        allCategories.forEach(category => {
            const option = new Option(category.name, category.name);
            categorySelect.append(option);
        });

        // Initially populate products from the first category or leave empty
        const initialCategory = allCategories.length > 0 ? allCategories[0].name : null;
        updateProductDropdown(initialCategory);

        categorySelect.trigger('change');
    }

    function updateProductDropdown(selectedCategory) {
        productSelect.empty();
        const filteredProducts = selectedCategory ? allProducts.filter(p => p.category === selectedCategory) : allProducts;

        filteredProducts.forEach(product => {
            const option = new Option(`${product.name}`, product.id);
            productSelect.append(option);
        });
        productSelect.trigger('change');
    }

    Promise.all([
        fetch('get_categories.php').then(res => res.json()),
        fetch('get_products.php').then(res => res.json())
    ]).then(([categories, products]) => {
        allCategories = categories;
        allProducts = products;
        populateSelectors();
        updatePreview();
    });

    scopeSelect.addEventListener('change', function() {
        categorySelection.style.display = 'none';
        productSelection.style.display = 'none';

        switch (this.value) {
            case 'category':
                categorySelection.style.display = 'grid';
                break;
            case 'specific':
                categorySelection.style.display = 'grid';
                productSelection.style.display = 'grid';
                break;
        }
        updatePreview();
    });

    categorySelect.on('change', function() {
        if (scopeSelect.value === 'specific') {
            updateProductDropdown(this.value);
        }
        updatePreview();
    });

    document.getElementById('generate-discount-form').addEventListener('input', updatePreview);

    function updatePreview() {
        const form = document.getElementById('generate-discount-form');
        const formData = new FormData(form);
        const discountType = formData.get('discount_type');
        const discountValue = parseFloat(formData.get('discount_value'));
        const scope = formData.get('discount_scope');
        const category = formData.get('category_name');
        const productIds = $('#discount-product-select').val();

        if (isNaN(discountValue) || discountValue <= 0) {
            document.getElementById('discount-preview').innerHTML = '<p class="no-orders-message">Enter a valid discount value to see a preview.</p>';
            return;
        }

        let productsToDiscount = [];
        if (scope === 'all') {
            productsToDiscount = allProducts;
        } else if (scope === 'category') {
            productsToDiscount = allProducts.filter(p => p.category === category);
        } else if (scope === 'specific') {
            productsToDiscount = allProducts.filter(p => productIds.includes(p.id.toString()));
        }

        if (productsToDiscount.length === 0) {
            document.getElementById('discount-preview').innerHTML = '<p class="no-orders-message">No products match the current selection.</p>';
            return;
        }

        let previewHtml = '<table class="orders-table"><thead><tr><th>Product</th><th>Original Price</th><th>Discounted Price</th><th>Savings</th></tr></thead><tbody>';
        productsToDiscount.forEach(product => {
            const originalPrice = parseFloat(product.price);
            let discountedPrice;
            let savings;

            if (discountType === 'percentage') {
                savings = originalPrice * discountValue / 100;
                discountedPrice = originalPrice - savings;
            } else {
                savings = discountValue;
                discountedPrice = originalPrice - savings;
            }

            if (discountedPrice < 0) discountedPrice = 0;

            previewHtml += `<tr><td>${product.name}</td><td>${originalPrice.toFixed(2)}</td><td><strong>${discountedPrice.toFixed(2)}</strong></td><td>${savings.toFixed(2)}</td></tr>`;
        });
        previewHtml += '</tbody></table>';
        document.getElementById('discount-preview').innerHTML = previewHtml;
    }

    document.getElementById('generate-discount-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        // Append selected products from Select2
        $('#discount-product-select').val().forEach(id => {
            formData.append('product_ids[]', id);
        });

        fetch('apply_discount.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Discounts applied successfully!');
                // Consider redirecting to the active discounts list
                window.location.href = 'admin_dashboard.php?page=discounts_list';
            } else {
                alert('Failed to apply discounts: ' + (data.message || 'Unknown error'));
            }
        });
    });
});
</script>
