<?php
require_once "admin_auth.php";

if (!$isAdministrator) {
    header("Location: admindashboard.php?access=denied");
    exit;
}

require_once "db_connection.php";

function inquiryEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function inquiryStatusLabel($status)
{
    return ucwords(strtolower(str_replace("_", " ", $status)));
}

if (empty($_SESSION["inquiry_admin_token"])) {
    $_SESSION["inquiry_admin_token"] = bin2hex(random_bytes(32));
}

$summaryResult = $conn->query("
    SELECT COUNT(*),
           COALESCE(SUM(inquiry_status = 'NEW'), 0),
           COALESCE(SUM(inquiry_status = 'READ'), 0),
           COALESCE(SUM(inquiry_status = 'REPLIED'), 0),
           COALESCE(SUM(inquiry_status = 'CLOSED'), 0)
    FROM contact_inquiries
");
$summaryRow = $summaryResult->fetch_row();
$summaryResult->close();
$summary = [
    "total" => $summaryRow[0], "new" => $summaryRow[1],
    "read" => $summaryRow[2], "replied" => $summaryRow[3],
    "closed" => $summaryRow[4]
];

$allowedStatuses = ["NEW", "READ", "REPLIED", "CLOSED"];
$search = trim($_GET["q"] ?? "");
$statusFilter = $_GET["status"] ?? "";
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

$inquirySql = "
    SELECT inquiry_id, full_name, email, phone, subject,
           inquiry_status, submitted_at
    FROM contact_inquiries
    WHERE 1 = 1
";
if ($statusFilter !== "") {
    $inquirySql .= " AND inquiry_status = '" . $statusFilter . "'";
}
if ($search !== "") {
    $inquirySql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
}
$inquirySql .= " ORDER BY submitted_at DESC, inquiry_id DESC";

$inquiryStatement = $conn->prepare($inquirySql);
if ($search !== "") {
    $searchValue = "%" . $search . "%";
    $inquiryStatement->bind_param(
        "sssss",
        $searchValue, $searchValue, $searchValue, $searchValue, $searchValue
    );
}
$inquiryStatement->execute();
$inquiryStatement->bind_result(
    $inquiryId, $fullName, $email, $phone,
    $subject, $inquiryStatus, $submittedAt
);
$inquiries = [];
while ($inquiryStatement->fetch()) {
    $inquiries[] = [
        "inquiry_id" => $inquiryId, "full_name" => $fullName,
        "email" => $email, "phone" => $phone, "subject" => $subject,
        "inquiry_status" => $inquiryStatus, "submitted_at" => $submittedAt
    ];
}
$inquiryStatement->close();

$selectedInquiry = null;
$viewId = filter_input(INPUT_GET, "view", FILTER_VALIDATE_INT);
if ($viewId) {
    $detailStatement = $conn->prepare("
        SELECT inquiry_id, user_id, full_name, email, phone,
               subject, message, inquiry_status, submitted_at
        FROM contact_inquiries
        WHERE inquiry_id = ?
        LIMIT 1
    ");
    $detailStatement->bind_param("i", $viewId);
    $detailStatement->execute();
    $detailStatement->bind_result(
        $dInquiryId, $dUserId, $dFullName, $dEmail, $dPhone,
        $dSubject, $dMessage, $dStatus, $dSubmittedAt
    );
    if ($detailStatement->fetch()) {
        $selectedInquiry = [
            "inquiry_id" => $dInquiryId, "user_id" => $dUserId,
            "full_name" => $dFullName, "email" => $dEmail,
            "phone" => $dPhone, "subject" => $dSubject,
            "message" => $dMessage, "inquiry_status" => $dStatus,
            "submitted_at" => $dSubmittedAt
        ];
    }
    $detailStatement->close();
}

$conn->close();
$result = $_GET["result"] ?? "";
$messages = [
    "updated" => "Inquiry status updated successfully.",
    "missing" => "The selected inquiry was not found.",
    "invalid" => "The submitted inquiry information is invalid."
];
$adminName = trim(($_SESSION["first_name"] ?? "") . " " . ($_SESSION["last_name"] ?? ""));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inquiries | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .inquiry-main{max-width:1250px;margin:35px auto;padding:0 20px}.inquiry-title{text-align:center;margin-bottom:28px}.inquiry-title p{color:#24704a;margin:0}.inquiry-title h1{color:#145837;margin:5px 0}.inquiry-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:25px}.inquiry-card,.inquiry-panel{background:#fff;border:1px solid #dfe8df;border-radius:8px}.inquiry-card{padding:18px}.inquiry-card span{display:block;color:#666;font-size:13px}.inquiry-card strong{display:block;color:#145837;font-size:25px;margin-top:7px}.inquiry-panel{padding:20px;margin-bottom:25px}.inquiry-filters{display:grid;grid-template-columns:2fr 1fr auto;gap:10px}.inquiry-filters input,.inquiry-filters select,.inquiry-status-form select{padding:10px;border:1px solid #cfd8cf}.inquiry-button{display:inline-block;padding:10px 16px;background:#145837;color:#fff;border:0;border-radius:4px;text-decoration:none;cursor:pointer}.inquiry-table-wrap{overflow-x:auto}.inquiry-table{width:100%;border-collapse:collapse;min-width:950px}.inquiry-table th,.inquiry-table td{padding:12px 9px;border-bottom:1px solid #e5e5e5;text-align:left}.inquiry-table th{background:#f3f7f2}.inquiry-status{display:inline-block;padding:5px 10px;border-radius:15px;background:#e8eee8;font-size:11px;font-weight:bold}.inquiry-status.new{background:#fff0cc;color:#704c00}.inquiry-status.replied{background:#e6f6e9;color:#145837}.inquiry-status.closed{background:#eee;color:#666}.inquiry-status-form{display:flex;gap:7px}.inquiry-status-form select{padding:7px}.inquiry-status-form button{padding:8px 11px;background:#145837;color:#fff;border:0;cursor:pointer}.inquiry-message{white-space:pre-wrap;background:#f5f7f2;padding:18px;border-left:4px solid #145837;line-height:1.6}.inquiry-details{display:grid;grid-template-columns:1fr 1fr;gap:20px}.inquiry-details p{margin:5px 0}.inquiry-notice{padding:12px;text-align:center;margin-bottom:20px}.inquiry-notice.success{background:#e6f6e9;color:#145837}.inquiry-notice.error{background:#ffecec;color:#a40000}@media(max-width:900px){.inquiry-summary{grid-template-columns:repeat(2,1fr)}.inquiry-details,.inquiry-filters{grid-template-columns:1fr}}@media(max-width:500px){.inquiry-summary{grid-template-columns:1fr}}
    </style>
</head>
<body class="admin-body">
    <header class="admin-header">
        <a href="index.php" class="admin-logo"><img src="Images/Home/Logo.png" alt="EcoSprout Logo"></a>
        <div class="admin-user"><div class="admin-user-details"><strong><?= inquiryEscape($adminName ?: "Staff Member") ?></strong><span><?= $isAdministrator ? "Administrator" : "Staff" ?></span></div><a href="logout.php" class="admin-logout-button">Log Out</a></div>
    </header>

    <nav class="admin-navigation" aria-label="Admin navigation">
        <a href="admindashboard.php" class="admin-nav-link">Dashboard</a>
        <a href="admin_orders.php" class="admin-nav-link">Orders</a>
        <a href="admin_plants.php" class="admin-nav-link">Plants</a>
        <a href="admin_tools.php" class="admin-nav-link">Tools</a>
        <a href="admin_services.php" class="admin-nav-link">Services</a>
        <a href="admin_inquiries.php" class="admin-nav-link active">Inquiries</a>
        <?php if ($isAdministrator): ?><a href="admin_staff.php" class="admin-nav-link">Manage Staff</a><?php endif; ?>
    </nav>

    <main class="inquiry-main">
        <div class="inquiry-title"><p>EcoSprout Customer Support</p><h1>Manage Inquiries</h1><span>Read customer messages and track your response progress.</span></div>

        <?php if (isset($messages[$result])): ?><div class="inquiry-notice <?= $result === "updated" ? "success" : "error" ?>"><?= inquiryEscape($messages[$result]) ?></div><?php endif; ?>

        <section class="inquiry-summary">
            <article class="inquiry-card"><span>Total</span><strong><?= (int) $summary["total"] ?></strong></article>
            <article class="inquiry-card"><span>New</span><strong><?= (int) $summary["new"] ?></strong></article>
            <article class="inquiry-card"><span>Read</span><strong><?= (int) $summary["read"] ?></strong></article>
            <article class="inquiry-card"><span>Replied</span><strong><?= (int) $summary["replied"] ?></strong></article>
            <article class="inquiry-card"><span>Closed</span><strong><?= (int) $summary["closed"] ?></strong></article>
        </section>

        <?php if ($selectedInquiry): ?>
            <section class="inquiry-panel">
                <h2><?= inquiryEscape($selectedInquiry["subject"]) ?></h2>
                <div class="inquiry-details">
                    <div><p><strong>From:</strong> <?= inquiryEscape($selectedInquiry["full_name"]) ?></p><p><strong>Email:</strong> <?= inquiryEscape($selectedInquiry["email"]) ?></p><p><strong>Phone:</strong> <?= inquiryEscape($selectedInquiry["phone"] ?: "Not provided") ?></p></div>
                    <div><p><strong>Submitted:</strong> <?= date("d M Y, h:i A", strtotime($selectedInquiry["submitted_at"])) ?></p><p><strong>Status:</strong> <?= inquiryEscape(inquiryStatusLabel($selectedInquiry["inquiry_status"])) ?></p><p><strong>Account:</strong> <?= $selectedInquiry["user_id"] ? "Registered customer" : "Guest" ?></p></div>
                </div>
                <h3>Message</h3><div class="inquiry-message"><?= inquiryEscape($selectedInquiry["message"]) ?></div>
                <p><a class="inquiry-button" href="mailto:<?= rawurlencode($selectedInquiry["email"]) ?>?subject=<?= rawurlencode("Re: " . $selectedInquiry["subject"]) ?>">Reply by Email</a></p>
            </section>
        <?php endif; ?>

        <section class="inquiry-panel">
            <form action="admin_inquiries.php" method="get" class="inquiry-filters">
                <input type="search" name="q" value="<?= inquiryEscape($search) ?>" placeholder="Search name, email, phone, subject or message">
                <select name="status"><option value="">All Statuses</option><?php foreach ($allowedStatuses as $value): ?><option value="<?= $value ?>" <?= $statusFilter === $value ? "selected" : "" ?>><?= inquiryEscape(inquiryStatusLabel($value)) ?></option><?php endforeach; ?></select>
                <button type="submit" class="inquiry-button">Filter</button>
            </form>
        </section>

        <section class="inquiry-panel inquiry-table-wrap">
            <table class="inquiry-table">
                <thead><tr><th>Customer</th><th>Subject</th><th>Submitted</th><th>Status</th><th>Update</th><th>Message</th></tr></thead>
                <tbody>
                    <?php if (count($inquiries) === 0): ?><tr><td colspan="6">No matching inquiries found.</td></tr><?php endif; ?>
                    <?php foreach ($inquiries as $inquiry): ?><tr>
                        <td><strong><?= inquiryEscape($inquiry["full_name"]) ?></strong><br><small><?= inquiryEscape($inquiry["email"]) ?></small></td>
                        <td><?= inquiryEscape($inquiry["subject"]) ?></td>
                        <td><?= date("d M Y", strtotime($inquiry["submitted_at"])) ?></td>
                        <td><span class="inquiry-status <?= strtolower($inquiry["inquiry_status"]) ?>"><?= inquiryEscape(inquiryStatusLabel($inquiry["inquiry_status"])) ?></span></td>
                        <td><form action="inquiry_action.php" method="post" class="inquiry-status-form">
                            <input type="hidden" name="inquiry_admin_token" value="<?= inquiryEscape($_SESSION["inquiry_admin_token"]) ?>"><input type="hidden" name="inquiry_id" value="<?= (int) $inquiry["inquiry_id"] ?>">
                            <select name="inquiry_status"><?php foreach ($allowedStatuses as $value): ?><option value="<?= $value ?>" <?= $inquiry["inquiry_status"] === $value ? "selected" : "" ?>><?= inquiryEscape(inquiryStatusLabel($value)) ?></option><?php endforeach; ?></select><button type="submit">Save</button>
                        </form></td>
                        <td><a href="admin_inquiries.php?view=<?= (int) $inquiry["inquiry_id"] ?>" class="inquiry-button">View</a></td>
                    </tr><?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
