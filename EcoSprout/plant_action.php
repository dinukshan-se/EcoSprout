<?php

require_once "admin_auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_plants.php");
    exit;
}

$submittedToken = $_POST["plant_admin_token"] ?? "";
$savedToken = $_SESSION["plant_admin_token"] ?? "";
if ($savedToken === "" || !hash_equals($savedToken, $submittedToken)) {
    header("Location: admin_plants.php?result=failed");
    exit;
}

require_once "db_connection.php";
$action = $_POST["action"] ?? "";

if ($action === "toggle_status") {
    $plantId = filter_input(INPUT_POST, "plant_id", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";

    if ($plantId === false || $plantId === null || $plantId < 1 ||
        !in_array($status, ["ACTIVE", "INACTIVE"], true)) {
        $conn->close();
        header("Location: admin_plants.php?result=invalid");
        exit;
    }

    $statement = $conn->prepare("UPDATE plants SET status = ? WHERE plant_id = ?");
    $statement->bind_param("si", $status, $plantId);
    $statement->execute();
    $statement->close();
    $conn->close();
    header("Location: admin_plants.php?result=status");
    exit;
}

if ($action === "delete") {
    $plantId = filter_input(INPUT_POST, "plant_id", FILTER_VALIDATE_INT);
    if ($plantId === false || $plantId === null || $plantId < 1) {
        $conn->close();
        header("Location: admin_plants.php?result=invalid");
        exit;
    }

    $imagePaths = [];
    try {
        $conn->begin_transaction();

        $imageQuery = $conn->prepare("SELECT image_path FROM plant_images WHERE plant_id = ?");
        $imageQuery->bind_param("i", $plantId);
        $imageQuery->execute();
        $imageQuery->bind_result($imagePath);
        while ($imageQuery->fetch()) { if (!empty($imagePath)) $imagePaths[] = $imagePath; }
        $imageQuery->close();

        $deleteImages = $conn->prepare("DELETE FROM plant_images WHERE plant_id = ?");
        $deleteImages->bind_param("i", $plantId);
        $deleteImages->execute();
        $deleteImages->close();

        $deletePlant = $conn->prepare("DELETE FROM plants WHERE plant_id = ?");
        $deletePlant->bind_param("i", $plantId);
        $deletePlant->execute();
        if ($deletePlant->affected_rows !== 1) { $deletePlant->close(); throw new RuntimeException("NOT_FOUND"); }
        $deletePlant->close();

        $conn->commit();
        $conn->close();

        foreach ($imagePaths as $relativePath) {
            $normalized = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $relativePath);
            $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
            if (is_file($absolutePath)) @unlink($absolutePath);
        }

        $_SESSION["plant_admin_token"] = bin2hex(random_bytes(32));
        header("Location: admin_plants.php?result=deleted");
        exit;
    } catch (Throwable $error) {
        $conn->rollback();
        $conn->close();
        error_log("EcoSprout plant delete error: " . $error->getMessage());
        header("Location: admin_plants.php?result=" . ($error->getMessage() === "NOT_FOUND" ? "invalid" : "delete_failed"));
        exit;
    }
}

if (!in_array($action, ["add", "update"], true)) {
    $conn->close();
    header("Location: admin_plants.php?result=invalid");
    exit;
}

$plantId = filter_input(INPUT_POST, "plant_id", FILTER_VALIDATE_INT);
$categoryId = filter_input(INPUT_POST, "plant_category_id", FILTER_VALIDATE_INT);
$plantName = trim($_POST["plant_name"] ?? "");
$price = filter_input(INPUT_POST, "price", FILTER_VALIDATE_FLOAT);
$quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
$plantSize = trim($_POST["plant_size"] ?? "");
$light = trim($_POST["light_requirement"] ?? "");
$potSize = trim($_POST["pot_size"] ?? "");
$potColor = trim($_POST["pot_color"] ?? "");
$petFriendly = isset($_POST["pet_friendly"]) ? 1 : 0;
$difficulty = trim($_POST["difficulty"] ?? "");
$airCleaner = isset($_POST["air_cleaner"]) ? 1 : 0;
$description = trim($_POST["description"] ?? "");
$status = $_POST["status"] ?? "ACTIVE";

if (
    $categoryId === false || $categoryId === null || $categoryId < 1 ||
    $plantName === "" || $description === "" ||
    $price === false || $price < 0 ||
    $quantity === false || $quantity < 0 ||
    ($action === "update" && ($plantId === false || $plantId === null || $plantId < 1))
) {
    $conn->close();
    header("Location: admin_plants.php?result=missing");
    exit;
}

$validSizes = ["", "Small", "Medium", "Large"];
$validLights = ["", "Low Light", "Indirect Light", "Full Sunlight", "Partial Shade"];
$validColors = ["", "Black", "Clay", "Gray", "Green", "White"];
$validDifficulties = ["", "Easy Care", "Medium Care", "Advanced Care"];

if (
    !in_array($plantSize, $validSizes, true) ||
    !in_array($light, $validLights, true) ||
    !in_array($potColor, $validColors, true) ||
    !in_array($difficulty, $validDifficulties, true) ||
    !in_array($status, ["ACTIVE", "INACTIVE"], true)
) {
    $conn->close();
    header("Location: admin_plants.php?result=invalid");
    exit;
}

// Empty strings are stored as SQL NULL in optional columns.
$plantSize = $plantSize === "" ? null : $plantSize;
$light = $light === "" ? null : $light;
$potSize = $potSize === "" ? null : $potSize;
$potColor = $potColor === "" ? null : $potColor;
$difficulty = $difficulty === "" ? null : $difficulty;

$uploadedFiles = [];

function uploadPlantImage($fieldName, &$uploadedFiles)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file["error"] !== UPLOAD_ERR_OK || $file["size"] > 5 * 1024 * 1024) {
        throw new RuntimeException("IMAGE");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file["tmp_name"]);
    $extensions = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($extensions[$mimeType])) {
        throw new RuntimeException("IMAGE");
    }

    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . "Images" . DIRECTORY_SEPARATOR . "Plants" . DIRECTORY_SEPARATOR . "uploads";
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true)) {
        throw new RuntimeException("IMAGE");
    }

    $fileName = "plant_" . bin2hex(random_bytes(10)) . "." . $extensions[$mimeType];
    $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $absolutePath)) {
        throw new RuntimeException("IMAGE");
    }

    $uploadedFiles[] = $absolutePath;
    return "Images/Plants/uploads/" . $fileName;
}

try {
    $images = [];
    for ($imageNumber = 1; $imageNumber <= 6; $imageNumber++) {
        $fieldName = $imageNumber === 1 ? "main_image" : "image_" . $imageNumber;
        $images[$imageNumber] = uploadPlantImage($fieldName, $uploadedFiles);
    }
    $mainImage = $images[1];

    $conn->begin_transaction();

    if ($action === "add") {
        $statement = $conn->prepare("
            INSERT INTO plants (
                plant_category_id, plant_name, price, quantity,
                plant_size, light_requirement, pot_size, pot_color,
                pet_friendly, difficulty, air_cleaner, description, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $statement->bind_param(
            "isdissssisiss",
            $categoryId, $plantName, $price, $quantity,
            $plantSize, $light, $potSize, $potColor,
            $petFriendly, $difficulty, $airCleaner, $description, $status
        );
        $statement->execute();
        $plantId = $conn->insert_id;
        $statement->close();
    } else {
        $statement = $conn->prepare("
            UPDATE plants SET
                plant_category_id = ?, plant_name = ?, price = ?, quantity = ?,
                plant_size = ?, light_requirement = ?, pot_size = ?, pot_color = ?,
                pet_friendly = ?, difficulty = ?, air_cleaner = ?,
                description = ?, status = ?
            WHERE plant_id = ?
        ");
        $statement->bind_param(
            "isdissssisissi",
            $categoryId, $plantName, $price, $quantity,
            $plantSize, $light, $potSize, $potColor,
            $petFriendly, $difficulty, $airCleaner,
            $description, $status, $plantId
        );
        $statement->execute();
        $statement->close();
    }

    if ($mainImage !== null) {
        $clearMain = $conn->prepare("UPDATE plant_images SET is_main = 0 WHERE plant_id = ?");
        $clearMain->bind_param("i", $plantId);
        $clearMain->execute();
        $clearMain->close();
    }

    $imageStatement = $conn->prepare("
        INSERT INTO plant_images (plant_id, image_path, is_main, sort_order)
        VALUES (?, ?, ?, ?)
    ");

    $images = [
        [$mainImage, 1, 1],
        [$image2, 0, 2],
        [$image3, 0, 3]
    ];

    foreach ($images as $image) {
        if ($image[0] === null) {
            continue;
        }
        $imagePath = $image[0];
        $isMain = $image[1];
        $sortOrder = $image[2];
        $imageStatement->bind_param("isii", $plantId, $imagePath, $isMain, $sortOrder);
        $imageStatement->execute();
    }
    $imageStatement->close();

    $conn->commit();
    $conn->close();
    $_SESSION["plant_admin_token"] = bin2hex(random_bytes(32));
    header("Location: admin_plants.php?result=" . ($action === "add" ? "added" : "updated"));
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    $conn->close();

    foreach ($uploadedFiles as $uploadedFile) {
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }

    error_log("EcoSprout plant management error: " . $error->getMessage());
    $result = $error->getMessage() === "IMAGE" ? "image" : "failed";
    header("Location: admin_plants.php?result=" . $result);
    exit;
}
