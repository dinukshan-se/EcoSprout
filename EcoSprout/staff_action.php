<?php

require_once "admin_auth.php";


/* =========================================================
   ADMIN ONLY
========================================================= */

if (!$isAdministrator) {

    header(
        "Location: admindashboard.php?access=denied"
    );

    exit;
}


/* =========================================================
   ONLY ALLOW POST REQUESTS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: admin_staff.php"
    );

    exit;
}


/* =========================================================
   SECURITY TOKEN
========================================================= */

$submittedToken =
    $_POST["staff_admin_token"] ?? "";

$sessionToken =
    $_SESSION["staff_admin_token"] ?? "";


if (
    $sessionToken === "" ||
    !hash_equals(
        $sessionToken,
        $submittedToken
    )
) {

    header(
        "Location: admin_staff.php?result=invalid"
    );

    exit;
}


/* =========================================================
   DATABASE
========================================================= */

require_once "db_connection.php";


/* =========================================================
   RETURN TO STAFF PAGE
========================================================= */

function returnToStaffPage(
    $result,
    $editId = 0
) {

    $location =
        "admin_staff.php?result=" .
        urlencode($result);


    if ($editId > 0) {

        $location .=
            "&edit=" .
            (int) $editId;
    }


    header(
        "Location: " . $location
    );

    exit;
}


/* =========================================================
   OPTIONAL STAFF VALUE
========================================================= */

function optionalStaffValue($value)
{

    $value =
        trim(
            (string) $value
        );


    return $value === ""
        ? null
        : $value;
}


/* =========================================================
   GET ACTION
========================================================= */

$action =
    $_POST["action"] ?? "";


/* =========================================================
   ACTIVATE / DEACTIVATE ACCOUNT
========================================================= */

if ($action === "toggle_status") {


    $userId =
        filter_input(
            INPUT_POST,
            "user_id",
            FILTER_VALIDATE_INT
        );


    $status =
        $_POST["status"] ?? "";


    /* -----------------------------------------------------
       VALIDATE
    ----------------------------------------------------- */

    if (
        !$userId ||
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

        returnToStaffPage(
            "invalid"
        );
    }


    /* -----------------------------------------------------
       UPDATE STATUS
    ----------------------------------------------------- */

    $statement =
        $conn->prepare(
            "
            UPDATE users

            SET status = ?

            WHERE user_id = ?

            AND role IN (
                'ADMIN',
                'STAFF'
            )
            "
        );


    $statement->bind_param(
        "si",
        $status,
        $userId
    );


    $statement->execute();


    $changed =
        $statement->affected_rows;


    $statement->close();

    $conn->close();


    /* NEW SECURITY TOKEN */

    $_SESSION["staff_admin_token"] =
        bin2hex(
            random_bytes(32)
        );


    returnToStaffPage(
        $changed > 0
            ? "status"
            : "missing"
    );
}


/* =========================================================
   DELETE STAFF / ADMIN ACCOUNT
========================================================= */

if ($action === "delete") {


    $userId =
        filter_input(
            INPUT_POST,
            "user_id",
            FILTER_VALIDATE_INT
        );


    /* -----------------------------------------------------
       VALIDATE USER ID
    ----------------------------------------------------- */

    if (
        $userId === false ||
        $userId === null ||
        $userId < 1
    ) {

        $conn->close();

        returnToStaffPage(
            "invalid"
        );
    }


    /* -----------------------------------------------------
       PREVENT CURRENT ADMIN FROM DELETING OWN ACCOUNT
    ----------------------------------------------------- */

    $loggedInUserId =
        (int) (
            $_SESSION["user_id"]
            ?? 0
        );


    if (
        $loggedInUserId > 0 &&
        $userId === $loggedInUserId
    ) {

        $conn->close();


        $_SESSION["staff_admin_token"] =
            bin2hex(
                random_bytes(32)
            );


        returnToStaffPage(
            "self_delete"
        );
    }


    try {


        /* -------------------------------------------------
           CONFIRM TARGET IS ADMIN OR STAFF
        ------------------------------------------------- */

        $checkStatement =
            $conn->prepare(
                "
                SELECT
                    user_id,
                    role

                FROM users

                WHERE user_id = ?

                AND role IN (
                    'ADMIN',
                    'STAFF'
                )

                LIMIT 1
                "
            );


        $checkStatement->bind_param(
            "i",
            $userId
        );


        $checkStatement->execute();


        $checkStatement->store_result();


        if (
            $checkStatement->num_rows !== 1
        ) {

            $checkStatement->close();

            $conn->close();


            $_SESSION["staff_admin_token"] =
                bin2hex(
                    random_bytes(32)
                );


            returnToStaffPage(
                "missing"
            );
        }


        $checkStatement->close();


        /* -------------------------------------------------
           DELETE USER
        ------------------------------------------------- */

        $deleteStatement =
            $conn->prepare(
                "
                DELETE FROM users

                WHERE user_id = ?

                AND role IN (
                    'ADMIN',
                    'STAFF'
                )
                "
            );


        $deleteStatement->bind_param(
            "i",
            $userId
        );


        $deleteStatement->execute();


        $deleted =
            $deleteStatement->affected_rows;


        $deleteStatement->close();

        $conn->close();


        /* -------------------------------------------------
           CREATE NEW TOKEN
        ------------------------------------------------- */

        $_SESSION["staff_admin_token"] =
            bin2hex(
                random_bytes(32)
            );


        /* -------------------------------------------------
           RESULT
        ------------------------------------------------- */

        if ($deleted === 1) {

            returnToStaffPage(
                "deleted"
            );
        }


        returnToStaffPage(
            "missing"
        );


    } catch (Throwable $error) {


        $conn->close();


        error_log(
            "EcoSprout staff delete error: " .
            $error->getMessage()
        );


        $_SESSION["staff_admin_token"] =
            bin2hex(
                random_bytes(32)
            );


        returnToStaffPage(
            "delete_failed"
        );
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

    returnToStaffPage(
        "invalid"
    );
}


/* =========================================================
   GET USER ID
========================================================= */

$userId =
    $action === "update"

        ? filter_input(
            INPUT_POST,
            "user_id",
            FILTER_VALIDATE_INT
        )

        : 0;


/* =========================================================
   GET FORM VALUES
========================================================= */

$employeeCode =
    trim(
        $_POST["employee_code"]
        ?? ""
    );


$firstName =
    trim(
        $_POST["first_name"]
        ?? ""
    );


$lastName =
    trim(
        $_POST["last_name"]
        ?? ""
    );


$email =
    strtolower(
        trim(
            $_POST["email"]
            ?? ""
        )
    );


$phone =
    optionalStaffValue(
        $_POST["phone"]
        ?? ""
    );


$alternativePhone =
    optionalStaffValue(
        $_POST["alternative_phone"]
        ?? ""
    );


$address1 =
    optionalStaffValue(
        $_POST["address_line_1"]
        ?? ""
    );


$address2 =
    optionalStaffValue(
        $_POST["address_line_2"]
        ?? ""
    );


$city =
    optionalStaffValue(
        $_POST["city"]
        ?? ""
    );


$postalCode =
    optionalStaffValue(
        $_POST["postal_code"]
        ?? ""
    );


$status =
    $_POST["status"]
    ?? "ACTIVE";


$role =
    $_POST["role"]
    ?? "STAFF";


$password =
    $_POST["password"]
    ?? "";


$confirmPassword =
    $_POST["confirm_password"]
    ?? "";


/* =========================================================
   VALIDATE ACCOUNT INFORMATION
========================================================= */

if (

    (
        $action === "update" &&
        !$userId
    )

    ||

    $employeeCode === ""

    ||

    $firstName === ""

    ||

    $lastName === ""

    ||

    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )

    ||

    !in_array(
        $role,
        [
            "ADMIN",
            "STAFF"
        ],
        true
    )

    ||

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


    returnToStaffPage(
        "invalid",
        (int) $userId
    );
}


/* =========================================================
   PASSWORD VALIDATION
========================================================= */

$passwordIsRequired =
    $action === "add";


/* PASSWORD REQUIRED FOR NEW ACCOUNT */

if (
    $passwordIsRequired &&
    $password === ""
) {

    $conn->close();


    returnToStaffPage(
        "password",
        (int) $userId
    );
}


/* PASSWORD MUST BE AT LEAST 8 CHARACTERS
   AND BOTH PASSWORDS MUST MATCH */

if (
    $password !== "" &&
    (
        strlen($password) < 8 ||
        $password !== $confirmPassword
    )
) {

    $conn->close();


    returnToStaffPage(
        "password",
        (int) $userId
    );
}


/* =========================================================
   CHECK DUPLICATE EMAIL / EMPLOYEE CODE
========================================================= */

$excludedId =
    (int) $userId;


$duplicateStatement =
    $conn->prepare(
        "
        SELECT user_id

        FROM users

        WHERE
            (
                email = ?
                OR
                employee_code = ?
            )

        AND user_id <> ?

        LIMIT 1
        "
    );


$duplicateStatement->bind_param(
    "ssi",
    $email,
    $employeeCode,
    $excludedId
);


$duplicateStatement->execute();


$duplicateStatement->store_result();


$duplicateExists =
    $duplicateStatement->num_rows > 0;


$duplicateStatement->close();


if ($duplicateExists) {

    $conn->close();


    returnToStaffPage(
        "duplicate",
        $excludedId
    );
}


/* =========================================================
   ADD NEW ACCOUNT
========================================================= */

if ($action === "add") {


    /* -----------------------------------------------------
       PASSWORD HASH
    ----------------------------------------------------- */

    $passwordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    /* -----------------------------------------------------
       INSERT ACCOUNT
    ----------------------------------------------------- */

    $statement =
        $conn->prepare(
            "
            INSERT INTO users (

                employee_code,
                first_name,
                last_name,
                email,
                phone,
                alternative_phone,
                address_line_1,
                address_line_2,
                city,
                postal_code,
                password_hash,
                role,
                status

            )

            VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
            "
        );


    $statement->bind_param(

        "sssssssssssss",

        $employeeCode,
        $firstName,
        $lastName,
        $email,
        $phone,
        $alternativePhone,
        $address1,
        $address2,
        $city,
        $postalCode,
        $passwordHash,
        $role,
        $status

    );


    try {

        $statement->execute();

    } catch (Throwable $error) {

        $statement->close();

        $conn->close();


        error_log(
            "EcoSprout staff add error: " .
            $error->getMessage()
        );


        returnToStaffPage(
            "failed"
        );
    }


    $statement->close();

    $conn->close();


    /* NEW TOKEN */

    $_SESSION["staff_admin_token"] =
        bin2hex(
            random_bytes(32)
        );


    returnToStaffPage(
        "added"
    );
}


/* =========================================================
   UPDATE ACCOUNT
========================================================= */


/* =========================================================
   UPDATE WITH NEW PASSWORD
========================================================= */

if ($password !== "") {


    $passwordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    $statement =
        $conn->prepare(
            "
            UPDATE users

            SET

                employee_code = ?,

                first_name = ?,

                last_name = ?,

                email = ?,

                phone = ?,

                alternative_phone = ?,

                address_line_1 = ?,

                address_line_2 = ?,

                city = ?,

                postal_code = ?,

                password_hash = ?,

                role = ?,

                status = ?

            WHERE user_id = ?

            AND role IN (
                'ADMIN',
                'STAFF'
            )
            "
        );


    $statement->bind_param(

        "sssssssssssssi",

        $employeeCode,
        $firstName,
        $lastName,
        $email,
        $phone,
        $alternativePhone,
        $address1,
        $address2,
        $city,
        $postalCode,
        $passwordHash,
        $role,
        $status,
        $userId

    );

}


/* =========================================================
   UPDATE WITHOUT CHANGING PASSWORD
========================================================= */

else {


    $statement =
        $conn->prepare(
            "
            UPDATE users

            SET

                employee_code = ?,

                first_name = ?,

                last_name = ?,

                email = ?,

                phone = ?,

                alternative_phone = ?,

                address_line_1 = ?,

                address_line_2 = ?,

                city = ?,

                postal_code = ?,

                role = ?,

                status = ?

            WHERE user_id = ?

            AND role IN (
                'ADMIN',
                'STAFF'
            )
            "
        );


    $statement->bind_param(

        "ssssssssssssi",

        $employeeCode,
        $firstName,
        $lastName,
        $email,
        $phone,
        $alternativePhone,
        $address1,
        $address2,
        $city,
        $postalCode,
        $role,
        $status,
        $userId

    );

}


/* =========================================================
   EXECUTE UPDATE
========================================================= */

try {

    $statement->execute();


} catch (Throwable $error) {


    $statement->close();

    $conn->close();


    error_log(
        "EcoSprout staff update error: " .
        $error->getMessage()
    );


    returnToStaffPage(
        "failed",
        (int) $userId
    );
}


$statement->close();

$conn->close();


/* =========================================================
   CREATE NEW SECURITY TOKEN
========================================================= */

$_SESSION["staff_admin_token"] =
    bin2hex(
        random_bytes(32)
    );


/* =========================================================
   SUCCESS
========================================================= */

returnToStaffPage(
    "updated"
);