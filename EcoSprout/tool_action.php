<?php

require_once "admin_auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_tools.php");
    exit;
}


/* =========================================================
   SECURITY TOKEN
========================================================= */

$submittedToken = $_POST["tool_admin_token"] ?? "";
$savedToken = $_SESSION["tool_admin_token"] ?? "";

if (
    $savedToken === "" ||
    !hash_equals($savedToken, $submittedToken)
) {
    header("Location: admin_tools.php?result=failed");
    exit;
}


require_once "db_connection.php";

$action = $_POST["action"] ?? "";


/* =========================================================
   ACTIVATE / DEACTIVATE TOOL
========================================================= */

if ($action === "toggle_status") {

    $toolId = filter_input(
        INPUT_POST,
        "tool_id",
        FILTER_VALIDATE_INT
    );

    $status = $_POST["status"] ?? "";


    if (
        $toolId === false ||
        $toolId === null ||
        $toolId < 1 ||
        !in_array(
            $status,
            ["ACTIVE", "INACTIVE"],
            true
        )
    ) {

        $conn->close();

        header(
            "Location: admin_tools.php?result=invalid"
        );

        exit;
    }


    $statement = $conn->prepare(
        "UPDATE tools
         SET status = ?
         WHERE tool_id = ?"
    );


    $statement->bind_param(
        "si",
        $status,
        $toolId
    );


    $statement->execute();

    $statement->close();

    $conn->close();


    $_SESSION["tool_admin_token"] =
        bin2hex(random_bytes(32));


    header(
        "Location: admin_tools.php?result=status"
    );

    exit;
}


/* =========================================================
   DELETE TOOL
========================================================= */

if ($action === "delete") {

    $toolId = filter_input(
        INPUT_POST,
        "tool_id",
        FILTER_VALIDATE_INT
    );


    if (
        $toolId === false ||
        $toolId === null ||
        $toolId < 1
    ) {

        $conn->close();

        header(
            "Location: admin_tools.php?result=invalid"
        );

        exit;
    }


    $imagePaths = [];


    try {

        $conn->begin_transaction();


        /* -----------------------------------------
           GET TOOL IMAGES
        ----------------------------------------- */

        $imageQuery = $conn->prepare(
            "SELECT image_path
             FROM tool_images
             WHERE tool_id = ?"
        );


        $imageQuery->bind_param(
            "i",
            $toolId
        );


        $imageQuery->execute();


        $imageQuery->bind_result(
            $imagePath
        );


        while ($imageQuery->fetch()) {

            if (!empty($imagePath)) {

                $imagePaths[] =
                    $imagePath;

            }

        }


        $imageQuery->close();


        /* -----------------------------------------
           DELETE TOOL IMAGE DATABASE RECORDS
        ----------------------------------------- */

        $deleteImages = $conn->prepare(
            "DELETE FROM tool_images
             WHERE tool_id = ?"
        );


        $deleteImages->bind_param(
            "i",
            $toolId
        );


        $deleteImages->execute();

        $deleteImages->close();


        /* -----------------------------------------
           DELETE TOOL
        ----------------------------------------- */

        $deleteTool = $conn->prepare(
            "DELETE FROM tools
             WHERE tool_id = ?"
        );


        $deleteTool->bind_param(
            "i",
            $toolId
        );


        $deleteTool->execute();


        if ($deleteTool->affected_rows !== 1) {

            $deleteTool->close();

            throw new RuntimeException(
                "NOT_FOUND"
            );

        }


        $deleteTool->close();


        /* -----------------------------------------
           SAVE DATABASE CHANGES
        ----------------------------------------- */

        $conn->commit();

        $conn->close();


        /* -----------------------------------------
           DELETE IMAGE FILES
        ----------------------------------------- */

        foreach ($imagePaths as $relativePath) {

            $normalizedPath =
                str_replace(
                    ["/", "\\"],
                    DIRECTORY_SEPARATOR,
                    $relativePath
                );


            $absolutePath =
                __DIR__ .
                DIRECTORY_SEPARATOR .
                ltrim(
                    $normalizedPath,
                    DIRECTORY_SEPARATOR
                );


            if (is_file($absolutePath)) {

                @unlink($absolutePath);

            }

        }


        $_SESSION["tool_admin_token"] =
            bin2hex(random_bytes(32));


        header(
            "Location: admin_tools.php?result=deleted"
        );

        exit;


    } catch (Throwable $error) {

        $conn->rollback();

        $conn->close();


        error_log(
            "EcoSprout tool delete error: " .
            $error->getMessage()
        );


        $result =
            $error->getMessage() === "NOT_FOUND"
                ? "not_found"
                : "delete_failed";


        header(
            "Location: admin_tools.php?result=" .
            $result
        );

        exit;
    }
}


/* =========================================================
   ALLOWED ADD / UPDATE ACTIONS
========================================================= */

if (
    !in_array(
        $action,
        ["add", "update"],
        true
    )
) {

    $conn->close();

    header(
        "Location: admin_tools.php?result=invalid"
    );

    exit;
}


/* =========================================================
   GET FORM VALUES
========================================================= */

$toolId = filter_input(
    INPUT_POST,
    "tool_id",
    FILTER_VALIDATE_INT
);

$categoryId = filter_input(
    INPUT_POST,
    "tool_category_id",
    FILTER_VALIDATE_INT
);

$toolName =
    trim($_POST["tool_name"] ?? "");

$brand =
    trim($_POST["brand"] ?? "");

$price = filter_input(
    INPUT_POST,
    "price",
    FILTER_VALIDATE_FLOAT
);

$quantity = filter_input(
    INPUT_POST,
    "quantity",
    FILTER_VALIDATE_INT
);

$purpose =
    trim($_POST["purpose"] ?? "");

$material =
    trim($_POST["material"] ?? "");

$description =
    trim($_POST["description"] ?? "");

$status =
    $_POST["status"] ?? "ACTIVE";


/* =========================================================
   VALIDATE REQUIRED VALUES
========================================================= */

if (

    $categoryId === false ||
    $categoryId === null ||
    $categoryId < 1 ||

    $toolName === "" ||

    $brand === "" ||

    $description === "" ||

    $price === false ||
    $price < 0 ||

    $quantity === false ||
    $quantity < 0 ||

    (
        $action === "update" &&
        (
            $toolId === false ||
            $toolId === null ||
            $toolId < 1
        )
    )

) {

    $conn->close();

    header(
        "Location: admin_tools.php?result=missing"
    );

    exit;
}


/* =========================================================
   VALID OPTIONS
========================================================= */

$validPurposes = [

    "Digging",
    "Cutting",
    "Watering",
    "Planting",
    "Cleaning",
    "Maintenance"

];


$validMaterials = [

    "",
    "Stainless Steel",
    "Carbon Steel",
    "Plastic",
    "Wood",
    "Aluminium",
    "Other"

];


if (

    !in_array(
        $purpose,
        $validPurposes,
        true
    ) ||

    !in_array(
        $material,
        $validMaterials,
        true
    ) ||

    !in_array(
        $status,
        ["ACTIVE", "INACTIVE"],
        true
    )

) {

    $conn->close();

    header(
        "Location: admin_tools.php?result=invalid"
    );

    exit;
}


$material =
    $material === ""
        ? null
        : $material;


$uploadedFiles = [];


/* =========================================================
   TOOL IMAGE UPLOAD FUNCTION
========================================================= */

function uploadToolImage(
    $fieldName,
    &$uploadedFiles
) {

    if (
        !isset($_FILES[$fieldName]) ||
        $_FILES[$fieldName]["error"] ===
        UPLOAD_ERR_NO_FILE
    ) {

        return null;
    }


    $file =
        $_FILES[$fieldName];


    if (
        $file["error"] !== UPLOAD_ERR_OK ||
        $file["size"] > 5 * 1024 * 1024
    ) {

        throw new RuntimeException(
            "IMAGE"
        );
    }


    $finfo =
        new finfo(FILEINFO_MIME_TYPE);


    $mimeType =
        $finfo->file(
            $file["tmp_name"]
        );


    $extensions = [

        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"

    ];


    if (
        !isset(
            $extensions[$mimeType]
        )
    ) {

        throw new RuntimeException(
            "IMAGE"
        );
    }


    $uploadDirectory =

        __DIR__ .
        DIRECTORY_SEPARATOR .
        "Images" .
        DIRECTORY_SEPARATOR .
        "Tools" .
        DIRECTORY_SEPARATOR .
        "uploads";


    if (
        !is_dir($uploadDirectory) &&
        !mkdir(
            $uploadDirectory,
            0775,
            true
        )
    ) {

        throw new RuntimeException(
            "IMAGE"
        );
    }


    $fileName =

        "tool_" .
        bin2hex(random_bytes(10)) .
        "." .
        $extensions[$mimeType];


    $absolutePath =

        $uploadDirectory .
        DIRECTORY_SEPARATOR .
        $fileName;


    if (
        !move_uploaded_file(
            $file["tmp_name"],
            $absolutePath
        )
    ) {

        throw new RuntimeException(
            "IMAGE"
        );
    }


    $uploadedFiles[] =
        $absolutePath;


    return

        "Images/Tools/uploads/" .
        $fileName;
}


/* =========================================================
   ADD / UPDATE TOOL
========================================================= */

try {

    /* -----------------------------------------
       PROCESS SIX TOOL IMAGES
    ----------------------------------------- */

    $images = [];


    for (
        $imageNumber = 1;
        $imageNumber <= 6;
        $imageNumber++
    ) {

        $fieldName =

            $imageNumber === 1
                ? "main_image"
                : "image_" . $imageNumber;


        $images[$imageNumber] =
            uploadToolImage(
                $fieldName,
                $uploadedFiles
            );
    }


    $conn->begin_transaction();


    /* -----------------------------------------
       ADD TOOL
    ----------------------------------------- */

    if ($action === "add") {

        $statement =
            $conn->prepare(
                "
                INSERT INTO tools (

                    tool_category_id,
                    tool_name,
                    brand,
                    price,
                    quantity,
                    purpose,
                    material,
                    description,
                    status

                )

                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
                "
            );


        $statement->bind_param(

            "issdissss",

            $categoryId,
            $toolName,
            $brand,
            $price,
            $quantity,
            $purpose,
            $material,
            $description,
            $status

        );


        $statement->execute();


        $toolId =
            $conn->insert_id;


        $statement->close();

    }


    /* -----------------------------------------
       UPDATE TOOL
    ----------------------------------------- */

    else {

        $statement =
            $conn->prepare(
                "
                UPDATE tools

                SET

                    tool_category_id = ?,
                    tool_name = ?,
                    brand = ?,
                    price = ?,
                    quantity = ?,
                    purpose = ?,
                    material = ?,
                    description = ?,
                    status = ?

                WHERE tool_id = ?
                "
            );


        $statement->bind_param(

            "issdissssi",

            $categoryId,
            $toolName,
            $brand,
            $price,
            $quantity,
            $purpose,
            $material,
            $description,
            $status,
            $toolId

        );


        $statement->execute();

        $statement->close();

    }


    /* =====================================================
       CHANGE MAIN IMAGE
    ===================================================== */

    if ($images[1] !== null) {

        $clearMain =
            $conn->prepare(
                "
                UPDATE tool_images

                SET is_main = 0

                WHERE tool_id = ?
                "
            );


        $clearMain->bind_param(
            "i",
            $toolId
        );


        $clearMain->execute();

        $clearMain->close();

    }


    /* =====================================================
       SAVE TOOL IMAGES
    ===================================================== */

    $imageStatement =
        $conn->prepare(
            "
            INSERT INTO tool_images (

                tool_id,
                image_path,
                is_main,
                sort_order

            )

            VALUES (?, ?, ?, ?)
            "
        );


    foreach (
        $images
        as $imageNumber => $imagePath
    ) {

        if ($imagePath === null) {

            continue;

        }


        $isMain =
            $imageNumber === 1
                ? 1
                : 0;


        $sortOrder =
            $imageNumber;


        $imageStatement->bind_param(

            "isii",

            $toolId,
            $imagePath,
            $isMain,
            $sortOrder

        );


        $imageStatement->execute();

    }


    $imageStatement->close();


    /* =====================================================
       COMPLETE
    ===================================================== */

    $conn->commit();

    $conn->close();


    $_SESSION["tool_admin_token"] =
        bin2hex(random_bytes(32));


    header(

        "Location: admin_tools.php?result=" .

        (
            $action === "add"
                ? "added"
                : "updated"
        )

    );


    exit;


/* =========================================================
   ERROR HANDLING
========================================================= */

} catch (Throwable $error) {

    $conn->rollback();

    $conn->close();


    /* DELETE NEWLY UPLOADED FILES
       IF DATABASE OPERATION FAILED */

    foreach (
        $uploadedFiles
        as $uploadedFile
    ) {

        if (
            is_file($uploadedFile)
        ) {

            unlink($uploadedFile);

        }

    }


    error_log(

        "EcoSprout tool management error: " .
        $error->getMessage()

    );


    header(

        "Location: admin_tools.php?result=" .

        (
            $error->getMessage() === "IMAGE"
                ? "image"
                : "failed"
        )

    );


    exit;
}