<?php

require_once "admin_auth.php";
require_once "db_connection.php";


function serviceAdminEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   SECURITY TOKEN
========================================================= */

if (empty($_SESSION["service_admin_token"])) {
    $_SESSION["service_admin_token"] =
        bin2hex(random_bytes(32));
}


/* =========================================================
   SERVICE CATEGORIES
========================================================= */

$categories = [];

$categoryResult = $conn->query(
    "SELECT service_category_id, category_name
     FROM service_categories
     ORDER BY category_name"
);

while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
}


/* =========================================================
   EDIT SERVICE
========================================================= */

$editService = null;

$editId = filter_input(
    INPUT_GET,
    "edit",
    FILTER_VALIDATE_INT
);

if (
    $editId !== false &&
    $editId !== null &&
    $editId > 0
) {

    $editStatement = $conn->prepare("
        SELECT
            service_id,
            service_category_id,
            service_name,
            starting_price,
            service_duration,
            service_area,
            image_path,
            description,
            status
        FROM services
        WHERE service_id = ?
        LIMIT 1
    ");

    $editStatement->bind_param(
        "i",
        $editId
    );

    $editStatement->execute();

    $editStatement->bind_result(
        $eServiceId,
        $eCategoryId,
        $eName,
        $ePrice,
        $eDuration,
        $eArea,
        $eImagePath,
        $eDescription,
        $eStatus
    );

    if ($editStatement->fetch()) {

        $editService = [
            "service_id" => $eServiceId,
            "service_category_id" => $eCategoryId,
            "service_name" => $eName,
            "starting_price" => $ePrice,
            "service_duration" => $eDuration,
            "service_area" => $eArea,
            "image_path" => $eImagePath,
            "description" => $eDescription,
            "status" => $eStatus
        ];
    }

    $editStatement->close();
}


/* =========================================================
   SERVICE INVENTORY
========================================================= */

$search = trim(
    $_GET["q"] ?? ""
);


$inventorySql = "

    SELECT
        s.service_id,
        s.service_name,
        sc.category_name,
        s.starting_price,
        s.service_duration,
        s.service_area,
        s.image_path,
        s.status,
        COUNT(sr.service_request_id)

    FROM services s

    INNER JOIN service_categories sc
        ON sc.service_category_id =
           s.service_category_id

    LEFT JOIN service_requests sr
        ON sr.service_id =
           s.service_id
";


if ($search !== "") {

    $inventorySql .= "
        WHERE
            s.service_name LIKE ?
            OR
            sc.category_name LIKE ?
    ";
}


$inventorySql .= "

    GROUP BY
        s.service_id,
        s.service_name,
        sc.category_name,
        s.starting_price,
        s.service_duration,
        s.service_area,
        s.image_path,
        s.status

    ORDER BY
        s.updated_at DESC,
        s.service_id DESC
";


$inventoryStatement =
    $conn->prepare($inventorySql);


if ($search !== "") {

    $searchValue =
        "%" . $search . "%";

    $inventoryStatement->bind_param(
        "ss",
        $searchValue,
        $searchValue
    );
}


$inventoryStatement->execute();


$inventoryStatement->bind_result(
    $serviceId,
    $serviceName,
    $categoryName,
    $startingPrice,
    $duration,
    $area,
    $imagePath,
    $status,
    $requestCount
);


$inventory = [];


while ($inventoryStatement->fetch()) {

    $inventory[] = [

        "service_id" =>
            $serviceId,

        "service_name" =>
            $serviceName,

        "category_name" =>
            $categoryName,

        "starting_price" =>
            $startingPrice,

        "duration" =>
            $duration,

        "area" =>
            $area,

        "image_path" =>
            $imagePath,

        "status" =>
            $status,

        "request_count" =>
            $requestCount
    ];
}


$inventoryStatement->close();


/* =========================================================
   CUSTOMER SERVICE REQUESTS
========================================================= */

$requests = [];


$requestStatement = $conn->prepare("

    SELECT
        sr.service_request_id,
        s.service_name,
        sr.customer_name,
        sr.email,
        sr.phone,
        sr.preferred_date,
        sr.request_status,
        sr.requested_at

    FROM service_requests sr

    INNER JOIN services s
        ON s.service_id =
           sr.service_id

    ORDER BY
        sr.requested_at DESC

    LIMIT 50
");


$requestStatement->execute();


$requestStatement->bind_result(
    $requestId,
    $requestServiceName,
    $customerName,
    $email,
    $phone,
    $preferredDate,
    $requestStatus,
    $requestedAt
);


while ($requestStatement->fetch()) {

    $requests[] = [

        "request_id" =>
            $requestId,

        "service_name" =>
            $requestServiceName,

        "customer_name" =>
            $customerName,

        "email" =>
            $email,

        "phone" =>
            $phone,

        "preferred_date" =>
            $preferredDate,

        "request_status" =>
            $requestStatus,

        "requested_at" =>
            $requestedAt
    ];
}


$requestStatement->close();

$conn->close();


/* =========================================================
   RESULT MESSAGES
========================================================= */

$result =
    $_GET["result"] ?? "";


$messages = [

    "added" =>
        "Service added successfully.",

    "updated" =>
        "Service updated successfully.",

    "status" =>
        "Service availability updated successfully.",

    "request" =>
        "Customer request status updated successfully.",

    "deleted" =>
        "Service deleted successfully.",

    "has_requests" =>
        "This service cannot be deleted because it has customer service requests. Deactivate it instead.",

    "delete_failed" =>
        "The service could not be deleted. Please try again.",

    "missing" =>
        "Please complete all required service fields.",

    "invalid" =>
        "One or more values are invalid.",

    "image" =>
        "The image must be JPEG, PNG or WebP and no larger than 5 MB.",

    "failed" =>
        "The change could not be saved. Please try again."
];


$successResults = [
    "added",
    "updated",
    "status",
    "request",
    "deleted"
];


$notice =
    $messages[$result] ?? "";


$adminName = trim(
    ($_SESSION["first_name"] ?? "") .
    " " .
    ($_SESSION["last_name"] ?? "")
);


$requestStatuses = [
    "PENDING",
    "CONFIRMED",
    "IN_PROGRESS",
    "COMPLETED",
    "CANCELLED"
];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Services | EcoSprout
    </title>

    <link
        rel="stylesheet"
        href="stylesheet.css"
    >


    <style>

        .admin-notice {
            margin: 0 0 22px;
            padding: 13px;
            text-align: center;
        }


        .admin-notice.success {
            background: #e6f6e9;
            color: #145837;
        }


        .admin-notice.error {
            background: #ffecec;
            color: #a40000;
        }


        .service-status-label {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 15px;

            font-size: 11px;

            font-weight: bold;
        }


        .service-status-label.active {
            background: #e6f6e9;
            color: #145837;
        }


        .service-status-label.inactive {
            background: #eee;
            color: #666;
        }


        /* =============================================
           SERVICE ACTION BUTTONS
        ============================================= */

        .service-inventory-actions {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 7px;
        }


        .service-inventory-actions form {

            display: inline-flex;

            margin: 0;
        }


        /* UPDATE */

        .service-update-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 34px;

            padding: 7px 12px;

            border-radius: 5px;

            background: #145837;

            border: 1px solid #145837;

            color: white;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;
        }


        .service-update-button:hover {

            background: #0d442a;

            border-color: #0d442a;
        }


        /* DEACTIVATE */

        .service-status-button {

            min-height: 34px;

            border: 1px solid #a66b16;

            background: #fff8ed;

            color: #8a5510;

            padding: 7px 11px;

            border-radius: 5px;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }


        .service-status-button:hover {

            background: #a66b16;

            color: white;
        }


        /* ACTIVATE */

        .service-activate-button {

            border-color: #145837;

            background: #f0faf4;

            color: #145837;
        }


        .service-activate-button:hover {

            background: #145837;

            color: white;
        }


        /* =============================================
           DELETE BUTTON
        ============================================= */

        .service-delete-button {

            min-height: 34px;

            padding: 7px 12px;

            border:

                1px solid
                #c63b3b;

            border-radius: 5px;

            background: white;

            color: #b92727;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background-color .2s ease,
                color .2s ease,
                border-color .2s ease;
        }


        .service-delete-button:hover {

            background: #b92727;

            border-color: #b92727;

            color: white;
        }


        /* SERVICE REQUESTS */

        .request-card {

            margin-top: 30px;
        }


        .request-status-form {

            display: flex;

            gap: 6px;
        }


        .request-status-form select {

            padding: 7px;

            border: 1px solid #ccc;
        }


        .request-status-form button {

            padding: 7px 10px;

            background: #145837;

            color: white;

            border: 0;

            cursor: pointer;
        }


        @media (max-width: 700px) {

            .service-inventory-actions {

                min-width: 210px;
            }

        }

    </style>

</head>


<body class="admin-services-body">


<!-- ========================================================
     ADMIN HEADER
========================================================= -->

<header class="admin-header">

    <a
        href="index.php"
        class="admin-logo"
    >

        <img
            src="Images/Home/Logo.png"
            alt="EcoSprout Logo"
        >

    </a>


    <div class="admin-user">

        <div class="admin-user-details">

            <strong>

                <?= serviceAdminEscape(
                    $adminName
                    ?: "Administrator"
                ) ?>

            </strong>

        </div>


        <a
            href="logout.php"
            class="admin-logout-button"
        >
            Log Out
        </a>

    </div>

</header>


<!-- ========================================================
     NAVIGATION
========================================================= -->

<nav class="admin-navigation">


    <a
        href="admindashboard.php"
        class="admin-nav-link"
    >
        Dashboard
    </a>


    <a
        href="admin_orders.php"
        class="admin-nav-link"
    >
        Orders
    </a>


    <a
        href="admin_plants.php"
        class="admin-nav-link"
    >
        Plants
    </a>


    <a
        href="admin_tools.php"
        class="admin-nav-link"
    >
        Tools
    </a>


    <a
        href="admin_services.php"
        class="admin-nav-link"
    >
        Services
    </a>


    <a
        href="admin_inquiries.php"
        class="admin-nav-link"
    >
        Inquiries
    </a>


    <?php if ($isAdministrator): ?>

        <a
            href="admin_staff.php"
            class="admin-nav-link"
        >
            Manage Staff
        </a>

    <?php endif; ?>


</nav>


<!-- ========================================================
     MAIN
========================================================= -->

<main class="admin-services-main">


    <!-- PAGE HEADING -->

    <div class="services-page-heading">

        <p>
            EcoSprout Services
        </p>

        <h1>
            Manage Services
        </h1>

        <span>
            Add services, manage availability
            and process customer requests.
        </span>

    </div>


    <!-- ====================================================
         NOTIFICATION
    ===================================================== -->

    <?php if ($notice !== ""): ?>

        <div
            class="admin-notice <?= in_array(
                $result,
                $successResults,
                true
            )
                ? "success"
                : "error"
            ?>"
        >

            <?= serviceAdminEscape(
                $notice
            ) ?>

        </div>

    <?php endif; ?>


    <!-- ====================================================
         SERVICE FORM
    ===================================================== -->

    <section class="service-form-card">


        <div class="service-section-heading">

            <h2>

                <?= $editService
                    ? "Update Service"
                    : "Add New Service"
                ?>

            </h2>


            <p>

                <?= $editService

                    ? "Edit the selected service."

                    : "Enter the service information and upload an image."

                ?>

            </p>

        </div>


        <form
            action="service_action.php"
            method="post"
            enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="service_admin_token"
                value="<?= serviceAdminEscape(
                    $_SESSION[
                        "service_admin_token"
                    ]
                ) ?>"
            >


            <input
                type="hidden"
                name="action"
                value="<?= $editService
                    ? "update"
                    : "add"
                ?>"
            >


            <?php if ($editService): ?>

                <input
                    type="hidden"
                    name="service_id"
                    value="<?= (int)
                        $editService[
                            "service_id"
                        ]
                    ?>"
                >

            <?php endif; ?>


            <!-- =================================================
                 SERVICE FIELDS
            ================================================== -->

            <div class="service-fields">


                <!-- SERVICE NAME -->

                <div
                    class="service-field
                           service-name-field"
                >

                    <label for="serviceName">

                        Service Name
                        <span>*</span>

                    </label>


                    <input
                        type="text"
                        id="serviceName"
                        name="service_name"
                        required
                        value="<?= serviceAdminEscape(
                            $editService[
                                "service_name"
                            ]
                            ?? ""
                        ) ?>"
                    >

                </div>


                <!-- CATEGORY -->

                <div class="service-field">

                    <label for="serviceCategory">

                        Service Category
                        <span>*</span>

                    </label>


                    <select
                        id="serviceCategory"
                        name="service_category_id"
                        required
                    >

                        <option value="">
                            Select category
                        </option>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>

                            <option

                                value="<?= (int)
                                    $category[
                                        "service_category_id"
                                    ]
                                ?>"

                                <?= (
                                    (int)
                                    (
                                        $editService[
                                            "service_category_id"
                                        ]
                                        ?? 0
                                    )
                                    ===
                                    (int)
                                    $category[
                                        "service_category_id"
                                    ]
                                )
                                    ? "selected"
                                    : ""
                                ?>

                            >

                                <?= serviceAdminEscape(
                                    $category[
                                        "category_name"
                                    ]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- STARTING PRICE -->

                <div class="service-field">

                    <label for="startingPrice">

                        Starting Price
                        <span>*</span>

                    </label>


                    <input
                        type="number"
                        id="startingPrice"
                        name="starting_price"
                        min="0"
                        step="0.01"
                        required
                        value="<?= serviceAdminEscape(
                            $editService[
                                "starting_price"
                            ]
                            ?? ""
                        ) ?>"
                    >

                </div>


                <!-- DURATION -->

                <div class="service-field">

                    <label for="serviceDuration">

                        Estimated Duration

                    </label>


                    <input
                        type="text"
                        id="serviceDuration"
                        name="service_duration"
                        value="<?= serviceAdminEscape(
                            $editService[
                                "service_duration"
                            ]
                            ?? ""
                        ) ?>"
                        placeholder="Example: 2-3 hours"
                    >

                </div>


                <!-- AREA -->

                <div class="service-field">

                    <label for="serviceArea">

                        Service Area

                    </label>


                    <input
                        type="text"
                        id="serviceArea"
                        name="service_area"
                        value="<?= serviceAdminEscape(
                            $editService[
                                "service_area"
                            ]
                            ?? ""
                        ) ?>"
                        placeholder="Example: Kegalle"
                    >

                </div>


                <!-- STATUS -->

                <div class="service-field">

                    <label for="serviceStatus">
                        Status
                    </label>


                    <select
                        id="serviceStatus"
                        name="status"
                    >

                        <option
                            value="ACTIVE"

                            <?= (
                                (
                                    $editService[
                                        "status"
                                    ]
                                    ?? "ACTIVE"
                                )
                                === "ACTIVE"
                            )
                                ? "selected"
                                : ""
                            ?>
                        >

                            Active

                        </option>


                        <option
                            value="INACTIVE"

                            <?= (
                                (
                                    $editService[
                                        "status"
                                    ]
                                    ?? ""
                                )
                                === "INACTIVE"
                            )
                                ? "selected"
                                : ""
                            ?>
                        >

                            Inactive

                        </option>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 IMAGE + DESCRIPTION
            ================================================== -->

            <div class="service-extra-details">


                <fieldset class="service-images">

                    <legend>
                        Add Service Image
                    </legend>


                    <label
                        class="service-image-upload"
                    >

                        <span>
                            +
                        </span>

                        <strong>
                            Upload Image
                        </strong>

                        <small>
                            JPG, PNG or WebP
                        </small>


                        <input
                            type="file"
                            name="service_image"
                            accept="image/jpeg,image/png,image/webp"
                        >

                    </label>


                    <?php if ($editService): ?>

                        <small>

                            Leave empty to retain
                            the current image.

                        </small>

                    <?php endif; ?>

                </fieldset>


                <!-- DESCRIPTION -->

                <div
                    class="service-field
                           service-description"
                >

                    <label for="serviceDescription">

                        Description
                        <span>*</span>

                    </label>


                    <textarea
                        id="serviceDescription"
                        name="description"
                        rows="9"
                        required
                    ><?= serviceAdminEscape(
                        $editService[
                            "description"
                        ]
                        ?? ""
                    ) ?></textarea>

                </div>

            </div>


            <!-- =================================================
                 FORM BUTTONS
            ================================================== -->

            <div class="service-form-buttons">


                <?php if ($editService): ?>

                    <a
                        href="admin_services.php"
                        class="service-clear-button"
                    >
                        Cancel
                    </a>

                <?php else: ?>

                    <button
                        type="reset"
                        class="service-clear-button"
                    >
                        Clear
                    </button>

                <?php endif; ?>


                <button
                    type="submit"
                    class="add-service-button"
                >

                    <?= $editService
                        ? "Update Service"
                        : "Add New Service"
                    ?>

                </button>

            </div>

        </form>

    </section>


    <!-- ====================================================
         SERVICE INVENTORY
    ===================================================== -->

    <section class="service-inventory-card">


        <div class="service-inventory-heading">


            <div>

                <h2>
                    Service Inventory
                </h2>

                <p>
                    View and manage all services.
                </p>

            </div>


            <form
                action="admin_services.php"
                method="get"
            >

                <input
                    type="search"
                    name="q"
                    class="service-search"
                    value="<?= serviceAdminEscape(
                        $search
                    ) ?>"
                    placeholder="Search services..."
                >

            </form>

        </div>


        <div class="service-table-container">


            <table class="service-inventory-table">


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Image</th>

                        <th>Service</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Requests</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count($inventory) === 0
                ): ?>

                    <tr>

                        <td colspan="8">

                            No services found.

                        </td>

                    </tr>

                <?php endif; ?>


                <?php foreach (
                    $inventory
                    as $service
                ): ?>


                    <tr>


                        <!-- ID -->

                        <td>

                            #S<?= str_pad(
                                (string)
                                $service[
                                    "service_id"
                                ],
                                3,
                                "0",
                                STR_PAD_LEFT
                            ) ?>

                        </td>


                        <!-- IMAGE -->

                        <td>

                            <img

                                src="<?= serviceAdminEscape(
                                    $service[
                                        "image_path"
                                    ]
                                    ?: "Images/Home/Logo.png"
                                ) ?>"

                                alt="<?= serviceAdminEscape(
                                    $service[
                                        "service_name"
                                    ]
                                ) ?>"

                            >

                        </td>


                        <!-- SERVICE NAME -->

                        <td>

                            <strong>

                                <?= serviceAdminEscape(
                                    $service[
                                        "service_name"
                                    ]
                                ) ?>

                            </strong>

                        </td>


                        <!-- CATEGORY -->

                        <td>

                            <?= serviceAdminEscape(
                                $service[
                                    "category_name"
                                ]
                            ) ?>

                        </td>


                        <!-- PRICE -->

                        <td>

                            Rs.

                            <?= number_format(
                                (float)
                                $service[
                                    "starting_price"
                                ],
                                2
                            ) ?>

                        </td>


                        <!-- REQUEST COUNT -->

                        <td>

                            <?= (int)
                                $service[
                                    "request_count"
                                ]
                            ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <span
                                class="service-status-label <?= strtolower(
                                    $service[
                                        "status"
                                    ]
                                ) ?>"
                            >

                                <?= serviceAdminEscape(
                                    $service[
                                        "status"
                                    ]
                                ) ?>

                            </span>

                        </td>


                        <!-- =================================================
                             ACTIONS
                        ================================================== -->

                        <td class="service-inventory-actions">


                            <!-- UPDATE -->

                            <a
                                href="admin_services.php?edit=<?= (int)
                                    $service[
                                        "service_id"
                                    ]
                                ?>"
                                class="service-update-button"
                            >

                                Update

                            </a>


                            <!-- ACTIVATE / DEACTIVATE -->

                            <form
                                action="service_action.php"
                                method="post"
                            >


                                <input
                                    type="hidden"
                                    name="service_admin_token"
                                    value="<?= serviceAdminEscape(
                                        $_SESSION[
                                            "service_admin_token"
                                        ]
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="action"
                                    value="toggle_status"
                                >


                                <input
                                    type="hidden"
                                    name="service_id"
                                    value="<?= (int)
                                        $service[
                                            "service_id"
                                        ]
                                    ?>"
                                >


                                <input
                                    type="hidden"
                                    name="status"
                                    value="<?= (
                                        $service[
                                            "status"
                                        ]
                                        === "ACTIVE"
                                    )
                                        ? "INACTIVE"
                                        : "ACTIVE"
                                    ?>"
                                >


                                <button

                                    type="submit"

                                    class="service-status-button <?= (
                                        $service[
                                            "status"
                                        ]
                                        === "INACTIVE"
                                    )
                                        ? "service-activate-button"
                                        : ""
                                    ?>"

                                >

                                    <?= (
                                        $service[
                                            "status"
                                        ]
                                        === "ACTIVE"
                                    )
                                        ? "Deactivate"
                                        : "Activate"
                                    ?>

                                </button>

                            </form>


                            <!-- =============================================
                                 DELETE
                            ============================================== -->

                            <form

                                action="service_action.php"

                                method="post"

                                class="service-delete-form"

                                onsubmit="return confirm('Are you sure you want to permanently delete this service? This action cannot be undone.');"

                            >


                                <input
                                    type="hidden"
                                    name="service_admin_token"
                                    value="<?= serviceAdminEscape(
                                        $_SESSION[
                                            "service_admin_token"
                                        ]
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >


                                <input
                                    type="hidden"
                                    name="service_id"
                                    value="<?= (int)
                                        $service[
                                            "service_id"
                                        ]
                                    ?>"
                                >


                                <button
                                    type="submit"
                                    class="service-delete-button"
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


    <!-- ====================================================
         CUSTOMER SERVICE REQUESTS
    ===================================================== -->

    <section
        class="service-inventory-card
               request-card"
    >


        <div class="service-inventory-heading">

            <div>

                <h2>
                    Customer Service Requests
                </h2>

                <p>
                    Update the progress of
                    recent requests.
                </p>

            </div>

        </div>


        <div class="service-table-container">


            <table class="service-inventory-table">


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Service</th>

                        <th>Customer</th>

                        <th>Contact</th>

                        <th>Preferred Date</th>

                        <th>Requested</th>

                        <th>Status</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count($requests) === 0
                ): ?>

                    <tr>

                        <td colspan="7">

                            No service requests
                            found.

                        </td>

                    </tr>

                <?php endif; ?>


                <?php foreach (
                    $requests
                    as $request
                ): ?>


                    <tr>


                        <td>

                            #R<?= str_pad(
                                (string)
                                $request[
                                    "request_id"
                                ],
                                3,
                                "0",
                                STR_PAD_LEFT
                            ) ?>

                        </td>


                        <td>

                            <?= serviceAdminEscape(
                                $request[
                                    "service_name"
                                ]
                            ) ?>

                        </td>


                        <td>

                            <?= serviceAdminEscape(
                                $request[
                                    "customer_name"
                                ]
                            ) ?>

                        </td>


                        <td>

                            <?= serviceAdminEscape(
                                $request[
                                    "email"
                                ]
                            ) ?>

                            <br>

                            <?= serviceAdminEscape(
                                $request[
                                    "phone"
                                ]
                            ) ?>

                        </td>


                        <td>

                            <?= $request[
                                "preferred_date"
                            ]
                                ? date(
                                    "d M Y",
                                    strtotime(
                                        $request[
                                            "preferred_date"
                                        ]
                                    )
                                )
                                : "Not specified"
                            ?>

                        </td>


                        <td>

                            <?= date(
                                "d M Y",
                                strtotime(
                                    $request[
                                        "requested_at"
                                    ]
                                )
                            ) ?>

                        </td>


                        <td>


                            <form
                                action="service_action.php"
                                method="post"
                                class="request-status-form"
                            >


                                <input
                                    type="hidden"
                                    name="service_admin_token"
                                    value="<?= serviceAdminEscape(
                                        $_SESSION[
                                            "service_admin_token"
                                        ]
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="action"
                                    value="update_request"
                                >


                                <input
                                    type="hidden"
                                    name="service_request_id"
                                    value="<?= (int)
                                        $request[
                                            "request_id"
                                        ]
                                    ?>"
                                >


                                <select
                                    name="request_status"
                                >


                                    <?php foreach (
                                        $requestStatuses
                                        as $statusOption
                                    ): ?>


                                        <option

                                            value="<?= $statusOption ?>"

                                            <?= (
                                                $request[
                                                    "request_status"
                                                ]
                                                ===
                                                $statusOption
                                            )
                                                ? "selected"
                                                : ""
                                            ?>

                                        >

                                            <?= serviceAdminEscape(
                                                ucwords(
                                                    strtolower(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $statusOption
                                                        )
                                                    )
                                                )
                                            ) ?>

                                        </option>


                                    <?php endforeach; ?>


                                </select>


                                <button type="submit">

                                    Save

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