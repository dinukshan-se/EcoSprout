<?php
require_once "admin_auth.php";
require_once "db_connection.php";

function toolAdminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["tool_admin_token"])) {
    $_SESSION["tool_admin_token"] = bin2hex(random_bytes(32));
}

/* Identify the logged-in user without depending on a separate variable. */
$currentRole = strtoupper(trim($_SESSION["role"] ?? "STAFF"));
$isAdministrator = $currentRole === "ADMIN";
$roleLabel = $isAdministrator ? "Administrator" : "Staff";
$pageError = "";

$categories = [];
$categoryResult = $conn->query("SELECT tool_category_id, category_name FROM tool_categories ORDER BY category_name");
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    $pageError = "Tool categories could not be loaded.";
}

$editTool = null;
$editId = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
if ($editId !== false && $editId !== null && $editId > 0) {
    $editStatement = $conn->prepare("
        SELECT tool_id, tool_category_id, tool_name, brand, price,
               quantity, purpose, material, description, status
        FROM tools WHERE tool_id = ? LIMIT 1
    ");
    if ($editStatement) {
        $editStatement->bind_param("i", $editId);
        $editStatement->execute();
        $editStatement->bind_result(
            $eToolId, $eCategoryId, $eName, $eBrand, $ePrice,
            $eQuantity, $ePurpose, $eMaterial, $eDescription, $eStatus
        );
        if ($editStatement->fetch()) {
            $editTool = [
                "tool_id" => $eToolId,
                "tool_category_id" => $eCategoryId,
                "tool_name" => $eName,
                "brand" => $eBrand,
                "price" => $ePrice,
                "quantity" => $eQuantity,
                "purpose" => $ePurpose,
                "material" => $eMaterial,
                "description" => $eDescription,
                "status" => $eStatus
            ];
        } else {
            $pageError = "The selected tool could not be found.";
        }
        $editStatement->close();
    } else {
        $pageError = "The selected tool could not be loaded.";
    }
}

$search = trim($_GET["q"] ?? "");
$inventorySql = "
    SELECT t.tool_id, t.tool_name, tc.category_name, t.brand,
           t.price, t.quantity, t.status,
           COALESCE(
               (SELECT image_path FROM tool_images
                WHERE tool_id = t.tool_id
                ORDER BY is_main DESC, sort_order ASC LIMIT 1),
               'Images/Home/Logo.png'
           )
    FROM tools t
    INNER JOIN tool_categories tc ON tc.tool_category_id = t.tool_category_id
";
if ($search !== "") {
    $inventorySql .= " WHERE t.tool_name LIKE ? OR t.brand LIKE ? OR tc.category_name LIKE ?";
}
$inventorySql .= " ORDER BY t.updated_at DESC, t.tool_id DESC";

$inventory = [];
$inventoryStatement = $conn->prepare($inventorySql);
if ($inventoryStatement) {
    if ($search !== "") {
        $searchValue = "%" . $search . "%";
        $inventoryStatement->bind_param("sss", $searchValue, $searchValue, $searchValue);
    }
    $inventoryStatement->execute();
    $inventoryStatement->bind_result(
        $toolId, $toolName, $categoryName, $brand,
        $price, $quantity, $status, $imagePath
    );

    while ($inventoryStatement->fetch()) {
        $inventory[] = [
            "tool_id" => $toolId,
            "tool_name" => $toolName,
            "category_name" => $categoryName,
            "brand" => $brand,
            "price" => $price,
            "quantity" => $quantity,
            "status" => $status,
            "image_path" => $imagePath
        ];
    }
    $inventoryStatement->close();
} elseif ($pageError === "") {
    $pageError = "The tool inventory could not be loaded.";
}
$conn->close();

$result = $_GET["result"] ?? "";
$result = $_GET["result"] ?? "";

$messages = [

    "added" =>
        "Tool added successfully.",

    "updated" =>
        "Tool updated successfully.",

    "status" =>
        "Tool status updated successfully.",

    "deleted" =>
        "Tool deleted successfully.",

    "delete_failed" =>
        "Tool could not be deleted. It may be linked to an existing cart or order.",

    "missing" =>
        "Please complete all required tool fields.",

    "invalid" =>
        "One or more tool values are invalid.",

    "image" =>
        "Images must be JPEG, PNG or WebP and no larger than 5 MB each.",

    "token" =>
        "The request expired. Please try again.",

    "not_found" =>
        "The selected tool could not be found.",

    "failed" =>
        "The tool could not be saved. Please try again."

];


$notice =
    $messages[$result] ?? "";


$successResults = [

    "added",
    "updated",
    "status",
    "deleted"

];


$adminName =
    trim(
        ($_SESSION["first_name"] ?? "") .
        " " .
        ($_SESSION["last_name"] ?? "")
    );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tools | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .admin-notice { margin:0 0 22px; padding:13px; text-align:center; }
        .admin-notice.success { background:#e6f6e9; color:#145837; }
        .admin-notice.error { background:#ffecec; color:#a40000; }
        .tool-status-label { display:inline-block; padding:5px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .tool-status-label.active { background:#e6f6e9; color:#145837; }
        .tool-status-label.inactive { background:#eee; color:#666; }
        .tool-status-button { border:1px solid #a33; background:white; color:#a33; padding:7px 11px; border-radius:5px; cursor:pointer; }
        .tool-activate-button { border-color:#145837; color:#145837; }
        .tool-inventory-actions form { display:inline; }
        .current-image-note { color:#666; font-size:12px; }
    </style>
</head>
<body class="admin-body admin-tools-body">
    <header class="admin-header">
        <a href="index.php" class="admin-logo"><img src="Images/Home/Logo.png" alt="EcoSprout Logo"></a>
        <div class="admin-user">
            <div class="admin-user-details">
                <strong><?= toolAdminEscape($adminName ?: $roleLabel) ?></strong>
                <span><?= toolAdminEscape($roleLabel) ?></span>
            </div>
            <a href="logout.php" class="admin-logout-button">Log Out</a>
        </div>
    </header>

    <nav class="admin-navigation">
        <a href="admindashboard.php" class="admin-nav-link">Dashboard</a>
        <a href="admin_orders.php" class="admin-nav-link">Orders</a>
        <a href="admin_plants.php" class="admin-nav-link">Plants</a>
        <a href="admin_tools.php" class="admin-nav-link active">Tools</a>
        <a href="admin_services.php" class="admin-nav-link">Services</a>
        <?php if ($isAdministrator): ?>
            <a href="admin_inquiries.php" class="admin-nav-link">Inquiries</a>
            <a href="admin_staff.php" class="admin-nav-link">Manage Staff</a>
        <?php endif; ?>
    </nav>

    <main class="admin-tools-main">
        <div class="tools-page-heading">
            <p>EcoSprout Inventory</p>
            <h1>Manage Tools</h1>
            <span>Add gardening tools and manage prices, stock and availability.</span>
        </div>

        <?php if ($notice !== ""): ?>
            <div class="admin-notice <?= in_array($result, $successResults, true) ? "success" : "error" ?>">
                <?= toolAdminEscape($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($pageError !== ""): ?>
            <div class="admin-notice error">
                <?= toolAdminEscape($pageError) ?>
            </div>
        <?php endif; ?>

        <section class="tool-form-card">
            <div class="tool-section-heading">
                <h2><?= $editTool ? "Update Tool" : "Add New Tool" ?></h2>
                <p><?= $editTool ? "Edit the selected tool information." : "Enter the gardening-tool information and upload images." ?></p>
            </div>

            <form action="tool_action.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="tool_admin_token" value="<?= toolAdminEscape($_SESSION["tool_admin_token"]) ?>">
                <input type="hidden" name="action" value="<?= $editTool ? "update" : "add" ?>">
                <?php if ($editTool): ?><input type="hidden" name="tool_id" value="<?= (int) $editTool["tool_id"] ?>"><?php endif; ?>

                <div class="tool-fields">
                    <div class="tool-field tool-name-field">
                        <label for="toolName">Tool Name <span>*</span></label>
                        <input type="text" id="toolName" name="tool_name" required value="<?= toolAdminEscape($editTool["tool_name"] ?? "") ?>">
                    </div>
                    <div class="tool-field">
                        <label for="toolPrice">Item Price <span>*</span></label>
                        <input type="number" id="toolPrice" name="price" min="0" step="0.01" required value="<?= toolAdminEscape($editTool["price"] ?? "") ?>">
                    </div>
                    <div class="tool-field">
                        <label for="toolQuantity">Quantity <span>*</span></label>
                        <input type="number" id="toolQuantity" name="quantity" min="0" required value="<?= toolAdminEscape($editTool["quantity"] ?? "0") ?>">
                    </div>
                    <div class="tool-field">
                        <label for="toolBrand">Brand <span>*</span></label>
                        <input type="text" id="toolBrand" name="brand" required value="<?= toolAdminEscape($editTool["brand"] ?? "") ?>">
                    </div>
                    <div class="tool-field">
                        <label for="toolCategory">Category <span>*</span></label>
                        <select id="toolCategory" name="tool_category_id" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category["tool_category_id"] ?>" <?= (int) ($editTool["tool_category_id"] ?? 0) === (int) $category["tool_category_id"] ? "selected" : "" ?>><?= toolAdminEscape($category["category_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tool-field">
                        <label for="toolPurpose">Purpose <span>*</span></label>
                        <select id="toolPurpose" name="purpose" required>
                            <option value="">Select purpose</option>
                            <?php foreach (["Digging", "Cutting", "Watering", "Planting", "Cleaning", "Maintenance"] as $option): ?>
                                <option value="<?= $option ?>" <?= ($editTool["purpose"] ?? "") === $option ? "selected" : "" ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tool-field">
                        <label for="toolMaterial">Material</label>
                        <select id="toolMaterial" name="material">
                            <option value="">Not specified</option>
                            <?php foreach (["Stainless Steel", "Carbon Steel", "Plastic", "Wood", "Aluminium", "Other"] as $option): ?>
                                <option value="<?= $option ?>" <?= ($editTool["material"] ?? "") === $option ? "selected" : "" ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tool-field">
                        <label for="toolStatus">Status</label>
                        <select id="toolStatus" name="status">
                            <option value="ACTIVE" <?= ($editTool["status"] ?? "ACTIVE") === "ACTIVE" ? "selected" : "" ?>>Active</option>
                            <option value="INACTIVE" <?= ($editTool["status"] ?? "") === "INACTIVE" ? "selected" : "" ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="tool-extra-details">
                    <fieldset class="tool-images">
                        <legend>Tool Images</legend>
                        <?php for ($imageNumber = 1; $imageNumber <= 6; $imageNumber++): ?>
                            <?php $fieldName = $imageNumber === 1 ? "main_image" : "image_" . $imageNumber; ?>
                            <label class="tool-image-upload">
                                <span>+</span><?= $imageNumber === 1 ? "Main Image" : "Image " . $imageNumber ?>
                                <input type="file" name="<?= $fieldName ?>" accept="image/jpeg,image/png,image/webp">
                            </label>
                        <?php endfor; ?>
                        <?php if ($editTool): ?><p class="current-image-note">Leave these empty to keep current images.</p><?php endif; ?>
                    </fieldset>

                    <div class="tool-field tool-description">
                        <label for="toolDescription">Description <span>*</span></label>
                        <textarea id="toolDescription" name="description" rows="10" required><?= toolAdminEscape($editTool["description"] ?? "") ?></textarea>
                    </div>
                </div>

                <div class="tool-form-buttons">
                    <?php if ($editTool): ?><a href="admin_tools.php" class="tool-clear-button">Cancel</a><?php else: ?><button type="reset" class="tool-clear-button">Clear</button><?php endif; ?>
                    <button type="submit" class="add-tool-button"><?= $editTool ? "Update Tool" : "Add New Tool" ?></button>
                </div>
            </form>
        </section>

        <section class="tool-inventory-card">
            <div class="tool-inventory-heading">
                <div><h2>Tool Inventory</h2><p>View and manage gardening tools.</p></div>
                <form action="admin_tools.php" method="get"><input type="search" name="q" class="tool-search" value="<?= toolAdminEscape($search) ?>" placeholder="Search tools..."></form>
            </div>

            <div class="tool-table-container">
                <table class="tool-inventory-table">
                    <thead><tr><th>Tool ID</th><th>Image</th><th>Tool Name</th><th>Category</th><th>Brand</th><th>Price</th><th>Quantity</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($inventory) === 0): ?>
                            <tr>
                                <td colspan="9" style="padding:28px; text-align:center; color:#69766e;">
                                    <?= $search !== "" ? "No tools matched your search." : "No tools have been added yet." ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($inventory as $tool): ?>
                            <tr>
                                <td>#T<?= str_pad((string) $tool["tool_id"], 3, "0", STR_PAD_LEFT) ?></td>
                                <td><img src="<?= toolAdminEscape($tool["image_path"]) ?>" alt="<?= toolAdminEscape($tool["tool_name"]) ?>"></td>
                                <td><strong><?= toolAdminEscape($tool["tool_name"]) ?></strong></td>
                                <td><?= toolAdminEscape($tool["category_name"]) ?></td>
                                <td><?= toolAdminEscape($tool["brand"]) ?></td>
                                <td>Rs. <?= number_format((float) $tool["price"], 2) ?></td>
                                <td><?= (int) $tool["quantity"] ?></td>
                                <td><span class="tool-status-label <?= strtolower($tool["status"]) ?>"><?= toolAdminEscape($tool["status"]) ?></span></td>
                                <td class="tool-inventory-actions">

    <!-- ==========================
         UPDATE TOOL
    =========================== -->

    <a
        href="admin_tools.php?edit=<?= (int) $tool["tool_id"] ?>"
        class="tool-update-button">

        Update

    </a>


    <!-- ==========================
         ACTIVATE / DEACTIVATE
    =========================== -->

    <form
        action="tool_action.php"
        method="post">

        <input
            type="hidden"
            name="tool_admin_token"
            value="<?= toolAdminEscape($_SESSION["tool_admin_token"]) ?>">

        <input
            type="hidden"
            name="action"
            value="toggle_status">

        <input
            type="hidden"
            name="tool_id"
            value="<?= (int) $tool["tool_id"] ?>">

        <input
            type="hidden"
            name="status"
            value="<?= $tool["status"] === "ACTIVE" ? "INACTIVE" : "ACTIVE" ?>">


        <button
            type="submit"
            class="tool-status-button <?= $tool["status"] === "INACTIVE" ? "tool-activate-button" : "" ?>"
            onclick="return confirm('Are you sure you want to change this tool status?');">

            <?= $tool["status"] === "ACTIVE" ? "Deactivate" : "Activate" ?>

        </button>

    </form>


    <!-- ==========================
         DELETE TOOL
    =========================== -->

    <form
        action="tool_action.php"
        method="post"
        class="tool-delete-form"
        onsubmit="return confirm('Are you sure you want to permanently delete this tool? This action cannot be undone.');">

        <input
            type="hidden"
            name="tool_admin_token"
            value="<?= toolAdminEscape($_SESSION["tool_admin_token"]) ?>">

        <input
            type="hidden"
            name="action"
            value="delete">

        <input
            type="hidden"
            name="tool_id"
            value="<?= (int) $tool["tool_id"] ?>">


        <button
            type="submit"
            class="tool-delete-button">

            Delete

        </button>

    </form>

</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
