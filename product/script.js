document.addEventListener('DOMContentLoaded', function() {
    // ----- NAVIGATION & UI FUNCTIONALITY -----
    
    // Side navigation functionality
    const sideNavToggle = document.querySelector('.menu-toggle');
    const sideNav = document.querySelector('.side-nav');
    const sideNavBackdrop = document.querySelector('.side-nav-backdrop');
    const sideNavClose = document.querySelector('.close-sidenav');
    
    if(sideNavToggle) {
        sideNavToggle.addEventListener('click', () => {
            sideNav.classList.add('open');
            sideNavBackdrop.classList.add('active');
            document.body.classList.add('no-scroll');
        });
    }
    
    function closeSideNav() {
        sideNav.classList.remove('open');
        sideNavBackdrop.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }
    
    if(sideNavClose) sideNavClose.addEventListener('click', closeSideNav);
    if(sideNavBackdrop) sideNavBackdrop.addEventListener('click', closeSideNav);
    
    // Back to top button
    const backToTopBtn = document.querySelector('.back-to-top');
    
    if(backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // ----- ORDER MANAGEMENT -----
    
    // Add to order functionality
    const addToOrderBtns = document.querySelectorAll('.btn-add-to-order');
    const orderCount = document.querySelector('.order-count');
    const notificationsContainer = document.getElementById('notifications-container');
    
    // Load orders from localStorage
    let orders = JSON.parse(localStorage.getItem('myOrders')) || [];
    
    // Update order count initially
    if(orderCount) updateOrderCount();
    
    // Add click event to all "Add to Order" buttons
    if(addToOrderBtns.length > 0) {
        addToOrderBtns.forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const name = button.dataset.name;
                const price = parseFloat(button.dataset.price);
                const image = button.dataset.image;
                
                // Check if item already in orders
                const existingItem = orders.find(item => item.id === id);
                
                if (existingItem) {
                    // Increase quantity
                    existingItem.quantity += 1;
                } else {
                    // Add new item
                    orders.push({
                        id: id,
                        name: name,
                        price: price,
                        image: image,
                        quantity: 1,
                        customization: {} // Empty object to store customizations later
                    });
                }
                
                // Save to localStorage
                localStorage.setItem('myOrders', JSON.stringify(orders));
                
                // Update order count
                updateOrderCount();
                
                // Create and display notification
                createNotification(id, name, image);
            });
        });
    }
    
    // Create notification for a product
    function createNotification(id, productName, productImage) {
        if(!notificationsContainer) return;
        
        // Check if notification for this product already exists
        const existingNotification = document.getElementById(`notification-${id}`);
        if (existingNotification) {
            // Update quantity instead of creating a new notification
            const quantityEl = existingNotification.querySelector('.product-quantity');
            const currentQuantity = parseInt(quantityEl.textContent.split(' ')[1]);
            quantityEl.textContent = `Qty: ${currentQuantity + 1}`;
            
            // Briefly highlight the notification to show it was updated
            existingNotification.classList.add('highlight');
            setTimeout(() => {
                existingNotification.classList.remove('highlight');
            }, 1000);
            
            return;
        }
        
        // Create new notification
        const notification = document.createElement('div');
        notification.className = 'side-notification';
        notification.id = `notification-${id}`;
        notification.innerHTML = `
            <div class="notification-product">
                <img src="images/${productImage}" alt="${productName}" class="product-thumbnail">
                <div class="product-details">
                    <p class="product-name">${productName}</p>
                    <p class="product-quantity">Qty: 1</p>
                </div>
            </div>
            <div class="notification-action">
                <button class="customize-btn">Add Customization</button>
            </div>
        `;
        
        // Make entire notification clickable
        notification.addEventListener('click', function(e) {
            if (!e.target.classList.contains('customize-btn')) {
                window.location.href = 'myorder.html';
            }
        });
        
        // Add event listener to customize button
        const customizeBtn = notification.querySelector('.customize-btn');
        customizeBtn.addEventListener('click', () => {
            window.location.href = 'myorder.html';
        });
        
        // Add to notifications container
        notificationsContainer.appendChild(notification);
        
        // Add entrance animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
    }
    
    // Update order count
    function updateOrderCount() {
        if(!orderCount) return;
        
        let count = 0;
        orders.forEach(item => {
            count += item.quantity;
        });
        orderCount.textContent = count;
    }
    
    // ----- MY ORDER PAGE FUNCTIONALITY -----
    
    // If we're on the My Order page, display the orders
    const myOrderContainer = document.getElementById('my-order-container');
    if(myOrderContainer) {
        displayOrders();
    }
    
    // Function to display orders on the My Order page
    function displayOrders() {
        if(orders.length === 0) {
            myOrderContainer.innerHTML = `
                <div class="empty-order">
                    <i class="fas fa-shopping-bag empty-icon"></i>
                    <h3>Your order is empty</h3>
                    <p>Start shopping to add items to your order.</p>
                    <a href="order.html" class="btn-shop-now">Shop Now</a>
                </div>
            `;
            return;
        }
        
        let orderHTML = `
            <div class="order-items">
                <h2>My Order</h2>
                <div class="order-list">
        `;
        
        let totalAmount = 0;
        
        orders.forEach(item => {
            const itemTotal = item.price * item.quantity;
            totalAmount += itemTotal;
            
            orderHTML += `
                <div class="order-item" data-id="${item.id}">
                    <div class="item-image">
                        <img src="images/${item.image}" alt="${item.name}">
                    </div>
                    <div class="item-details">
                        <h3>${item.name}</h3>
                        <p class="item-price">PKR ${item.price.toLocaleString()}</p>
                        <div class="item-quantity">
                            <button class="btn-quantity-change decrease" data-id="${item.id}">-</button>
                            <span class="quantity-value">${item.quantity}</span>
                            <button class="btn-quantity-change increase" data-id="${item.id}">+</button>
                        </div>
                    </div>
                    <div class="item-total">
                        <p>PKR ${itemTotal.toLocaleString()}</p>
                        <button class="btn-remove" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
        });
        
        orderHTML += `
                </div>
            </div>
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>PKR ${totalAmount.toLocaleString()}</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>PKR 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>PKR ${totalAmount.toLocaleString()}</span>
                    </div>
                </div>
                <button class="btn-checkout">Proceed to Customize</button>
            </div>
        `;
        
        myOrderContainer.innerHTML = orderHTML;
        
        // Add event listeners for quantity changes and item removal
        document.querySelectorAll('.btn-quantity-change').forEach(button => {
            button.addEventListener('click', handleQuantityChange);
        });
        
        document.querySelectorAll('.btn-remove').forEach(button => {
            button.addEventListener('click', handleItemRemoval);
        });
        
        // Add event listener for checkout button
        const checkoutButton = document.querySelector('.btn-checkout');
        if(checkoutButton) {
            checkoutButton.addEventListener('click', () => {
                // You can implement checkout logic here
                alert('Redirecting to customization page...');
                // window.location.href = 'customize.html';
            });
        }
    }
    
    // Handle quantity changes on the My Order page
    function handleQuantityChange(e) {
        const id = e.target.dataset.id;
        const isIncrease = e.target.classList.contains('increase');
        
        const itemIndex = orders.findIndex(item => item.id === id);
        if (itemIndex === -1) return;
        
        if (isIncrease) {
            orders[itemIndex].quantity += 1;
        } else {
            orders[itemIndex].quantity -= 1;
            
            // Remove item if quantity is 0
            if (orders[itemIndex].quantity <= 0) {
                orders.splice(itemIndex, 1);
            }
        }
        
        // Save updated orders to localStorage
        localStorage.setItem('myOrders', JSON.stringify(orders));
        
        // Refresh the display
        displayOrders();
        updateOrderCount();
    }
    
    // Handle item removal from the My Order page
    function handleItemRemoval(e) {
        const id = e.target.closest('.btn-remove').dataset.id;
        
        const itemIndex = orders.findIndex(item => item.id === id);
        if (itemIndex === -1) return;
        
        // Remove the item
        orders.splice(itemIndex, 1);
        
        // Save updated orders to localStorage
        localStorage.setItem('myOrders', JSON.stringify(orders));
        
        // Refresh the display
        displayOrders();
        updateOrderCount();
    }
    
    // ----- SLIDER FUNCTIONALITY -----
    
    // Fabric type slider functionality
    const slider = document.querySelector('.fabric-slider');
    const prevBtn = document.querySelector('.slider-control.prev');
    const nextBtn = document.querySelector('.slider-control.next');
    
    if(slider && prevBtn && nextBtn) {
        const sliderItems = document.querySelectorAll('.slider-item');
        
        if(sliderItems.length > 0) {
            // Calculate the width to scroll
            const itemWidth = sliderItems[0].offsetWidth + 15; // 15px is the margin-right
            
            // Slide control buttons
            nextBtn.addEventListener('click', () => {
                slider.scrollBy({ left: itemWidth * 3, behavior: 'smooth' });
            });
            
            prevBtn.addEventListener('click', () => {
                slider.scrollBy({ left: -itemWidth * 3, behavior: 'smooth' });
            });
        }
    }
    
    // ----- SMOOTH SCROLLING -----
    
    // Smooth scrolling for section links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const section = document.querySelector(this.getAttribute('href'));
            if (section) {
                window.scrollTo({
                    top: section.offsetTop - 20,
                    behavior: 'smooth'
                });
            }
        });
    });
});