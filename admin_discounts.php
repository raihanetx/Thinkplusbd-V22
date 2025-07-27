<div class="content-card">
    <h2 class="card-title">Generate Discount</h2>
    <form id="generate-discount-form">
        <div class="form-group">
            <label for="discount-scope">Apply Discount To:</label>
            <select id="discount-scope" name="discount_scope">
                <option value="all">All Products</option>
                <option value="category">All Products in a Category</option>
                <option value="specific">Specific Products</option>
            </select>
        </div>

        <div id="category-selection" class="form-group" style="display:none;">
            <label for="discount-category-select">Category</label>
            <select id="discount-category-select" name="category_name"></select>
        </div>

        <div id="product-selection" class="form-group" style="display:none;">
            <label for="discount-product-select">Products</label>
            <select id="discount-product-select" name="product_ids[]" multiple></select>
        </div>

        <div class="form-group">
            <label for="discount-type">Discount Type</label>
            <select id="discount-type" name="discount_type">
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
            </select>
        </div>

        <div class="form-group">
            <label for="discount-value">Discount Value</label>
            <input type="number" id="discount-value" name="discount_value" required>
        </div>

        <button type="submit">Apply Discount</button>
    </form>
</div>

<div class="content-card">
    <h2 class="card-title">Discount Preview</h2>
    <div id="discount-preview"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scopeSelect = document.getElementById('discount-scope');
    const categorySelection = document.getElementById('category-selection');
    const productSelection = document.getElementById('product-selection');
    const categorySelect = document.getElementById('discount-category-select');
    const productSelect = document.getElementById('discount-product-select');
    let allProducts = [];

    fetch('get_products.php')
        .then(response => response.json())
        .then(products => {
            allProducts = products;
            updateProductSelection();
        });

    fetch('get_categories.php')
        .then(response => response.json())
        .then(categories => {
            categories.forEach(category => {
                const option = new Option(category.name, category.name);
                categorySelect.add(option);
            });
        });

    scopeSelect.addEventListener('change', function() {
        switch (this.value) {
            case 'all':
                categorySelection.style.display = 'none';
                productSelection.style.display = 'none';
                break;
            case 'category':
                categorySelection.style.display = 'block';
                productSelection.style.display = 'none';
                break;
            case 'specific':
                categorySelection.style.display = 'block';
                productSelection.style.display = 'block';
                break;
        }
        updateProductSelection();
    });

    categorySelect.addEventListener('change', function() {
        updateProductSelection();
    });

    function updateProductSelection() {
        const selectedScope = scopeSelect.value;
        const selectedCategory = categorySelect.value;
        productSelect.innerHTML = '';

        if (selectedScope === 'specific') {
            const filteredProducts = allProducts.filter(product => product.category === selectedCategory);
            filteredProducts.forEach(product => {
                const option = new Option(product.name, product.id);
                productSelect.add(option);
            });
        }
    }

    document.getElementById('generate-discount-form').addEventListener('input', function() {
        const formData = new FormData(this);
        const discountType = formData.get('discount_type');
        const discountValue = parseFloat(formData.get('discount_value'));
        const scope = formData.get('discount_scope');
        const category = formData.get('category_name');
        const productIds = formData.getAll('product_ids[]');

        if (isNaN(discountValue) || discountValue <= 0) {
            document.getElementById('discount-preview').innerHTML = '<p>Enter a valid discount value.</p>';
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

        let previewHtml = '<table><tr><th>Product</th><th>Original Price</th><th>Discounted Price</th></tr>';
        productsToDiscount.forEach(product => {
            const originalPrice = parseFloat(product.price);
            let discountedPrice;
            if (discountType === 'percentage') {
                discountedPrice = originalPrice - (originalPrice * discountValue / 100);
            } else {
                discountedPrice = originalPrice - discountValue;
            }
            previewHtml += `<tr><td>${product.name}</td><td>${originalPrice.toFixed(2)}</td><td>${discountedPrice.toFixed(2)}</td></tr>`;
        });
        previewHtml += '</table>';
        document.getElementById('discount-preview').innerHTML = previewHtml;
    });

    document.getElementById('generate-discount-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('apply_discount.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Discounts applied successfully!');
                location.reload();
            } else {
                alert('Failed to apply discounts: ' + data.message);
            }
        });
    });
});
</script>
