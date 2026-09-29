<?php

require_once "admin_auth.php";


/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: admin_services.php"
    );

    exit;
}


/* =========================================================
   SECURITY TOKEN
========================================================= */

$submittedToken =
    $_POST["service_admin_token"]
    ?? "";


$savedToken =
    $_SESSION["service_admin_token"]
    ?? "";


if (
    $savedToken === "" ||
    !hash_equals(
        $savedToken,
        $submittedToken
    )
) {

    header(
        "Location: admin_services.php?result=failed"
    );

    exit;
}


require_once "db_connection.php";


$action =
    $_POST["action"]
    ?? "";


/* =========================================================
   ACTIVATE / DEACTIVATE SERVICE
========================================================= */

if ($action === "toggle_status") {


    $serviceId =
        filter_input(
            INPUT_POST,
            "service_id",
            FILTER_VALIDATE_INT
        );


    $status =
        $_POST["status"]
        ?? "";


    if (
        $serviceId === false ||
        $serviceId === null ||
        $serviceId < 1 ||
        !in_array(
            $status,
            [
                "ACTIVE",
                "INACTIVE"
            ],
            true
        )
    ) {

        $conn->close();

        header(
            "Location: admin_services.php?result=invalid"
        );

        exit;
    }


    $statement =
        $conn->prepare(
            "
            UPDATE services

            SET status = ?

            WHERE service_id = ?
            "
        );


    $statement->bind_param(
        "si",
        $status,
        $serviceId
    );


    $statement->execute();

    $statement->close();

    $conn->close();


    $_SESSION["service_admin_token"] =
        bin2hex(random_bytes(32));


    header(
        "Location: admin_services.php?result=status"
    );

    exit;
}


/* =========================================================
   UPDATE CUSTOMER REQUEST
========================================================= */

if ($action === "update_request") {


    $requestId =
        filter_input(
            INPUT_POST,
            "service_request_id",
            FILTER_VALIDATE_INT
        );


    $requestStatus =
        $_POST["request_status"]
        ?? "";


    $validRequestStatuses = [

        "PENDING",
        "CONFIRMED",
        "IN_PROGRESS",
        "COMPLETED",
        "CANCELLED"

    ];


    if (
        $requestId === false ||
        $requestId === null ||
        $requestId < 1 ||
        !in_array(
            $requestStatus,
            $validRequestStatuses,
            true
        )
    ) {

        $conn->close();

        header(
            "Location: admin_services.php?result=invalid"
        );

        exit;
    }


    $statement =
        $conn->prepare(
            "
            UPDATE service_requests

            SET request_status = ?

            WHERE service_request_id = ?
            "
        );


    $statement->bind_param(
        "si",
        $requestStatus,
        $requestId
    );


    $statement->execute();

    $statement->close();

    $conn->close();


    $_SESSION["service_admin_token"] =
        bin2hex(random_bytes(32));


    header(
        "Location: admin_services.php?result=request"
    );

    exit;
}


/* =========================================================
   DELETE SERVICE
========================================================= */

if ($action === "delete") {


    $serviceId =
        filter_input(
            INPUT_POST,
            "service_id",
            FILTER_VALIDATE_INT
        );


    if (
        $serviceId === false ||
        $serviceId === null ||
        $serviceId < 1
    ) {

        $conn->close();

        header(
            "Location: admin_services.php?result=invalid"
        );

        exit;
    }


    try {


        /* =================================================
           CHECK SERVICE EXISTS + GET IMAGE
        ================================================= */

        $serviceStatement =
            $conn->prepare(
                "
                SELECT image_path

                FROM services

                WHERE service_id = ?

                LIMIT 1
                "
            );


        $serviceStatement->bind_param(
            "i",
            $serviceId
        );


        $serviceStatement->execute();


        $serviceStatement->bind_result(
            $existingImagePath
        );


        if (!$serviceStatement->fetch()) {

            $serviceStatement->close();

            $conn->close();


            header(
                "Location: admin_services.php?result=invalid"
            );

            exit;
        }


        $serviceStatement->close();


        /* =================================================
           CHECK CUSTOMER REQUESTS

           Do not delete services that have request history.
        ================================================= */

        $requestCheck =
            $conn->prepare(
                "
                SELECT COUNT(*)

                FROM service_requests

                WHERE service_id = ?
                "
            );


        $requestCheck->bind_param(
            "i",
            $serviceId
        );


        $requestCheck->execute();


        $requestCheck->bind_result(
            $requestCount
        );


        $requestCheck->fetch();

        $requestCheck->close();


        if ((int) $requestCount > 0) {


            $conn->close();


            $_SESSION["service_admin_token"] =
                bin2hex(random_bytes(32));


            header(
                "Location: admin_services.php?result=has_requests"
            );


            exit;
        }


        /* =================================================
           DELETE SERVICE
        ================================================= */

        $conn->begin_transaction();


        $deleteStatement =
            $conn->prepare(
                "
                DELETE FROM services

                WHERE service_id = ?
                "
            );


        $deleteStatement->bind_param(
            "i",
            $serviceId
        );


        $deleteStatement->execute();


        if (
            $deleteStatement->affected_rows
            !== 1
        ) {

            $deleteStatement->close();


            throw new RuntimeException(
                "DELETE_FAILED"
            );
        }


        $deleteStatement->close();


        $conn->commit();

        $conn->close();


        /* =================================================
           DELETE SERVICE IMAGE FILE

           Only after database deletion succeeds.
        ================================================= */

        if (!empty($existingImagePath)) {


            $normalizedPath =
                str_replace(
                    ["/", "\\"],
                    DIRECTORY_SEPARATOR,
                    $existingImagePath
                );


            $absolutePath =
                __DIR__ .
                DIRECTORY_SEPARATOR .
                ltrim(
                    $normalizedPath,
                    DIRECTORY_SEPARATOR
                );


            /*
             * Only delete files from the
             * Services uploads directory.
             */

            $uploadsDirectory =
                realpath(
                    __DIR__ .
                    DIRECTORY_SEPARATOR .
                    "Images" .
                    DIRECTORY_SEPARATOR .
                    "Services" .
                    DIRECTORY_SEPARATOR .
                    "uploads"
                );


            $imageRealPath =
                realpath(
                    $absolutePath
                );


            if (
                $uploadsDirectory !== false &&
                $imageRealPath !== false &&
                str_starts_with(
                    $imageRealPath,
                    $uploadsDirectory .
                    DIRECTORY_SEPARATOR
                ) &&
                is_file(
                    $imageRealPath
                )
            ) {

                @unlink(
                    $imageRealPath
                );
            }
        }


        /* NEW TOKEN */

        $_SESSION["service_admin_token"] =
            bin2hex(random_bytes(32));


        header(
            "Location: admin_services.php?result=deleted"
        );


        exit;


    } catch (Throwable $error) {


        if ($conn->errno === 0) {

            /*
             * rollback() is safe here when
             * a transaction was started.
             */

            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }
        }


        $conn->close();


        error_log(
            "EcoSprout service delete error: "
            . $error->getMessage()
        );


        header(
            "Location: admin_services.php?result=delete_failed"
        );


        exit;
    }
}


/* =========================================================
   ONLY ADD / UPDATE FROM THIS POINT
========================================================= */

if (
    !in_array(
        $action,
        [
            "add",
            "update"
        ],
        true
    )
) {

    $conn->close();

    header(
        "Location: admin_services.php?result=invalid"
    );

    exit;
}


/* =========================================================
   GET FORM VALUES
========================================================= */

$serviceId =
    filter_input(
        INPUT_POST,
        "service_id",
        FILTER_VALIDATE_INT
    );


$categoryId =
    filter_input(
        INPUT_POST,
        "service_category_id",
        FILTER_VALIDATE_INT
    );


$serviceName =
    trim(
        $_POST["service_name"]
        ?? ""
    );


$startingPrice =
    filter_input(
        INPUT_POST,
        "starting_price",
        FILTER_VALIDATE_FLOAT
    );


$duration =
    trim(
        $_POST["service_duration"]
        ?? ""
    );


$area =
    trim(
        $_POST["service_area"]
        ?? ""
    );


$description =
    trim(
        $_POST["description"]
        ?? ""
    );


$status =
    $_POST["status"]
    ?? "ACTIVE";


/* =========================================================
   VALIDATE
========================================================= */

if (

    $categoryId === false ||
    $categoryId === null ||
    $categoryId < 1 ||

    $serviceName === "" ||

    $description === "" ||

    $startingPrice === false ||
    $startingPrice < 0 ||

    (
        $action === "update" &&
        (
            $serviceId === false ||
            $serviceId === null ||
            $serviceId < 1
        )
    )

) {

    $conn->close();


    header(
        "Location: admin_services.php?result=missing"
    );


    exit;
}


/* =========================================================
   VALIDATE STATUS
========================================================= */

if (
    !in_array(
        $status,
        [
            "ACTIVE",
            "INACTIVE"
        ],
        true
    )
) {

    $conn->close();


    header(
        "Location: admin_services.php?result=invalid"
    );


    exit;
}


/* =========================================================
   OPTIONAL VALUES
========================================================= */

$duration =
    $duration === ""
        ? null
        : $duration;


$area =
    $area === ""
        ? null
        : $area;


$uploadedAbsolutePath =
    null;


$imagePath =
    null;


/* =========================================================
   ADD / UPDATE SERVICE
========================================================= */

try {


    /* =====================================================
       IMAGE UPLOAD
    ===================================================== */

    if (
        isset(
            $_FILES["service_image"]
        ) &&
        $_FILES["service_image"]["error"]
        !== UPLOAD_ERR_NO_FILE
    ) {


        $file =
            $_FILES["service_image"];


        if (
            $file["error"]
            !== UPLOAD_ERR_OK ||

            $file["size"]
            > 5 * 1024 * 1024
        ) {

            throw new RuntimeException(
                "IMAGE"
            );
        }


        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );


        $mimeType =
            $finfo->file(
                $file["tmp_name"]
            );


        $extensions = [

            "image/jpeg" =>
                "jpg",

            "image/png" =>
                "png",

            "image/webp" =>
                "webp"

        ];


        if (
            !isset(
                $extensions[
                    $mimeType
                ]
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
            "Services" .
            DIRECTORY_SEPARATOR .
            "uploads";


        if (
            !is_dir(
                $uploadDirectory
            ) &&
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

            "service_" .
            bin2hex(
                random_bytes(10)
            ) .
            "." .
            $extensions[
                $mimeType
            ];


        $uploadedAbsolutePath =

            $uploadDirectory .
            DIRECTORY_SEPARATOR .
            $fileName;


        if (
            !move_uploaded_file(
                $file["tmp_name"],
                $uploadedAbsolutePath
            )
        ) {

            throw new RuntimeException(
                "IMAGE"
            );
        }


        $imagePath =

            "Images/Services/uploads/" .
            $fileName;
    }


    /* =====================================================
       DATABASE TRANSACTION
    ===================================================== */

    $conn->begin_transaction();


    /* =====================================================
       ADD SERVICE
    ===================================================== */

    if ($action === "add") {


        $statement =
            $conn->prepare(
                "
                INSERT INTO services (

                    service_category_id,
                    service_name,
                    starting_price,
                    service_duration,
                    service_area,
                    image_path,
                    description,
                    status

                )

                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
                "
            );


        $statement->bind_param(

            "isdsssss",

            $categoryId,
            $serviceName,
            $startingPrice,
            $duration,
            $area,
            $imagePath,
            $description,
            $status

        );

    }


    /* =====================================================
       UPDATE SERVICE
    ===================================================== */

    else {


        $statement =
            $conn->prepare(
                "
                UPDATE services

                SET

                    service_category_id = ?,

                    service_name = ?,

                    starting_price = ?,

                    service_duration = ?,

                    service_area = ?,

                    image_path =
                        COALESCE(
                            ?,
                            image_path
                        ),

                    description = ?,

                    status = ?

                WHERE service_id = ?
                "
            );


        $statement->bind_param(

            "isdsssssi",

            $categoryId,
            $serviceName,
            $startingPrice,
            $duration,
            $area,
            $imagePath,
            $description,
            $status,
            $serviceId

        );
    }


    $statement->execute();

    $statement->close();


    $conn->commit();

    $conn->close();


    /* =====================================================
       CREATE NEW SECURITY TOKEN
    ===================================================== */

    $_SESSION["service_admin_token"] =
        bin2hex(
            random_bytes(32)
        );


    /* =====================================================
       REDIRECT
    ===================================================== */

    header(

        "Location: admin_services.php?result=" .

        (
            $action === "add"
                ? "added"
                : "updated"
        )

    );


    exit;


/* =========================================================
   ERROR
========================================================= */

} catch (Throwable $error) {


    try {

        $conn->rollback();

    } catch (Throwable $ignored) {
    }


    $conn->close();


    /* DELETE NEW IMAGE IF SAVE FAILED */

    if (
        $uploadedAbsolutePath !== null &&
        is_file(
            $uploadedAbsolutePath
        )
    ) {

        unlink(
            $uploadedAbsolutePath
        );
    }


    error_log(

        "EcoSprout service management error: "
        .
        $error->getMessage()

    );


    header(

        "Location: admin_services.php?result=" .

        (
            $error->getMessage()
            === "IMAGE"

                ? "image"

                : "failed"
        )

    );


    exit;
}