/**
 * Sharurah Hardware Ltd Management System - Main JavaScript
 * File: js/app.js
 */

// Global variables
let currentPage = 'dashboard';
let products = [];
let customers = [];
let orders = [];
let payments = [];
let saleProducts = [];

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeModals();
    initializeForms();
    initializeCharts();
    loadDashboardData();
});

// Navigation
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.sidebar-menu a');
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            navigateToPage(page);
            
            // Update active state
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
}

function navigateToPage(page) {
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(p => {
        p.style.display = 'none';
    });
    
    // Show selected page
    const pageElement = document.getElementById(page + '-page');
    if (pageElement) {
        pageElement.style.display = 'block';
        currentPage = page;
        
        // Load page-specific data
        switch(page) {
            case 'inventory':
                loadProducts();
                break;
            case 'sales':
                loadOrders();
                break;
            case 'customers':
                loadCustomers();
                break;
            case 'payments':
                loadPayments();
                break;
            case 'accounting':
                loadAccountingData();
                break;
        }
    }
    
    // Update header title
    const headerTitle = document.querySelector('.header h1');
    if (headerTitle) {
        headerTitle.textContent = page.charAt(0).toUpperCase() + page.slice(1);
    }
}

// Modals
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.modal-close');
    
    // Open modals
    document.getElementById('addProductBtn')?.addEventListener('click', () => {
        openModal('addProductModal');
    });
    
    document.getElementById('newSaleBtn')?.addEventListener('click', () => {
        openModal('newSaleModal');
        loadCustomersForSale();
    });
    
    document.getElementById('addCustomerBtn')?.addEventListener('click', () => {
        openModal('addCustomerModal');
    });
    
    document.getElementById('newPaymentBtn')?.addEventListener('click', () => {
        openModal('newPaymentModal');
        loadCustomersForPayment();
    });
    
    // Close modals
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close on outside click
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modal) {
    modal.classList.remove('active');
}

// Forms
function initializeForms() {
    // Add Product Form
    document.getElementById('saveProductBtn')?.addEventListener('click', function() {
        saveProduct();
    });
    
    // Add Customer Form
    document.getElementById('saveCustomerBtn')?.addEventListener('click', function() {
        saveCustomer();
    });
    
    // New Sale Form
    document.getElementById('addProductToSale')?.addEventListener('click', function() {
        addProductToSale();
    });
    
    document.getElementById('saveSaleBtn')?.addEventListener('click', function() {
        saveSale();
    });
    
    // Payment Method Selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            
            const selectedMethod = this.getAttribute('data-method');
            document.getElementById('selectedPaymentMethod').value = selectedMethod;
            
            // Show/hide phone number field
            const phoneGroup = document.getElementById('phoneNumberGroup');
            if (selectedMethod === 'mtn_mobile_money' || selectedMethod === 'airtel_money') {
                phoneGroup.style.display = 'block';
            } else {
                phoneGroup.style.display = 'none';
            }
        });
    });
    
    // Save Payment
    document.getElementById('savePaymentBtn')?.addEventListener('click', function() {
        savePayment();
    });
    
    // Search functionality
    document.getElementById('searchProductBtn')?.addEventListener('click', function() {
        searchProducts();
    });
    
    document.getElementById('searchOrderBtn')?.addEventListener('click', function() {
        searchOrders();
    });
    
    document.getElementById('searchCustomerBtn')?.addEventListener('click', function() {
        searchCustomers();
    });
}

// Charts
function initializeCharts() {
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Sales (UGX)',
                    data: [12000000, 19000000, 15000000, 25000000, 22000000, 30000000, 28000000],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'UGX ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });
    }
}

// Data Loading Functions
function loadDashboardData() {
    // Load dashboard statistics
    // This would typically fetch from API
    console.log('Loading dashboard data...');
}

function loadProducts() {
    // Simulate loading products
    products = [
        {
            product_id: 1,
            product_code: 'LAP001',
            product_name: 'Ceramic Lap',
            category_name: 'Laps',
            quantity_in_stock: 150,
            unit_price: 25000,
            status: 'In Stock'
        },
        {
            product_id: 2,
            product_code: 'TOI001',
            product_name: 'Standard Toilet',
            category_name: 'Toilet',
            quantity_in_stock: 45,
            unit_price: 180000,
            status: 'In Stock'
        },
        {
            product_id: 3,
            product_code: 'BAS001',
            product_name: 'Wash Basin',
            category_name: 'Basins',
            quantity_in_stock: 8,
            unit_price: 65000,
            status: 'Low Stock'
        },
        {
            product_id: 4,
            product_code: 'SHW001',
            product_name: 'Shower Mixer',
            category_name: 'Shower Mixer',
            quantity_in_stock: 0,
            unit_price: 120000,
            status: 'Out of Stock'
        }
    ];
    
    renderProductsTable();
}

function renderProductsTable() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>${product.product_code}</td>
            <td>${product.product_name}</td>
            <td>${product.category_name}</td>
            <td>${product.quantity_in_stock}</td>
            <td>UGX ${product.unit_price.toLocaleString()}</td>
            <td><span class="badge ${getStatusBadgeClass(product.status)}">${product.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editProduct(${product.product_id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.product_id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'In Stock': return 'badge-success';
        case 'Low Stock': return 'badge-warning';
        case 'Out of Stock': return 'badge-danger';
        default: return 'badge-info';
    }
}

function loadOrders() {
    // Simulate loading orders
    orders = [
        {
            order_id: 1,
            order_number: 'SHH20240115001',
            customer_name: 'John Doe',
            order_date: '2024-01-15',
            total_amount: 250000,
            payment_method: 'mtn_mobile_money',
            payment_status: 'paid',
            order_status: 'completed'
        },
        {
            order_id: 2,
            order_number: 'SHH20240115002',
            customer_name: 'Jane Smith',
            order_date: '2024-01-15',
            total_amount: 180000,
            payment_method: 'cash',
            payment_status: 'pending',
            order_status: 'processing'
        }
    ];
    
    renderOrdersTable();
}

function renderOrdersTable() {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${order.order_date}</td>
            <td>UGX ${order.total_amount.toLocaleString()}</td>
            <td>${formatPaymentMethod(order.payment_method)}</td>
            <td><span class="badge ${getOrderStatusBadgeClass(order.order_status)}">${order.order_status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewOrder(${order.order_id})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="printOrder(${order.order_id})">
                    <i class="fas fa-print"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function formatPaymentMethod(method) {
    const methods = {
        'cash': 'Cash',
        'mtn_mobile_money': 'MTN MM',
        'airtel_money': 'Airtel MM',
        'bank_transfer': 'Bank',
        'credit': 'Credit'
    };
    return methods[method] || method;
}

function getOrderStatusBadgeClass(status) {
    switch(status) {
        case 'completed': return 'badge-success';
        case 'processing': return 'badge-info';
        case 'pending': return 'badge-warning';
        case 'cancelled': return 'badge-danger';
        default: return 'badge-info';
    }
}

function loadCustomers() {
    // Simulate loading customers
    customers = [
        {
            customer_id: 1,
            customer_code: 'CUST20240115001',
            first_name: 'John',
            last_name: 'Doe',
            phone_number: '0773586844',
            customer_type: 'retail',
            current_balance: 0
        },
        {
            customer_id: 2,
            customer_code: 'CUST20240115002',
            first_name: 'Jane',
            last_name: 'Smith',
            phone_number: '0704467880',
            customer_type: 'wholesale',
            current_balance: 50000
        }
    ];
    
    renderCustomersTable();
}

function renderCustomersTable() {
    const tbody = document.getElementById('customersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td>${customer.customer_code}</td>
            <td>${customer.first_name} ${customer.last_name}</td>
            <td>${customer.phone_number}</td>
            <td><span class="badge badge-primary">${customer.customer_type}</span></td>
            <td>UGX ${customer.current_balance.toLocaleString()}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.customer_id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="viewCustomer(${customer.customer_id})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function loadPayments() {
    // Simulate loading payments
    payments = [
        {
            payment_id: 1,
            payment_number: 'PAY20240115001',
            customer_name: 'John Doe',
            amount: 250000,
            payment_method: 'mtn_mobile_money',
            payment_status: 'completed',
            payment_date: '2024-01-15'
        },
        {
            payment_id: 2,
            payment_number: 'PAY20240115002',
            customer_name: 'Jane Smith',
            amount: 180000,
            payment_method: 'cash',
            payment_status: 'pending',
            payment_date: '2024-01-15'
        }
    ];
    
    renderPaymentsTable();
}

function renderPaymentsTable() {
    const tbody = document.getElementById('paymentsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = payments.map(payment => `
        <tr>
            <td>${payment.payment_number}</td>
            <td>${payment.customer_name}</td>
            <td>UGX ${payment.amount.toLocaleString()}</td>
            <td>${formatPaymentMethod(payment.payment_method)}</td>
            <td><span class="badge ${getPaymentStatusBadgeClass(payment.payment_status)}">${payment.payment_status}</span></td>
            <td>${payment.payment_date}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewPayment(${payment.payment_id})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getPaymentStatusBadgeClass(status) {
    switch(status) {
        case 'completed': return 'badge-success';
        case 'pending': return 'badge-warning';
        case 'failed': return 'badge-danger';
        default: return 'badge-info';
    }
}

function loadAccountingData() {
    console.log('Loading accounting data...');
}

// CRUD Operations
function saveProduct() {
    const form = document.getElementById('addProductForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Create product object
    const product = {
        product_code: formData.get('product_code'),
        product_name: formData.get('product_name'),
        category_id: formData.get('category_id'),
        unit_price: parseFloat(formData.get('unit_price')),
        cost_price: parseFloat(formData.get('cost_price')),
        quantity_in_stock: parseInt(formData.get('quantity_in_stock')),
        reorder_level: parseInt(formData.get('reorder_level')),
        unit_of_measure: formData.get('unit_of_measure'),
        brand: formData.get('brand'),
        barcode: formData.get('barcode'),
        description: formData.get('description')
    };
    
    // Simulate API call
    console.log('Saving product:', product);
    
    // Add to local array
    products.push({
        ...product,
        product_id: products.length + 1,
        category_name: 'New Category',
        status: product.quantity_in_stock > 0 ? 'In Stock' : 'Out of Stock'
    });
    
    // Refresh table
    renderProductsTable();
    
    // Close modal
    closeModal(document.getElementById('addProductModal'));
    
    // Reset form
    form.reset();
    
    // Show success message
    alert('Product saved successfully!');
}

function saveCustomer() {
    const form = document.getElementById('addCustomerForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        alert('Please fill in all required fields');
        return;
    }
    
    const customer = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        phone_number: formData.get('phone_number'),
        email: formData.get('email'),
        customer_type: formData.get('customer_type'),
        credit_limit: parseFloat(formData.get('credit_limit')),
        address: formData.get('address'),
        city: formData.get('city'),
        country: formData.get('country')
    };
    
    console.log('Saving customer:', customer);
    
    customers.push({
        ...customer,
        customer_id: customers.length + 1,
        customer_code: 'CUST' + Date.now(),
        current_balance: 0
    });
    
    renderCustomersTable();
    closeModal(document.getElementById('addCustomerModal'));
    form.reset();
    alert('Customer saved successfully!');
}

function addProductToSale() {
    // This would open a product selection modal
    // For now, we'll add a sample product
    const product = {
        product_id: 1,
        product_name: 'Ceramic Lap',
        unit_price: 25000,
        quantity: 1
    };
    
    saleProducts.push(product);
    renderSaleProductsTable();
    updateSaleTotals();
}

function renderSaleProductsTable() {
    const tbody = document.getElementById('saleProductsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = saleProducts.map((product, index) => `
        <tr>
            <td>${product.product_name}</td>
            <td>
                <input type="number" value="${product.quantity}" min="1" 
                       onchange="updateSaleProductQuantity(${index}, this.value)">
            </td>
            <td>UGX ${product.unit_price.toLocaleString()}</td>
            <td>UGX ${(product.quantity * product.unit_price).toLocaleString()}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeSaleProduct(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function updateSaleProductQuantity(index, quantity) {
    saleProducts[index].quantity = parseInt(quantity);
    renderSaleProductsTable();
    updateSaleTotals();
}

function removeSaleProduct(index) {
    saleProducts.splice(index, 1);
    renderSaleProductsTable();
    updateSaleTotals();
}

function updateSaleTotals() {
    const subtotal = saleProducts.reduce((sum, p) => sum + (p.quantity * p.unit_price), 0);
    const discount = parseFloat(document.getElementById('saleDiscount').value) || 0;
    const tax = (subtotal - discount) * 0.18;
    const total = subtotal - discount + tax;
    
    document.getElementById('saleSubtotal').value = 'UGX ' + subtotal.toLocaleString();
    document.getElementById('saleTax').value = 'UGX ' + tax.toLocaleString();
    document.getElementById('saleTotal').value = 'UGX ' + total.toLocaleString();
}

function saveSale() {
    const form = document.getElementById('newSaleForm');
    const formData = new FormData(form);
    
    if (saleProducts.length === 0) {
        alert('Please add at least one product to the sale');
        return;
    }
    
    if (!formData.get('customer_id')) {
        alert('Please select a customer');
        return;
    }
    
    const sale = {
        customer_id: formData.get('customer_id'),
        payment_method: formData.get('payment_method'),
        items: saleProducts,
        subtotal: saleProducts.reduce((sum, p) => sum + (p.quantity * p.unit_price), 0),
        discount: parseFloat(document.getElementById('saleDiscount').value) || 0,
        tax: (saleProducts.reduce((sum, p) => sum + (p.quantity * p.unit_price), 0) - 
              (parseFloat(document.getElementById('saleDiscount').value) || 0)) * 0.18,
        notes: formData.get('notes')
    };
    
    sale.total = sale.subtotal - sale.discount + sale.tax;
    
    console.log('Saving sale:', sale);
    
    // Reset sale products
    saleProducts = [];
    renderSaleProductsTable();
    updateSaleTotals();
    
    closeModal(document.getElementById('newSaleModal'));
    form.reset();
    
    alert('Sale completed successfully!');
    loadOrders();
}

function savePayment() {
    const form = document.getElementById('newPaymentForm');
    const formData = new FormData(form);
    
    if (!formData.get('customer_id')) {
        alert('Please select a customer');
        return;
    }
    
    if (!formData.get('payment_method')) {
        alert('Please select a payment method');
        return;
    }
    
    const payment = {
        customer_id: formData.get('customer_id'),
        order_id: formData.get('order_id'),
        amount: parseFloat(formData.get('amount')),
        payment_method: formData.get('payment_method'),
        phone_number: formData.get('phone_number'),
        transaction_reference: formData.get('transaction_reference'),
        notes: formData.get('notes')
    };
    
    console.log('Processing payment:', payment);
    
    closeModal(document.getElementById('newPaymentModal'));
    form.reset();
    
    alert('Payment processed successfully!');
    loadPayments();
}

// Helper Functions
function loadCustomersForSale() {
    const select = document.querySelector('#newSaleForm select[name="customer_id"]');
    if (select) {
        select.innerHTML = '<option value="">Select Customer</option>' + 
            customers.map(c => `<option value="${c.customer_id}">${c.first_name} ${c.last_name}</option>`).join('');
    }
}

function loadCustomersForPayment() {
    const select = document.querySelector('#newPaymentForm select[name="customer_id"]');
    if (select) {
        select.innerHTML = '<option value="">Select Customer</option>' + 
            customers.map(c => `<option value="${c.customer_id}">${c.first_name} ${c.last_name}</option>`).join('');
    }
}

function searchProducts() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    const filtered = products.filter(p => 
        p.product_name.toLowerCase().includes(searchTerm) ||
        p.product_code.toLowerCase().includes(searchTerm)
    );
    
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = filtered.map(product => `
        <tr>
            <td>${product.product_code}</td>
            <td>${product.product_name}</td>
            <td>${product.category_name}</td>
            <td>${product.quantity_in_stock}</td>
            <td>UGX ${product.unit_price.toLocaleString()}</td>
            <td><span class="badge ${getStatusBadgeClass(product.status)}">${product.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editProduct(${product.product_id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.product_id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function searchOrders() {
    const searchTerm = document.getElementById('orderSearch').value.toLowerCase();
    const filtered = orders.filter(o => 
        o.order_number.toLowerCase().includes(searchTerm) ||
        o.customer_name.toLowerCase().includes(searchTerm)
    );
    
    const tbody = document.getElementById('ordersTableBody');
    tbody.innerHTML = filtered.map(order => `
        <tr>
            <td>${order.order_number}</td>
            <td>${order.customer_name}</td>
            <td>${order.order_date}</td>
            <td>UGX ${order.total_amount.toLocaleString()}</td>
            <td>${formatPaymentMethod(order.payment_method)}</td>
            <td><span class="badge ${getOrderStatusBadgeClass(order.order_status)}">${order.order_status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewOrder(${order.order_id})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="printOrder(${order.order_id})">
                    <i class="fas fa-print"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function searchCustomers() {
    const searchTerm = document.getElementById('customerSearch').value.toLowerCase();
    const filtered = customers.filter(c => 
        c.first_name.toLowerCase().includes(searchTerm) ||
        c.last_name.toLowerCase().includes(searchTerm) ||
        c.phone_number.includes(searchTerm)
    );
    
    const tbody = document.getElementById('customersTableBody');
    tbody.innerHTML = filtered.map(customer => `
        <tr>
            <td>${customer.customer_code}</td>
            <td>${customer.first_name} ${customer.last_name}</td>
            <td>${customer.phone_number}</td>
            <td><span class="badge badge-primary">${customer.customer_type}</span></td>
            <td>UGX ${customer.current_balance.toLocaleString()}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.customer_id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="viewCustomer(${customer.customer_id})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Placeholder functions for edit/delete/view operations
function editProduct(id) {
    console.log('Edit product:', id);
    alert('Edit product functionality - ID: ' + id);
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        console.log('Delete product:', id);
        alert('Product deleted successfully!');
    }
}

function viewOrder(id) {
    console.log('View order:', id);
    alert('View order details - ID: ' + id);
}

function printOrder(id) {
    console.log('Print order:', id);
    alert('Print order - ID: ' + id);
}

function editCustomer(id) {
    console.log('Edit customer:', id);
    alert('Edit customer functionality - ID: ' + id);
}

function viewCustomer(id) {
    console.log('View customer:', id);
    alert('View customer details - ID: ' + id);
}

function viewPayment(id) {
    console.log('View payment:', id);
    alert('View payment details - ID: ' + id);
}

// Logout functionality
document.getElementById('logoutBtn')?.addEventListener('click', function() {
    if (confirm('Are you sure you want to logout?')) {
        // Redirect to login page
        window.location.href = 'login.html';
    }
});