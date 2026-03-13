<?php
// index.php - Simplified E-Commerce Shop with Cart & Display

session_start();

// --- CONFIG & CONNECTION ---
$host = "localhost";
$user = "root";
$password = "";
$database = "computer_shop";

// Attempt database connection
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- USER STATUS ---
$messages = [];
// Check for and display messages passed via session (from login/register/logout)
if (isset($_SESSION['messages'])) {
    $messages = $_SESSION['messages'];
    unset($_SESSION['messages']); // Clear messages after displaying
}

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $_SESSION['user_id'] ?? null;

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);

    // 1. AUTHENTICATION ACTIONS (Logout is simple, keep it here)
    if ($action === 'logout') {
        session_unset();
        session_destroy();
        $_SESSION['messages'] = [['type' => 'success', 'text' => 'You have been logged out.']];
        header("Location: index.php"); // Refresh to show logged-out state
        exit();
    }

    // 2. CART AND CHECKOUT ACTIONS (Only for logged-in users)
    elseif ($is_logged_in) {
        if ($action === 'add' && $product_id > 0) {
            $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $current_user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $new_quantity = $row['quantity'] + 1;
                $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $update_stmt->bind_param("iii", $new_quantity, $current_user_id, $product_id);
                $update_stmt->execute();
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $insert_stmt->bind_param("ii", $current_user_id, $product_id);
                $insert_stmt->execute();
            }
            $stmt->close();
            $_SESSION['messages'] = [['type' => 'success', 'text' => 'Product added to cart.']];
        }

        elseif ($action === 'remove' && $product_id > 0) {
            $delete_stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            $delete_stmt->bind_param("ii", $current_user_id, $product_id);
            $delete_stmt->execute();
            $_SESSION['messages'] = [['type' => 'success', 'text' => 'Product removed from cart.']];
        }

        elseif ($action === 'checkout') {
             // TRANSACTION START
             $conn->begin_transaction();
             try {
                $cart_stmt = $conn->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ? FOR UPDATE");
                $cart_stmt->bind_param("i", $current_user_id);
                $cart_stmt->execute();
                $cart_result = $cart_stmt->get_result();
                
                $items_checked_out = 0;
                while ($item = $cart_result->fetch_assoc()) {
                    // Deduct stock safely
                    $update = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
                    $update->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                    $update->execute();
                    if ($update->affected_rows === 0) {
                        throw new Exception("Stock low for product " . $item['product_id']);
                    }
                    $items_checked_out++;
                }
                
                if ($items_checked_out === 0) {
                    throw new Exception("Your cart is empty.");
                }

                // Clear the cart
                $clear = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $clear->bind_param("i", $current_user_id);
                $clear->execute();
                
                $conn->commit();
                $_SESSION['messages'] = [['type' => 'success', 'text' => 'Checkout successful!']];

             } catch (Exception $e) {
                 $conn->rollback();
                 $_SESSION['messages'] = [['type' => 'error', 'text' => "Checkout failed: " . $e->getMessage()]];
             }
        }
    
        // Redirect after successful cart/checkout POSTs to prevent form resubmission
        header("Location: index.php");
        exit();
    } else {
         // Prevent non-logged-in users from trying to do cart actions
         $_SESSION['messages'] = [['type' => 'error', 'text' => "You must be logged in to perform this action."]];
         header("Location: index.php");
         exit();
    }
}

// --- FETCH DATA FOR DISPLAY ---
$products = [];
$product_query = "SELECT product_id, name, category, price, stock_quantity, description FROM products WHERE stock_quantity > 0 ORDER BY price DESC";
$product_result = $conn->query($product_query);
while ($row = $product_result->fetch_assoc()) {
    $products[] = $row;
}

$cart_items = [];
$cart_total = 0;

if ($is_logged_in) {
    $cart_query = "
        SELECT ci.product_id, ci.quantity, p.name, p.price
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.user_id = ?
    ";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    while ($row = $cart_result->fetch_assoc()) {
        $row['line_total'] = $row['quantity'] * $row['price'];
        $cart_total += $row['line_total'];
        $cart_items[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Simple E-Commerce Shop</title>
    <link rel="stylesheet" href="tail/src/output.css" />
<style>
    body {
        background-image: url('images/Cover/test.jpg');
        background-size: cover;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
</style>
</head>
<body class="p-4 md:p-8">
<div class="max-w-7xl mx-auto">
<header class="text-center mb-12 bg-cyan-100 p-6 rounded-lg shadow-sm">
<h1 class="text-4xl md:text-5xl font-extrabold text-red-700">Apex Tech</h1>
<p class="text-md md:text-xl text-gray-600 mt-2">Where All your Computer Components Are Here</p>
</header>

<?php foreach($messages as $msg): ?>
<div class="mb-4 p-4 rounded-lg <?php echo $msg['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>" role="alert">
    <?php echo htmlspecialchars($msg['text']); ?>
</div>
<?php endforeach; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

<div class="lg:col-span-2">
<h2 class="text-3xl font-bold text-red-800 mb-6 border-b pb-2">Products</h2>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<?php foreach($products as $product): ?>
<div class="card bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
    <!-- Product Image from File System -->
    <div class="product-image-container bg-gray-100 flex items-center justify-center overflow-hidden">
        <?php
        // Check for image in different formats
        $image_paths = [
            "images/products/" . $product['product_id'] . ".jpg",
            "images/products/" . $product['product_id'] . ".jpeg",
            "images/products/" . $product['product_id'] . ".png",
            "images/products/" . $product['product_id'] . ".webp"
        ];
        
        $image_found = false;
        $actual_image_path = "";
        
        foreach ($image_paths as $path) {
            if (file_exists($path)) {
                $image_found = true;
                $actual_image_path = $path;
                break;
            }
        }
        
        if ($image_found): ?>
            <img src="<?php echo $actual_image_path; ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 class="w-full h-full object-contain p-4 hover:scale-105 transition-transform duration-300">
        <?php else: ?>
            <!-- Fallback placeholder if no image found -->
            <div class="text-center text-gray-400 p-4">
                <svg class="w-20 h-20 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-sm"><?php echo htmlspecialchars($product['name']); ?></p>
                <p class="text-xs mt-1">Image coming soon</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="p-6">
        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full text-indigo-800 bg-indigo-100 mb-2">
            <?php echo htmlspecialchars($product['category']); ?>
        </span>
        <h3 class="text-xl font-bold mb-2 text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h3>
        
        <?php if (!empty($product['description'])): ?>
        <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars($product['description']); ?></p>
        <?php endif; ?>
        
        <p class="text-2xl font-extrabold text-red-600 mb-3"><?php echo number_format($product['price'], 2); ?> EGP</p>
        <p class="text-sm text-gray-500 mb-4">
            Stock: 
            <span class="font-bold text-black">
                <?php echo $product['stock_quantity']; ?> available
            </span>
        </p>

        <?php if ($is_logged_in): ?>
        <form method="POST" action="index.php">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition duration-200">
            Add to Cart
        </button>
        </form>
        <?php else: ?>
        <div class="text-center">
            <button class="w-full bg-gray-400 text-white py-2 rounded-lg font-semibold text-sm cursor-not-allowed" disabled>
                Log in to Add to Cart
            </button>
            <p class="text-xs text-gray-500 mt-2">Login or register to start shopping</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="lg:col-span-1">
<div class="bg-white p-6 rounded-lg shadow-xl border border-cyan-500 mb-6">
    <h2 class="text-2xl font-bold text-gray-700 mb-4">About Us</h2>
    <p class="text-gray-600 mb-4">Learn more about our mission and commitment to quality.</p>
    <div class="space-y-4">
        <a href="about.html" 
           target="_blank" 
           class="block w-full text-center bg-cyan-500 text-white py-3 rounded-lg font-bold hover:bg-cyan-600 transition">
            About Us
        </a>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-xl border border-yellow-300 mb-6">
    <h2 class="text-2xl font-bold text-gray-700 mb-4">Need Our Help?</h2>
    <p class="text-gray-600 mb-4">Have questions? Our team is here to assist you.</p>
    <div class="space-y-4">
        <a href="contact.html" 
           target="_blank" 
           class="block w-full text-center bg-yellow-500 text-white py-3 rounded-lg font-bold hover:bg-yellow-600 transition">
            Contact Us
        </a>
    </div>
</div>

<div class="sticky top-8 bg-white p-6 rounded-lg shadow-xl border border-indigo-200">
<?php if (!$is_logged_in): ?>
    
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Account Access</h2>
    <p class="text-gray-600 mb-4">Login or create an account to shop and manage your cart.</p>
    <div class="space-y-4">
        <a href="login.html" class="block w-full text-center bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
            Login
        </a>
        <a href="register.html" class="block w-full text-center bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition">
            Register
        </a>
    </div>

<?php else: ?>
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Your Cart 
        <span class="text-sm font-normal text-gray-500">(User #<?php echo $current_user_id; ?>)</span>
    </h2>
    
    <?php if (count($cart_items) > 0): ?>
    <ul class="space-y-4 mb-6 max-h-80 overflow-y-auto pr-2">
    <?php foreach($cart_items as $item): ?>
    <li class="flex justify-between items-center border-b pb-3">
        <div class="flex-1">
            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
            <p class="text-sm text-gray-500"><?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></p>
        </div>
        <div class="text-right ml-4">
            <p class="font-bold text-lg text-indigo-600">$<?php echo number_format($item['line_total'], 2); ?></p>
            <form method="POST" action="index.php" class="mt-1">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                <button type="submit" class="text-xs text-red-500 hover:text-red-700 hover:underline">
                    Remove
                </button>
            </form>
        </div>
    </li>
    <?php endforeach; ?>
    </ul>

    <div class="flex justify-between items-center pt-4 border-t-2 border-indigo-300">
        <p class="text-xl font-bold text-gray-700">Cart Total</p>
        <p class="text-3xl font-extrabold text-red-600">$<?php echo number_format($cart_total, 2); ?></p>
    </div>

    <form method="POST" action="index.php" class="mt-6">
        <input type="hidden" name="action" value="checkout">
        <button class="w-full bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition duration-200">
            Proceed to Checkout
        </button>
    </form>

    <?php else: ?>
    <div class="text-center py-8 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-lg font-medium">Your cart is empty</p>
        <p class="text-sm mt-2">Add some products from the store!</p>
    </div>
    <?php endif; ?>

    <form method="POST" action="index.php" class="mt-6">
        <input type="hidden" name="action" value="logout">
        <button class="w-full bg-red-500 text-white py-2 rounded-lg font-bold hover:bg-red-600 transition duration-200">
            Logout
        </button>
    </form>
<?php endif; ?>
</div>
</div>

</div>

<footer class="mt-12 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
    <p>© 2024 Apex Tech. All rights reserved.</p>
    <p class="mt-2">Computer Components & Accessories Shop</p>
</footer>

</div>
</body>
</html>