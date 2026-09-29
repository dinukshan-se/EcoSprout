<?php

require_once "admin_auth.php";
require_once "db_connection.php";

function plantAdminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["plant_admin_token"])) {
    $_SESSION["plant_admin_token"] = bin2hex(random_bytes(32));
}

$currentRole = strtoupper(trim($_SESSION["role"] ?? "STAFF"));
$isAdministrator = $currentRole === "ADMIN";
$roleLabel = $isAdministrator ? "Administrator" : "Staff";
$pageError = "";

$categories = [];
$categoryResult = $conn->query("
    SELECT plant_category_id, category_name
    FROM plant_categories
    ORDER BY category_name
");
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
}

$editPlant = null;
$editId = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
if ($editId !== false && $editId !== null && $editId > 0) {
    $editStatement = $conn->prepare("
        SELECT plant_id, plant_category_id, plant_name, price, quantity,
               plant_size, light_requirement, pot_size, pot_color,
               pet_friendly, difficulty, air_cleaner, description, status
        FROM plants
        WHERE plant_id = ?
        LIMIT 1
    ");
    $editStatement->bind_param("i", $editId);
    $editStatement->execute();
    $editStatement->bind_result(
        $ePlantId, $eCategoryId, $eName, $ePrice, $eQuantity,
        $eSize, $eLight, $ePotSize, $ePotColor,
        $ePetFriendly, $eDifficulty, $eAirCleaner, $eDescription, $eStatus
    );
    if ($editStatement->fetch()) {
        $editPlant = [
            "plant_id" => $ePlantId,
            "plant_category_id" => $eCategoryId,
            "plant_name" => $eName,
            "price" => $ePrice,
            "quantity" => $eQuantity,
            "plant_size" => $eSize,
            "light_requirement" => $eLight,
            "pot_size" => $ePotSize,
            "pot_color" => $ePotColor,
            "pet_friendly" => $ePetFriendly,
            "difficulty" => $eDifficulty,
            "air_cleaner" => $eAirCleaner,
            "description" => $eDescription,
            "status" => $eStatus
        ];
    }
    $editStatement->close();
}

$search = trim($_GET["q"] ?? "");
$inventorySql = "
    SELECT
        p.plant_id, p.plant_name, pc.category_name, p.price,
        p.quantity, p.status,
        COALESCE(
            (SELECT image_path FROM plant_images
             WHERE plant_id = p.plant_id
             ORDER BY is_main DESC, sort_order ASC LIMIT 1),
            'Images/Home/Logo.png'
        )
    FROM plants p
    INNER JOIN plant_categories pc
        ON pc.plant_category_id = p.plant_category_id
";

if ($search !== "") {
    $inventorySql .= " WHERE p.plant_name LIKE ? OR pc.category_name LIKE ?";
}
$inventorySql .= " ORDER BY p.updated_at DESC, p.plant_id DESC";

$inventoryStatement = $conn->prepare($inventorySql);
if ($search !== "") {
    $searchValue = "%" . $search . "%";
    $inventoryStatement->bind_param("ss", $searchValue, $searchValue);
}
$inventoryStatement->execute();
$inventoryStatement->bind_result(
    $plantId, $plantName, $categoryName, $price,
    $quantity, $status, $imagePath
);

$inventory = [];
while ($inventoryStatement->fetch()) {
    $inventory[] = [
        "plant_id" => $plantId,
        "plant_name" => $plantName,
        "category_name" => $categoryName,
        "price" => $price,
        "quantity" => $quantity,
        "status" => $status,
        "image_path" => $imagePath
    ];
}
$inventoryStatement->close();
$conn->close();

$result = $_GET["result"] ?? "";
$messages = [
    "added" => "Plant added successfully.",
    "updated" => "Plant updated successfully.",
    "status" => "Plant status updated successfully.",
    "deleted" => "Plant deleted successfully.",
    "delete_failed" => "Plant could not be deleted. It may be linked to an existing cart or order.",
    "missing" => "Please complete all required plant fields.",
    "invalid" => "One or more plant values are invalid.",
    "image" => "The image must be JPEG, PNG or WebP and no larger than 5 MB.",
    "failed" => "The plant could not be saved. Please try again."
];
$successResults = ["added", "updated", "status", "deleted"];
$notice = $messages[$result] ?? "";
$adminName = trim(($_SESSION["first_name"] ?? "") . " " . ($_SESSION["last_name"] ?? ""));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plants | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .admin-notice { margin:0 0 22px; padding:13px; text-align:center; }
        .admin-notice.success { background:#e6f6e9; color:#145837; }
        .admin-notice.error { background:#ffecec; color:#a40000; }
        .plant-status-label { display:inline-block; padding:5px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .plant-status-label.active { background:#e6f6e9; color:#145837; }
        .plant-status-label.inactive { background:#eee; color:#666; }
        .status-button { border:1px solid #a33; background:white; color:#a33; padding:7px 11px; border-radius:5px; cursor:pointer; }
        .activate-button { border-color:#145837; color:#145837; }
        .inventory-actions form { display:inline; }
        .current-image-note { color:#666; font-size:12px; margin-top:6px; }
    </style>
</head>
<body class="admin-body admin-plants-body">
    <header class="admin-header">
        <a href="index.php" class="admin-logo"><img src="Images/Home/Logo.png" alt="EcoSprout Logo"></a>
        <div class="admin-user">
            <div class="admin-user-details"><strong><?= plantAdminEscape($adminName ?: $roleLabel) ?></strong><span><?= plantAdminEscape($roleLabel) ?></span></div>
            <a href="logout.php" class="admin-logout-button">Log Out</a>
        </div>
    </header>

    <nav class="admin-navigation">
        <a href="admindashboard.php" class="admin-nav-link">Dashboard</a>
<a href="admin_orders.php" class="admin-nav-link">Orders</a>
<a href="admin_plants.php" class="admin-nav-link active">Plants</a>
<a href="admin_tools.php" class="admin-nav-link">Tools</a>
<a href="admin_services.php" class="admin-nav-link">Services</a>
<a href="admin_inquiries.php" class="admin-nav-link">Inquiries</a>
        <?php if ($isAdministrator): ?>
             <a href="admin_staff.php" class="admin-nav-link">Manage Staff</a>
        <?php endif; ?>
    </nav>

    <main class="admin-plants-main">
        <div class="page-heading">
            <p>EcoSprout Inventory</p>
            <h1>Manage Plants</h1>
            <span>Add plants and manage prices, stock and availability.</span>
        </div>

        <?php if ($notice !== ""): ?>
            <div class="admin-notice <?= in_array($result, $successResults, true) ? "success" : "error" ?>">
                <?= plantAdminEscape($notice) ?>
            </div>
        <?php endif; ?>

        <section class="plant-form-card">
            <div class="section-heading">
                <h2><?= $editPlant ? "Update Plant" : "Add New Plant" ?></h2>
                <p><?= $editPlant ? "Edit the selected plant information." : "Enter the plant information and upload images." ?></p>
            </div>

            <form action="plant_action.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="plant_admin_token" value="<?= plantAdminEscape($_SESSION["plant_admin_token"]) ?>">
                <input type="hidden" name="action" value="<?= $editPlant ? "update" : "add" ?>">
                <?php if ($editPlant): ?>
                    <input type="hidden" name="plant_id" value="<?= (int) $editPlant["plant_id"] ?>">
                <?php endif; ?>

                <div class="plant-fields">
                    <div class="plant-field plant-name-field">
                        <label for="plantName">Plant Name <span>*</span></label>
                        <input type="text" id="plantName" name="plant_name" required
                               value="<?= plantAdminEscape($editPlant["plant_name"] ?? "") ?>">
                    </div>

                    <div class="plant-field">
                        <label for="price">Item Price <span>*</span></label>
                        <input type="number" id="price" name="price" min="0" step="0.01" required
                               value="<?= plantAdminEscape($editPlant["price"] ?? "") ?>">
                    </div>

                    <div class="plant-field">
                        <label for="quantity">Quantity <span>*</span></label>
                        <input type="number" id="quantity" name="quantity" min="0" required
                               value="<?= plantAdminEscape($editPlant["quantity"] ?? "0") ?>">
                    </div>

                    <div class="plant-field">
                        <label for="category">Category <span>*</span></label>
                        <select id="category" name="plant_category_id" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category["plant_category_id"] ?>"
                                    <?= (int) ($editPlant["plant_category_id"] ?? 0) === (int) $category["plant_category_id"] ? "selected" : "" ?>>
                                    <?= plantAdminEscape($category["category_name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="plant-field">
                        <label for="plantSize">Plant Size</label>
                        <select id="plantSize" name="plant_size">
                            <option value="">Not specified</option>
                            <?php foreach (["Small", "Medium", "Large"] as $option): ?>
                                <option value="<?= $option ?>" <?= ($editPlant["plant_size"] ?? "") === $option ? "selected" : "" ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="plant-field">
                        <label for="light">Light Requirement</label>
                        <select id="light" name="light_requirement">
                            <option value="">Not specified</option>
                            <?php foreach (["Low Light", "Indirect Light", "Full Sunlight", "Partial Shade"] as $option): ?>
                                <option value="<?= $option ?>" <?= ($editPlant["light_requirement"] ?? "") === $option ? "selected" : "" ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="plant-field">
                        <label for="potSize">Pot Size</label>
                        <input type="text" id="potSize" name="pot_size"
                               value="<?= plantAdminEscape($editPlant["pot_size"] ?? "") ?>" placeholder="Example: 20 cm">
                    </div>

                    <div class="plant-field">
                        <label for="potColor">Pot Color</label>
                        <select id="potColor" name="pot_color">
                            <option value="">Not specified</option>
                            <?php foreach (["Black", "Clay", "Gray", "Green", "White"] as $option): ?>
                                <option value="<?= $option ?>" <?= ($editPlant["pot_color"] ?? "") === $option ? "selected" : "" ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="plant-field">
                        <label for="difficulty">Care Difficulty</label>
                        <select id="difficulty" name="difficulty">
                            <option value="">Not specified</option>
                            <?php foreach (["Easy Care", "Medium Care", "Advanced Care"] as $option): ?>
                                <option value="<?= $option ?>" <?= ($editPlant["difficulty"] ?? "") === $option ? "selected" : "" ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="plant-field">
                        <label><input type="checkbox" name="pet_friendly" value="1" <?= !empty($editPlant["pet_friendly"]) ? "checked" : "" ?>> Pet Friendly</label>
                    </div>
                    <div class="plant-field">
                        <label><input type="checkbox" name="air_cleaner" value="1" <?= !empty($editPlant["air_cleaner"]) ? "checked" : "" ?>> Air Cleaner</label>
                    </div>
                    <div class="plant-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="ACTIVE" <?= ($editPlant["status"] ?? "ACTIVE") === "ACTIVE" ? "selected" : "" ?>>Active</option>
                            <option value="INACTIVE" <?= ($editPlant["status"] ?? "") === "INACTIVE" ? "selected" : "" ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="plant-extra-details">
                    <fieldset class="plant-images">
                        <legend>Add Images</legend>
                        <?php for ($imageNumber = 1; $imageNumber <= 6; $imageNumber++): ?>
                            <?php $fieldName = $imageNumber === 1 ? "main_image" : "image_" . $imageNumber; ?>
                            <label class="image-upload-box">
                                <span>+</span><?= $imageNumber === 1 ? "Main Image" : "Image " . $imageNumber ?>
                                <input type="file" name="<?= $fieldName ?>" accept="image/jpeg,image/png,image/webp">
                            </label>
                        <?php endfor; ?>
                        <?php if ($editPlant): ?><p class="current-image-note">Leave these empty to keep the current images.</p><?php endif; ?>
                    </fieldset>

                    <div class="plant-field plant-description">
                        <label for="description">Description <span>*</span></label>
                        <textarea id="description" name="description" rows="8" required><?= plantAdminEscape($editPlant["description"] ?? "") ?></textarea>
                    </div>
                </div>

                <div class="plant-form-buttons">
                    <?php if ($editPlant): ?><a href="admin_plants.php" class="clear-button">Cancel</a><?php else: ?><button type="reset" class="clear-button">Clear</button><?php endif; ?>
                    <button type="submit" class="add-plant-button"><?= $editPlant ? "Update Plant" : "Add New Plant" ?></button>
                </div>
            </form>
        </section>

        <section class="inventory-card">
            <div class="inventory-heading">
                <div><h2>Plant Inventory</h2><p>View and manage all plants.</p></div>
                <form action="admin_plants.php" method="get">
                    <input type="search" name="q" class="plant-search" value="<?= plantAdminEscape($search) ?>" placeholder="Search plants...">
                </form>
            </div>

            <div class="inventory-table-container">
                <table class="inventory-table">
                    <thead><tr><th>Plant ID</th><th>Image</th><th>Plant Name</th><th>Category</th><th>Price</th><th>Quantity</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($inventory) === 0): ?><tr><td colspan="8">No plants found.</td></tr><?php endif; ?>
                        <?php foreach ($inventory as $plant): ?>
                            <tr>
                                <td>#P<?= str_pad((string) $plant["plant_id"], 3, "0", STR_PAD_LEFT) ?></td>
                                <td><img src="<?= plantAdminEscape($plant["image_path"]) ?>" alt="<?= plantAdminEscape($plant["plant_name"]) ?>"></td>
                                <td><?= plantAdminEscape($plant["plant_name"]) ?></td>
                                <td><?= plantAdminEscape($plant["category_name"]) ?></td>
                                <td>Rs. <?= number_format((float) $plant["price"], 2) ?></td>
                                <td><?= (int) $plant["quantity"] ?></td>
                                <td><span class="plant-status-label <?= strtolower($plant["status"]) ?>"><?= plantAdminEscape($plant["status"]) ?></span></td>
                                <td class="inventory-actions">
                                    <a href="admin_plants.php?edit=<?= (int) $plant["plant_id"] ?>" class="update-button">Update</a>
                                    <form action="plant_action.php" method="post">
                                        <input type="hidden" name="plant_admin_token" value="<?= plantAdminEscape($_SESSION["plant_admin_token"]) ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="plant_id" value="<?= (int) $plant["plant_id"] ?>">
                                        <input type="hidden" name="status" value="<?= $plant["status"] === "ACTIVE" ? "INACTIVE" : "ACTIVE" ?>">
                                        <button type="submit" class="status-button <?= $plant["status"] === "INACTIVE" ? "activate-button" : "" ?>"><?= $plant["status"] === "ACTIVE" ? "Deactivate" : "Activate" ?></button>
                                    </form>
                                    <form action="plant_action.php" method="post" class="plant-delete-form" onsubmit="return confirm('Are you sure you want to permanently delete this plant? This action cannot be undone.');">
                                        <input type="hidden" name="plant_admin_token" value="<?= plantAdminEscape($_SESSION["plant_admin_token"]) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="plant_id" value="<?= (int) $plant["plant_id"] ?>">
                                        <button type="submit" class="plant-delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="admin-plants-footer"><p>&copy; 2026 EcoSprout Nursery. All rights reserved.</p></footer>
</body>
</html>
