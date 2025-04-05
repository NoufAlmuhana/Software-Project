<?php
session_start();
$connection = new mysqli("localhost", "root", "root", "wisaldb");
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
$cart = json_decode($_POST['cartData'], true);
$total = floatval($_POST['total']);
$address = $_POST['address'];
$loyaltyPoints = intval($_POST['loyaltyPoints']);
$userID = $_SESSION['userID'] ?? 1; 

$stmt = $connection->prepare("INSERT INTO orders (totalAmount, status, orderDate, deliveryAddress, userID) VALUES (?, 'Completed', NOW(), ?, ?)");
$stmt->bind_param("dsi", $total, $address, $userID);
$stmt->execute();
$orderID = $stmt->insert_id;

$stmt2 = $connection->prepare("INSERT INTO contains (itemID, orderID, quantity) VALUES (?, ?, ?)");
foreach ($cart as $item) {
$stmt2->bind_param("iii", $item['id'], $orderID, $item['quantity']);
$stmt2->execute();
}

$stmt3 = $connection->prepare("UPDATE users SET LoyaltyPoints = LoyaltyPoints + ? WHERE userID = ?");
$stmt3->bind_param("ii", $loyaltyPoints, $userID);
$stmt3->execute();

    http_response_code(200);
    exit("Saved successfully.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" />
    <title>Cart</title>
    <style>

.header {
display: flex;
justify-content: space-between;
align-items: center;
background-color: #493E32;
padding: 15px 30px;
color: white;
}
.header .logo{
margin-left: 8%;
}
.text {
color: #C5A478;
margin-left: 5px;
}
.nav-menu a {
color: #ccc;
text-decoration: none;
margin: 0 15px;
position: relative;
}
.nav-menu a.active,
.nav-menu a:hover {
color: white;
font-weight: bold;
}
.nav-menu a.active::after,
.nav-menu a:hover::after {
content: "";
display: block;
width: 100%;
height: 2px;
background-color: #C5A478;
position: absolute;
bottom: -5px;
left: 0;
}
.icons{
width: 20%;
}
.icons i {
margin-left: 15px;
font-size: 18px;
cursor: pointer;
}
.icons a{
padding: 10px;
}
.icons svg{
width: 10%;
}
.icons svg path{
fill: white
}

        body {
            color: rgb(94, 39, 0);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: auto;
        }
        h1 {
            text-align: center;
        }
        .cart-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-item img {
            max-width: 100px;
            margin-right: 15px;
        }
        .total {
            font-weight: bold;
            margin-top: 20px;
        }
        .discount {
            color: green;
            font-weight: bold;
            margin-top: 10px;
        }
        .loyalty {
            display: none;
            margin-top: 20px;
        }
        .empty-cart-message {
            text-align: center;
            margin-top: 20px;
            font-size: 1.2em;
            color: #555;
        }
        .delete-button {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            margin-left: 10px;
        }
        .address-input {
            margin-top: 20px;
        }

        footer {
background-color: #382904;
color: white;
padding: 40px 20px;
text-align: center;
}
.footer-container {
display: flex;
justify-content: space-around;
flex-wrap: wrap;
}
.footer-section {
margin: 20px;
flex: 1;
min-width: 200px;
}
.footer-section h3 {
border-bottom: 2px solid #f9d7a1;
padding-bottom: 10px;
}
.footer-section a {
color: white;
text-decoration: none;
display: block;
margin: 5px 0;
transition: color 0.3s;
}
.footer-section a:hover {
color: #f3ddb9;
}
.social-icons {
margin: 10px 0;
}
.social-icons a {
margin: 0 10px;
}
.contact-info {
font-size: 0.9em;
}
.copyright {
margin-top: 20px;
font-size: 0.8em;
}
    </style>
</head>
<body>
  
    <header class="header">
        <div class="logo">
        <img src="images/logo copy.jpg" alt="logo" style="width: 100px;">
        </div>
        <nav class="nav-menu">
        <a href="index.html">Home</a>
        <a href="Gameorder.html">Game And Ordering</a>
        <a href="cafefood.html" class="active">Cafe And Food</a>
        <a href="Loyalty.html">Loyalty Program</a>
        </nav>
        <div class="icons">
        <a href="#" onclick="confirmLogout()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="30" height="30" fill="currentColor">
        <path d="M497.9 273L353.9 417c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9L434.1 272 320 159.9c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0L497.9 239c9.4 9.4 9.4 24.6 0 34zM160 32h64c13.3 0 24-10.7 24-24S237.3 0 224 0h-64C71.6 0 0 71.6 0 160v192c0 88.4 71.6 160 160 160h64c13.3 0 24-10.7 24-24s-10.7-24-24-24h-64c-61.9 0-112-50.1-112-112V160c0-61.9 50.1-112 112-112z"/>
        </svg>
        </a>
        
        <script>
        function confirmLogout() {
        let confirmAction = confirm("Are you sure you want to log out?");
        if (confirmAction) {
        window.location.href = "Main.html";
        }
        }
        </script>
        <a href="cart.php.html"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style="width: 11%;"><path d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg></a>
        </div></a>
        
        </header>
        
    <div class="container">
        <h1>Your Cart</h1>
        <div id="cartTable"></div>
        <p class="total">Total Price: <span id="totalPrice">0</span> Riyal</p>
        <p class="total">Shipping Fee: 30 Riyal</p>
        <p class="total">Final Total: <span id="finalTotal">0</span> Riyal</p>
        <div class="discount" id="discountMessage" style="display: none;"></div>
        <p class="loyalty"></p>
        <input type="text" id="deliveryAddress" class="address-input" placeholder="Enter your delivery address"/>
        <button onclick="redeemPoints()">Redeem Points</button>
        <button onclick="proceedToLoyalty()">Proceed to Checkout</button>
        <p class="empty-cart-message" id="emptyCartMessage" style="display: none;">
            Your cart is empty. Run to our games and café order pages and order some goodies!
        </p>
    </div>

    <footer>
        <div class="footer-container">
        <div class="footer-section">
        <h3>Quick Links</h3>
        <a href="index.html">Home</a>
        <a href="Gameorder.html">Game And Ordering</a>
        <a href="cafefood.html">Cafe And food</a>
        <a href="Regster.html">ٍRegister Page</a>
        <a href="Loyalty.html">Loyalty</a>
        </div>
        
        <div class="footer-section">
        <h3>Follow Us</h3>
        <div class="social-icons">
        <a href="#">Facebook</a>
        <a href="#">Twitter</a>
        <a href="#">Instagram</a>
        <a href="#">LinkedIn</a>
        </div>
        </div>
        <div class="footer-section">
        <h3>Contact Info</h3>
        <p class="contact-info">Phone: 123-456-7890</p>
        <p class="contact-info">Email: info@example.com</p>
        <p class="contact-info">Address: 123 Main St, City, Country</p>
        </div>
        </div>
        <div class="copyright">
        <p>©️ 2023 All Rights Reserved.</p>
        </div>
        </footer>

   <script>
    const shippingFee = 30;

    function updateCart() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartTable = document.getElementById('cartTable');
        let totalPrice = 0;
        cartTable.innerHTML = ''; 

        if (cart.length === 0) {
            document.getElementById('emptyCartMessage').style.display = 'block';
            document.getElementById('totalPrice').innerText = 0;
            document.getElementById('finalTotal').innerText = shippingFee; 
            document.getElementById('discountMessage').style.display = 'none'; 
            return;
        } else {
            document.getElementById('emptyCartMessage').style.display = 'none';
        }

        cart.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item';
            itemDiv.innerHTML = `
                <div style="display: flex; align-items: center;">
                    <img src="${item.image}" alt="${item.name}">
                    <div>
                        <h3>${item.name}</h3>
                        <p>Total: <span class="item-total">${item.totalPrice}</span> Riyal</p>
                        <label for="quantity-${index}">Quantity:</label>
                        <input type="number" id="quantity-${index}" class="quantity-input" value="${item.quantity}" min="1" onchange="updateQuantity(${index})">
                    </div>
                </div>
                <button class="delete-button" onclick="deleteItem(${index})">Delete</button>
            `;
            cartTable.appendChild(itemDiv);
            totalPrice += item.totalPrice;
        });

        const finalTotal = totalPrice + shippingFee;
        document.getElementById('totalPrice').innerText = totalPrice;
        document.getElementById('finalTotal').innerText = finalTotal;

        // Show loyalty points based on final total
        document.getElementById('loyaltyPoints').innerText = Math.floor(finalTotal / 2);
    }

    function updateQuantity(index) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const quantityInput = document.getElementById(`quantity-${index}`);
        const newQuantity = parseInt(quantityInput.value);
        cart[index].quantity = newQuantity;
        cart[index].totalPrice = cart[index].price * newQuantity; 
        localStorage.setItem('cart', JSON.stringify(cart)); 
        updateCart(); // Refresh the cart display
    }

    function deleteItem(index) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        cart.splice(index, 1); // Remove the item at the specified index
        localStorage.setItem('cart', JSON.stringify(cart)); 
        updateCart(); // Refresh the cart display
    }

    function redeemPoints() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    if (cart.length === 0) {
        alert("Your cart is empty. Please add items to your cart before redeeming points.");
        return;
    }

    const loyaltyPoints = parseInt(localStorage.getItem('loyaltyPoints')) || 0;
    const finalTotal = parseFloat(document.getElementById('finalTotal').innerText);
    
    const pointsToRedeem = Math.min(loyaltyPoints, Math.floor(finalTotal / 2)); 

    if (pointsToRedeem > 0) {
        const discount = pointsToRedeem * 0.5;  
        const newTotal = finalTotal - discount;

        document.getElementById('finalTotal').innerText = newTotal;
        document.getElementById('discountMessage').innerText = `You got a discount of ${discount} Riyal!`;
        document.getElementById('discountMessage').style.display = 'block';

        localStorage.setItem('loyaltyPoints', loyaltyPoints - pointsToRedeem);
    } else {
        alert("You have no loyalty points to redeem.");
    }
}

    function proceedToLoyalty() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const deliveryAddress = document.getElementById('deliveryAddress').value;

    if (cart.length === 0) {
        alert("Your cart is empty. Please add items to your cart before proceeding to checkout.");
        return;
    }

    if (!deliveryAddress) {
        alert("Please fill in your delivery address.");
        return;
    }

    const finalTotal = parseFloat(document.getElementById('finalTotal').innerText);
    const loyaltyPoints = Math.floor(finalTotal / 2); 
    const currentLoyaltyPoints = parseInt(localStorage.getItem('loyaltyPoints')) || 0;

  
    const newLoyaltyPoints = currentLoyaltyPoints + loyaltyPoints;

    localStorage.setItem('loyaltyPoints', newLoyaltyPoints);

    document.querySelector('.loyalty').style.display = 'block';
    alert("Checkout completed! Loyalty points earned: " + loyaltyPoints);

   
    localStorage.removeItem('cart');
    updateCart(); 


    document.getElementById('discountMessage').style.display = 'none';
    
    // Send data to PHP
const xhr = new XMLHttpRequest();
xhr.open("POST", "cart.php", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

const params = new URLSearchParams();
params.append("checkout", "1");
params.append("cartData", JSON.stringify(cart));
params.append("total", finalTotal);
params.append("address", deliveryAddress);
params.append("loyaltyPoints", loyaltyPoints);

xhr.onload = function() {
    if (xhr.status === 200) {
        console.log("Order saved to database:", xhr.responseText);
    } else {
        alert("Error saving order to database.");
    }
};
xhr.send(params.toString());
}

   
    window.onload = updateCart;
        
        

</script>


</body>
</html>
