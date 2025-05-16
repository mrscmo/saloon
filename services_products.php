<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Process form submissions
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"])){
        switch($_POST["action"]){
            case "add_service":
                $sql = "INSERT INTO services (name, description, price, duration) VALUES (?, ?, ?, ?)";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "ssdi", 
                        $_POST["name"],
                        $_POST["description"],
                        $_POST["price"],
                        $_POST["duration"]
                    );
                    mysqli_stmt_execute($stmt);
                    header("location: services_products.php?success=1");
                    exit();
                }
                break;
                
            case "add_package":
                mysqli_begin_transaction($conn);
                try {
                    $sql = "INSERT INTO service_packages (name, description, price, duration, discount_percentage) 
                            VALUES (?, ?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "ssdid", 
                            $_POST["name"],
                            $_POST["description"],
                            $_POST["price"],
                            $_POST["duration"],
                            $_POST["discount_percentage"]
                        );
                        mysqli_stmt_execute($stmt);
                        $package_id = mysqli_insert_id($conn);
                        
                        // Add services to package
                        foreach($_POST["services"] as $service_id){
                            $sql = "INSERT INTO package_services (package_id, service_id) VALUES (?, ?)";
                            if($stmt = mysqli_prepare($conn, $sql)){
                                mysqli_stmt_bind_param($stmt, "ii", $package_id, $service_id);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                        
                        mysqli_commit($conn);
                        header("location: services_products.php?success=2");
                        exit();
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error creating package: " . $e->getMessage();
                }
                break;
                
            case "add_product":
                mysqli_begin_transaction($conn);
                try {
                    $sql = "INSERT INTO products (name, description, category, brand, unit_price, retail_price, sku, barcode) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "ssssddss", 
                            $_POST["name"],
                            $_POST["description"],
                            $_POST["category"],
                            $_POST["brand"],
                            $_POST["unit_price"],
                            $_POST["retail_price"],
                            $_POST["sku"],
                            $_POST["barcode"]
                        );
                        mysqli_stmt_execute($stmt);
                        $product_id = mysqli_insert_id($conn);
                        
                        // Initialize inventory
                        $sql = "INSERT INTO inventory (product_id, quantity, reorder_level, reorder_quantity) 
                                VALUES (?, ?, ?, ?)";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "iiii", 
                                $product_id,
                                $_POST["initial_quantity"],
                                $_POST["reorder_level"],
                                $_POST["reorder_quantity"]
                            );
                            mysqli_stmt_execute($stmt);
                        }
                        
                        mysqli_commit($conn);
                        header("location: services_products.php?success=3");
                        exit();
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error creating product: " . $e->getMessage();
                }
                break;
            
            case "edit_service":
                $sql = "UPDATE services SET name = ?, description = ?, price = ?, duration = ? WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "ssdii", 
                        $_POST["name"],
                        $_POST["description"],
                        $_POST["price"],
                        $_POST["duration"],
                        $_POST["id"]
                    );
                    mysqli_stmt_execute($stmt);
                    header("location: services_products.php?success=4");
                    exit();
                }
                break;
            
            case "edit_package":
                mysqli_begin_transaction($conn);
                try {
                    $sql = "UPDATE service_packages SET name = ?, description = ?, price = ?, duration = ?, discount_percentage = ? WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "ssdidi", 
                            $_POST["name"],
                            $_POST["description"],
                            $_POST["price"],
                            $_POST["duration"],
                            $_POST["discount_percentage"],
                            $_POST["id"]
                        );
                        mysqli_stmt_execute($stmt);
                        
                        // Update package services
                        $sql = "DELETE FROM package_services WHERE package_id = ?";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $_POST["id"]);
                            mysqli_stmt_execute($stmt);
                        }
                        
                        foreach($_POST["services"] as $service_id){
                            $sql = "INSERT INTO package_services (package_id, service_id) VALUES (?, ?)";
                            if($stmt = mysqli_prepare($conn, $sql)){
                                mysqli_stmt_bind_param($stmt, "ii", $_POST["id"], $service_id);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                        
                        mysqli_commit($conn);
                        header("location: services_products.php?success=5");
                        exit();
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error updating package: " . $e->getMessage();
                }
                break;
            
            case "edit_product":
                mysqli_begin_transaction($conn);
                try {
                    $sql = "UPDATE products SET name = ?, description = ?, category = ?, brand = ?, 
                            unit_price = ?, retail_price = ?, sku = ?, barcode = ?, status = ? WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "ssssddsssi", 
                            $_POST["name"],
                            $_POST["description"],
                            $_POST["category"],
                            $_POST["brand"],
                            $_POST["unit_price"],
                            $_POST["retail_price"],
                            $_POST["sku"],
                            $_POST["barcode"],
                            $_POST["status"],
                            $_POST["id"]
                        );
                        mysqli_stmt_execute($stmt);
                        
                        // Update inventory settings
                        $sql = "UPDATE inventory SET reorder_level = ?, reorder_quantity = ? WHERE product_id = ?";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "iii", 
                                $_POST["reorder_level"],
                                $_POST["reorder_quantity"],
                                $_POST["id"]
                            );
                            mysqli_stmt_execute($stmt);
                        }
                        
                        mysqli_commit($conn);
                        header("location: services_products.php?success=6");
                        exit();
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error updating product: " . $e->getMessage();
                }
                break;
            
            case "delete_service":
                $sql = "DELETE FROM services WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $_POST["id"]);
                    mysqli_stmt_execute($stmt);
                    header("location: services_products.php?success=7");
                    exit();
                }
                break;
            
            case "delete_package":
                $sql = "DELETE FROM service_packages WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $_POST["id"]);
                    mysqli_stmt_execute($stmt);
                    header("location: services_products.php?success=8");
                    exit();
                }
                break;
            
            case "delete_product":
                $sql = "DELETE FROM products WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $_POST["id"]);
                    mysqli_stmt_execute($stmt);
                    header("location: services_products.php?success=9");
                    exit();
                }
                break;
            
            case "adjust_stock":
                mysqli_begin_transaction($conn);
                try {
                    // Update inventory
                    $sql = "UPDATE inventory SET quantity = quantity + ? WHERE product_id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "ii", $_POST["quantity"], $_POST["id"]);
                        mysqli_stmt_execute($stmt);
                    }
                    
                    // Record transaction
                    $sql = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, unit_price, total_amount, notes) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        $transaction_type = $_POST["quantity"] > 0 ? "purchase" : "adjustment";
                        $unit_price = 0; // For adjustments, price is 0
                        $total_amount = 0;
                        mysqli_stmt_bind_param($stmt, "isidds", 
                            $_POST["id"],
                            $transaction_type,
                            $_POST["quantity"],
                            $unit_price,
                            $total_amount,
                            $_POST["notes"]
                        );
                        mysqli_stmt_execute($stmt);
                    }
                    
                    mysqli_commit($conn);
                    header("location: services_products.php?success=10");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Error adjusting stock: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all services
$services = array();
$sql = "SELECT * FROM services ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $services[] = $row;
}

// Fetch all packages
$packages = array();
$sql = "SELECT sp.*, 
        (SELECT COUNT(*) FROM package_services WHERE package_id = sp.id) as service_count
        FROM service_packages sp 
        ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $packages[] = $row;
}

// Fetch all products with inventory
$products = array();
$sql = "SELECT p.*, i.quantity, i.reorder_level, i.reorder_quantity 
        FROM products p 
        LEFT JOIN inventory i ON p.id = i.product_id 
        ORDER BY p.name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services & Products - Salon Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .nav-tabs {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white text-center mb-4">Salon Admin</h3>
                <nav>
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    <a href="customers.php"><i class="fas fa-users me-2"></i> Customers</a>
                    <a href="appointments.php"><i class="fas fa-calendar-alt me-2"></i> Appointments</a>
                    <a href="services_products.php" class="active"><i class="fas fa-cut me-2"></i> Services & Products</a>
                    <a href="staff.php"><i class="fas fa-user-tie me-2"></i> Staff</a>
                    <a href="billing.php"><i class="fas fa-file-invoice-dollar me-2"></i> Billing</a>
                    <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Services & Products Management</h2>
                </div>

                <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    <?php
                    switch($_GET["success"]){
                        case 1: echo "Service added successfully!"; break;
                        case 2: echo "Package added successfully!"; break;
                        case 3: echo "Product added successfully!"; break;
                        case 4: echo "Service updated successfully!"; break;
                        case 5: echo "Package updated successfully!"; break;
                        case 6: echo "Product updated successfully!"; break;
                        case 7: echo "Service deleted successfully!"; break;
                        case 8: echo "Package deleted successfully!"; break;
                        case 9: echo "Product deleted successfully!"; break;
                        case 10: echo "Stock adjusted successfully!"; break;
                    }
                    ?>
                </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="services-tab" data-bs-toggle="tab" href="#services" role="tab">
                            Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="packages-tab" data-bs-toggle="tab" href="#packages" role="tab">
                            Packages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="products-tab" data-bs-toggle="tab" href="#products" role="tab">
                            Products
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="myTabContent">
                    <!-- Services Tab -->
                    <div class="tab-pane fade show active" id="services" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Services</h4>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                <i class="fas fa-plus"></i> Add Service
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service["name"]); ?></td>
                                        <td><?php echo htmlspecialchars($service["description"]); ?></td>
                                        <td>$<?php echo number_format($service["price"], 2); ?></td>
                                        <td><?php echo $service["duration"]; ?> minutes</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary edit-service" 
                                                    data-id="<?php echo $service["id"]; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editServiceModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-service"
                                                    data-id="<?php echo $service["id"]; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Packages Tab -->
                    <div class="tab-pane fade" id="packages" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Service Packages</h4>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                                <i class="fas fa-plus"></i> Add Package
                            </button>
                        </div>
                        
                        <div class="row">
                            <?php foreach($packages as $package): ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($package["name"]); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($package["description"]); ?></p>
                                        <div class="mb-3">
                                            <p class="mb-1">
                                                <strong>Price:</strong> $<?php echo number_format($package["price"], 2); ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Duration:</strong> <?php echo $package["duration"]; ?> minutes
                                            </p>
                                            <p class="mb-1">
                                                <strong>Discount:</strong> <?php echo $package["discount_percentage"]; ?>%
                                            </p>
                                            <p class="mb-1">
                                                <strong>Services:</strong> <?php echo $package["service_count"]; ?> included
                                            </p>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary edit-package"
                                                    data-id="<?php echo $package["id"]; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info view-package"
                                                    data-id="<?php echo $package["id"]; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#viewPackageModal">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-package"
                                                    data-id="<?php echo $package["id"]; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Products Tab -->
                    <div class="tab-pane fade" id="products" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Products</h4>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>SKU</th>
                                        <th>Unit Price</th>
                                        <th>Retail Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product["name"]); ?></td>
                                        <td><?php echo htmlspecialchars($product["category"]); ?></td>
                                        <td><?php echo htmlspecialchars($product["brand"]); ?></td>
                                        <td><?php echo htmlspecialchars($product["sku"]); ?></td>
                                        <td>$<?php echo number_format($product["unit_price"], 2); ?></td>
                                        <td>$<?php echo number_format($product["retail_price"], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $product["quantity"] <= $product["reorder_level"] ? 'danger' : 'success'; ?>">
                                                <?php echo $product["quantity"]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product["status"] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($product["status"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary edit-product"
                                                    data-id="<?php echo $product["id"]; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editProductModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info view-product"
                                                    data-id="<?php echo $product["id"]; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#viewProductModal">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning adjust-stock"
                                                    data-id="<?php echo $product["id"]; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-product"
                                                    data-id="<?php echo $product["id"]; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_service">
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" name="duration" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Package Modal -->
    <div class="modal fade" id="addPackageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_package">
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" class="form-control" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" name="duration" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discount (%)</label>
                                    <input type="number" class="form-control" name="discount_percentage" 
                                           min="0" max="100" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Services</label>
                            <select class="form-select" name="services[]" multiple required>
                                <?php foreach($services as $service): ?>
                                <option value="<?php echo $service["id"]; ?>">
                                    <?php echo htmlspecialchars($service["name"]); ?> 
                                    ($<?php echo number_format($service["price"], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple services</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Package</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" class="form-control" name="category" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SKU</label>
                                    <input type="text" class="form-control" name="sku" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Price</label>
                                    <input type="number" class="form-control" name="unit_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Retail Price</label>
                                    <input type="number" class="form-control" name="retail_price" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Initial Quantity</label>
                                    <input type="number" class="form-control" name="initial_quantity" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Level</label>
                                    <input type="number" class="form-control" name="reorder_level" value="10" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Quantity</label>
                                    <input type="number" class="form-control" name="reorder_quantity" value="20" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_service">
                        <input type="hidden" name="id" id="edit_service_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_service_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_service_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="edit_service_price" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" name="duration" id="edit_service_duration" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <div class="modal fade" id="editPackageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_package">
                        <input type="hidden" name="id" id="edit_package_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_package_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_package_description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" class="form-control" name="price" id="edit_package_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" name="duration" id="edit_package_duration" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discount (%)</label>
                                    <input type="number" class="form-control" name="discount_percentage" id="edit_package_discount" 
                                           min="0" max="100" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Services</label>
                            <select class="form-select" name="services[]" id="edit_package_services" multiple required>
                                <?php foreach($services as $service): ?>
                                <option value="<?php echo $service["id"]; ?>">
                                    <?php echo htmlspecialchars($service["name"]); ?> 
                                    ($<?php echo number_format($service["price"], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple services</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Package</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_product">
                        <input type="hidden" name="id" id="edit_product_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_product_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" class="form-control" name="category" id="edit_product_category" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" id="edit_product_brand">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SKU</label>
                                    <input type="text" class="form-control" name="sku" id="edit_product_sku" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_product_description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Price</label>
                                    <input type="number" class="form-control" name="unit_price" id="edit_product_unit_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Retail Price</label>
                                    <input type="number" class="form-control" name="retail_price" id="edit_product_retail_price" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Level</label>
                                    <input type="number" class="form-control" name="reorder_level" id="edit_product_reorder_level" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Quantity</label>
                                    <input type="number" class="form-control" name="reorder_quantity" id="edit_product_reorder_quantity" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="edit_product_status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode" id="edit_product_barcode">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="services_products.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="adjust_stock">
                        <input type="hidden" name="id" id="adjust_stock_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <input type="number" class="form-control" id="current_stock" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Adjustment Quantity</label>
                            <input type="number" class="form-control" name="quantity" required>
                            <small class="text-muted">Enter positive number to add stock, negative to remove stock</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Adjust Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle service editing
        document.querySelectorAll('.edit-service').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                // Fetch service details and populate modal
                fetch(`get_service.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('edit_service_id').value = data.id;
                        document.getElementById('edit_service_name').value = data.name;
                        document.getElementById('edit_service_description').value = data.description;
                        document.getElementById('edit_service_price').value = data.price;
                        document.getElementById('edit_service_duration').value = data.duration;
                    });
            });
        });

        // Handle package editing
        document.querySelectorAll('.edit-package').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                // Fetch package details and populate modal
                fetch(`get_package.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('edit_package_id').value = data.id;
                        document.getElementById('edit_package_name').value = data.name;
                        document.getElementById('edit_package_description').value = data.description;
                        document.getElementById('edit_package_price').value = data.price;
                        document.getElementById('edit_package_duration').value = data.duration;
                        document.getElementById('edit_package_discount').value = data.discount_percentage;
                        
                        // Set selected services
                        const servicesSelect = document.getElementById('edit_package_services');
                        data.services.forEach(serviceId => {
                            const option = servicesSelect.querySelector(`option[value="${serviceId}"]`);
                            if(option) option.selected = true;
                        });
                    });
            });
        });

        // Handle product editing
        document.querySelectorAll('.edit-product').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                // Fetch product details and populate modal
                fetch(`get_product.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('edit_product_id').value = data.id;
                        document.getElementById('edit_product_name').value = data.name;
                        document.getElementById('edit_product_description').value = data.description;
                        document.getElementById('edit_product_category').value = data.category;
                        document.getElementById('edit_product_brand').value = data.brand;
                        document.getElementById('edit_product_sku').value = data.sku;
                        document.getElementById('edit_product_unit_price').value = data.unit_price;
                        document.getElementById('edit_product_retail_price').value = data.retail_price;
                        document.getElementById('edit_product_reorder_level').value = data.reorder_level;
                        document.getElementById('edit_product_reorder_quantity').value = data.reorder_quantity;
                        document.getElementById('edit_product_status').value = data.status;
                        document.getElementById('edit_product_barcode').value = data.barcode;
                    });
            });
        });

        // Handle stock adjustment
        document.querySelectorAll('.adjust-stock').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                // Fetch current stock and populate modal
                fetch(`get_product.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('adjust_stock_id').value = data.id;
                        document.getElementById('current_stock').value = data.quantity;
                    });
            });
        });

        // Handle service deletion
        document.querySelectorAll('.delete-service').forEach(button => {
            button.addEventListener('click', function() {
                if(confirm('Are you sure you want to delete this service?')){
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'services_products.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_service';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = this.dataset.id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Handle package deletion
        document.querySelectorAll('.delete-package').forEach(button => {
            button.addEventListener('click', function() {
                if(confirm('Are you sure you want to delete this package?')){
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'services_products.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_package';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = this.dataset.id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Handle product deletion
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                if(confirm('Are you sure you want to delete this product?')){
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'services_products.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_product';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = this.dataset.id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html> 