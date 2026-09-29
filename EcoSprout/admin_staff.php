<?php
require_once "admin_auth.php";

if (!$isAdministrator) {
    header("Location: admindashboard.php?access=denied");
    exit;
}

require_once "db_connection.php";

function staffEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["staff_admin_token"])) {
    $_SESSION["staff_admin_token"] = bin2hex(random_bytes(32));
}

$editStaff = null;
$editId = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
if ($editId !== false && $editId !== null && $editId > 0) {
    $editStatement = $conn->prepare("
        SELECT user_id, employee_code, first_name, last_name, email, role,
               phone, alternative_phone, address_line_1, address_line_2,
               city, postal_code, status
        FROM users
        WHERE user_id = ? AND role IN ('ADMIN', 'STAFF')
        LIMIT 1
    ");
    $editStatement->bind_param("i", $editId);
    $editStatement->execute();
    $editStatement->bind_result(
        $eUserId, $eEmployeeCode, $eFirstName, $eLastName, $eEmail, $eRole,
        $ePhone, $eAlternativePhone, $eAddress1, $eAddress2,
        $eCity, $ePostalCode, $eStatus
    );
    if ($editStatement->fetch()) {
        $editStaff = [
            "user_id" => $eUserId,
            "employee_code" => $eEmployeeCode,
            "first_name" => $eFirstName,
            "last_name" => $eLastName,
            "email" => $eEmail,
            "role" => $eRole,
            "phone" => $ePhone,
            "alternative_phone" => $eAlternativePhone,
            "address_line_1" => $eAddress1,
            "address_line_2" => $eAddress2,
            "city" => $eCity,
            "postal_code" => $ePostalCode,
            "status" => $eStatus
        ];
    }
    $editStatement->close();
}

$search = trim($_GET["q"] ?? "");
$staffSql = "
    SELECT user_id, employee_code, first_name, last_name,
           email, phone, role, status, created_at
    FROM users
    WHERE role IN ('ADMIN', 'STAFF')
";
if ($search !== "") {
    $staffSql .= " AND (employee_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
}
$staffSql .= " ORDER BY created_at DESC, user_id DESC";

$staffStatement = $conn->prepare($staffSql);
if ($search !== "") {
    $searchValue = "%" . $search . "%";
    $staffStatement->bind_param("ssss", $searchValue, $searchValue, $searchValue, $searchValue);
}
$staffStatement->execute();
$staffStatement->bind_result(
    $userId, $employeeCode, $firstName, $lastName,
    $email, $phone, $role, $status, $createdAt
);
$staffMembers = [];
while ($staffStatement->fetch()) {
    $staffMembers[] = [
        "user_id" => $userId,
        "employee_code" => $employeeCode,
        "first_name" => $firstName,
        "last_name" => $lastName,
        "email" => $email,
        "phone" => $phone,
        "role" => $role,
        "status" => $status,
        "created_at" => $createdAt
    ];
}
$staffStatement->close();
$conn->close();

$result = $_GET["result"] ?? "";
$messages = [
    "added" => "User account created successfully.",
    "updated" => "User account updated successfully.",
    "status" => "User access status updated successfully.",

    "deleted" => "Staff account deleted successfully.",

    "self_delete" =>
        "You cannot delete your own administrator account.",

    "delete_failed" =>
        "The staff account could not be deleted. It may be linked to existing records.",

    "missing" =>
        "Please complete all required account fields.",

    "password" =>
        "Passwords must match and contain at least 8 characters.",

    "duplicate" =>
        "The employee code or email address is already used.",

    "invalid" =>
        "The submitted account information is invalid.",

    "failed" =>
        "The user account could not be saved."
];

$successResults = [
    "added",
    "updated",
    "status",
    "deleted"
];
$notice = $messages[$result] ?? "";
$adminName = trim(($_SESSION["first_name"] ?? "") . " " . ($_SESSION["last_name"] ?? ""));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .admin-notice { margin:0 0 22px; padding:13px; text-align:center; }
        .admin-notice.success { background:#e6f6e9; color:#145837; }
        .admin-notice.error { background:#ffecec; color:#a40000; }
        .staff-status { display:inline-block; padding:5px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .staff-status.active { background:#e6f6e9; color:#145837; }
        .staff-status.inactive { background:#eee; color:#666; }
        .staff-admin-role { background:#fff0cc; color:#704c00; }
        .staff-status-button { border:1px solid #a33; background:white; color:#a33; padding:7px 11px; border-radius:5px; cursor:pointer; }
        .staff-activate-button { border-color:#145837; color:#145837; }
        .staff-actions form { display:inline; }
        .staff-help { color:#666; font-size:12px; margin-top:5px; }
    </style>
</head>
<body class="manage-staff-body">
    <header class="staff-header">
        <a href="admindashboard.php" class="staff-logo"><img src="Images/Home/Logo.png" alt="EcoSprout Logo"></a>
        <div><strong><?= staffEscape($adminName ?: "Administrator") ?></strong> <a href="logout.php" class="staff-logout-button">Log Out</a></div>
    </header>

    <nav class="staff-navigation">
        <a href="admindashboard.php">Dashboard</a>
        <a href="admin_plants.php">Plants</a>
        <a href="admin_tools.php">Tools</a>
        <a href="admin_services.php">Services</a>
        <a href="admin_staff.php" class="active">Manage Staff</a>
    </nav>

    <main class="manage-staff-main">
        <div class="staff-page-heading">
            <p>EcoSprout User Management</p><h1>Manage Staff and Admins</h1>
            <span>Create and manage administrator and staff accounts.</span>
        </div>

        <?php if ($notice !== ""): ?>
            <div class="admin-notice <?= in_array($result, $successResults, true) ? "success" : "error" ?>"><?= staffEscape($notice) ?></div>
        <?php endif; ?>

        <section class="staff-form-card">
            <div class="staff-section-heading">
                <h2><?= $editStaff ? "Update Account" : "Add New Account" ?></h2>
                <p><?= $editStaff ? "Edit the selected administrator or staff account." : "Enter the employee details, role and login password." ?></p>
            </div>

            <form action="staff_action.php" method="post" class="staff-form">
                <input type="hidden" name="staff_admin_token" value="<?= staffEscape($_SESSION["staff_admin_token"]) ?>">
                <input type="hidden" name="action" value="<?= $editStaff ? "update" : "add" ?>">
                <?php if ($editStaff): ?><input type="hidden" name="user_id" value="<?= (int) $editStaff["user_id"] ?>"><?php endif; ?>

                <div class="staff-fields">
                    <div class="staff-field">
                        <label for="employeeCode">Employee ID <span>*</span></label>
                        <input type="text" id="employeeCode" name="employee_code" required value="<?= staffEscape($editStaff["employee_code"] ?? "") ?>" placeholder="Example: EMP001">
                    </div>
                    <div class="staff-field">
                        <label for="firstName">First Name <span>*</span></label>
                        <input type="text" id="firstName" name="first_name" required value="<?= staffEscape($editStaff["first_name"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="lastName">Last Name <span>*</span></label>
                        <input type="text" id="lastName" name="last_name" required value="<?= staffEscape($editStaff["last_name"] ?? "") ?>">
                    </div>
                    <div class="staff-field staff-email-field">
                        <label for="email">Email Address <span>*</span></label>
                        <input type="email" id="email" name="email" required value="<?= staffEscape($editStaff["email"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= staffEscape($editStaff["phone"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="alternativePhone">Alternative Phone</label>
                        <input type="tel" id="alternativePhone" name="alternative_phone" value="<?= staffEscape($editStaff["alternative_phone"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="address1">Address Line 1</label>
                        <input type="text" id="address1" name="address_line_1" value="<?= staffEscape($editStaff["address_line_1"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="address2">Address Line 2</label>
                        <input type="text" id="address2" name="address_line_2" value="<?= staffEscape($editStaff["address_line_2"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?= staffEscape($editStaff["city"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="postalCode">Postal Code</label>
                        <input type="text" id="postalCode" name="postal_code" value="<?= staffEscape($editStaff["postal_code"] ?? "") ?>">
                    </div>
                    <div class="staff-field">
                        <label for="status">Access Status</label>
                        <select id="status" name="status">
                            <option value="ACTIVE" <?= ($editStaff["status"] ?? "ACTIVE") === "ACTIVE" ? "selected" : "" ?>>Active</option>
                            <option value="INACTIVE" <?= ($editStaff["status"] ?? "") === "INACTIVE" ? "selected" : "" ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="staff-field">
                        <label for="role">User Role <span>*</span></label>
                        <select id="role" name="role" required>
                            <option value="STAFF" <?= ($editStaff["role"] ?? "STAFF") === "STAFF" ? "selected" : "" ?>>Staff</option>
                            <option value="ADMIN" <?= ($editStaff["role"] ?? "") === "ADMIN" ? "selected" : "" ?>>Admin</option>
                        </select>
                    </div>
                    <div class="staff-field">
                        <label for="password">Password <?= $editStaff ? "" : "*" ?></label>
                        <input type="password" id="password" name="password" minlength="8" <?= $editStaff ? "" : "required" ?>>
                        <?php if ($editStaff): ?><p class="staff-help">Leave empty to keep the current password.</p><?php endif; ?>
                    </div>
                    <div class="staff-field">
                        <label for="confirmPassword">Confirm Password <?= $editStaff ? "" : "*" ?></label>
                        <input type="password" id="confirmPassword" name="confirm_password" minlength="8" <?= $editStaff ? "" : "required" ?>>
                    </div>
                </div>

                <div class="staff-form-buttons">
                    <?php if ($editStaff): ?><a href="admin_staff.php" class="staff-update-form-button">Cancel</a><?php endif; ?>
                    <button type="submit" class="staff-add-button"><?= $editStaff ? "Update Account" : "Add New Account" ?></button>
                </div>
            </form>
        </section>

        <section class="staff-inventory-card">
            <div class="staff-inventory-heading">
                <div><h2>Admin and Staff Accounts</h2><p>View and manage both account types.</p></div>
                <form action="admin_staff.php" method="get"><input type="search" name="q" class="staff-search" value="<?= staffEscape($search) ?>" placeholder="Search admins or staff..."></form>
            </div>
            <div class="staff-table-container">
                <table class="staff-inventory-table">
                    <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($staffMembers) === 0): ?><tr><td colspan="7">No administrator or staff accounts found.</td></tr><?php endif; ?>
                        <?php foreach ($staffMembers as $staff): ?>
                            <tr>
                                <td><strong><?= staffEscape($staff["employee_code"]) ?></strong></td>
                                <td><?= staffEscape($staff["first_name"] . " " . $staff["last_name"]) ?></td>
                                <td><?= staffEscape($staff["email"]) ?></td>
                                <td><?= staffEscape($staff["phone"] ?: "-") ?></td>
                                <td><span class="user-role <?= $staff["role"] === "ADMIN" ? "staff-admin-role" : "staff-role" ?>">
                                    <?= staffEscape($staff["role"] === "ADMIN" ? "Admin" : "Staff") ?></span></td>
                                <td><span class="staff-status <?= strtolower($staff["status"]) ?>"><?= staffEscape($staff["status"]) ?></span></td>
                                <td class="staff-actions">

    <!-- UPDATE -->

    <a
        href="admin_staff.php?edit=<?= (int) $staff["user_id"] ?>"
        class="staff-table-update"
    >
        Update
    </a>


    <!-- ACTIVATE / DEACTIVATE -->

    <form
        action="staff_action.php"
        method="post"
    >

        <input
            type="hidden"
            name="staff_admin_token"
            value="<?= staffEscape($_SESSION["staff_admin_token"]) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="toggle_status"
        >

        <input
            type="hidden"
            name="user_id"
            value="<?= (int) $staff["user_id"] ?>"
        >

        <input
            type="hidden"
            name="status"
            value="<?= $staff["status"] === "ACTIVE" ? "INACTIVE" : "ACTIVE" ?>"
        >

        <button
            type="submit"
            class="staff-status-button <?= $staff["status"] === "INACTIVE" ? "staff-activate-button" : "" ?>"
        >
            <?= $staff["status"] === "ACTIVE" ? "Deactivate" : "Activate" ?>
        </button>

    </form>


    <!-- DELETE -->

    <form
        action="staff_action.php"
        method="post"
        class="staff-delete-form"
        onsubmit="return confirm('Are you sure you want to permanently delete this staff account? This action cannot be undone.');"
    >

        <input
            type="hidden"
            name="staff_admin_token"
            value="<?= staffEscape($_SESSION["staff_admin_token"]) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="delete"
        >

        <input
            type="hidden"
            name="user_id"
            value="<?= (int) $staff["user_id"] ?>"
        >

        <button
            type="submit"
            class="staff-delete-button"
        >
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
