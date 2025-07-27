<div class="content-card">
    <h2 class="card-title">Active Direct Discounts</h2>
    <div id="active-discounts-container" class="orders-table-container"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function fetchAndDisplayDiscounts() {
        fetch('get_products.php')
            .then(response => response.json())
            .then(products => {
                const container = document.getElementById('active-discounts-container');
                const discountedProducts = products.filter(p => p.hasOwnProperty('discount'));

                if (discountedProducts.length === 0) {
                    container.innerHTML = '<p class="no-orders-message">No active direct discounts.</p>';
                    return;
                }

                let html = '<table class="orders-table"><thead>';
                html += '<tr><th>Product Name</th><th>Category</th><th>Original Price</th><th>Discount</th><th>Discounted Price</th><th>Actions</th></tr>';
                html += '</thead><tbody>';

                discountedProducts.forEach(product => {
                    const originalPrice = parseFloat(product.price);
                    let discountedPrice;
                    let discountDisplay;

                    if (product.discount.type === 'percentage') {
                        discountedPrice = originalPrice - (originalPrice * product.discount.value / 100);
                        discountDisplay = `${product.discount.value}%`;
                    } else {
                        discountedPrice = originalPrice - product.discount.value;
                        discountDisplay = `${product.discount.value} Taka`;
                    }

                    html += `
                        <tr>
                            <td data-label="Product Name">${product.name}</td>
                            <td data-label="Category">${product.category}</td>
                            <td data-label="Original Price">${originalPrice.toFixed(2)} Taka</td>
                            <td data-label="Discount">${discountDisplay}</td>
                            <td data-label="Discounted Price">${discountedPrice.toFixed(2)} Taka</td>
                            <td data-label="Actions">
                                <button class="action-btn action-btn-delete" onclick="removeDiscount(${product.id})">Remove</button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            });
    }

    window.removeDiscount = function(productId) {
        if (!confirm('Are you sure you want to remove the discount for this product?')) {
            return;
        }

        fetch('remove_discount.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Discount removed successfully!');
                fetchAndDisplayDiscounts();
            } else {
                alert('Failed to remove discount: ' + data.message);
            }
        });
    }

    fetchAndDisplayDiscounts();
});
</script>
