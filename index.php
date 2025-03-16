<?php
session_start();
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
// DB connection using id of the user
$id = $_SESSION['id'];
try {
    $connection = new PDO('mysql:host=localhost;dbname=general', 'root', '');
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

$statement = $connection->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$statement->execute(array(':id' => $id));
$result = $statement->fetch();

// If user exists
if ($result) {
    require_once 'GoogleAuthenticator.php';
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = $ga->createSecret();

    $cypherMethod = 'AES-256-GCM';
    $key = random_bytes(32);
    $iv = random_bytes(12);

    $encryptedSecret = openssl_encrypt($secret, $cypherMethod, $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    if ($encryptedSecret === false) {
        die('Error al encriptar el secreto: ' . openssl_error_string());
    }

    $finalEncryptedString = base64_encode($encryptedSecret);
    $hexKey = bin2hex($key);
    $hexIv = bin2hex($iv);
    $hexTag = bin2hex($tag);

    $userEmail = $result['email']; // User email
    $qrCodeUrl = $ga->getQRCodeGoogleUrl("example.com: $userEmail", $secret); // QR code URL
    echo "<img src='$qrCodeUrl' alt='TOTP QR image'>"; // Show QR code
    $_SESSION['tempTotpSecret'] = $secret; // Save secret in session (Potential security risk: XSS , MitM)

    if ($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_SESSION['tempTotpSecret'])){
            $tempSecret = $_SESSION['tempTotpSecret'];
        }
        if($checkResult = $ga->verifyCode($tempSecret, $_POST['code'], 2)){
            $statement = $connection->prepare('INSERT INTO usersTotp (id, totpSecret, totpKey, totpIv, totpTag) 
            VALUES (:id, :totpSecret, :totpKey, :totpIv, :totpTag) 
            ON DUPLICATE KEY UPDATE 
            totpSecret = :totpSecret, 
            totpKey = :totpKey, 
            totpIv = :totpIv, 
            totpTag = :totpTag');
    
            $statement->execute(array(
                ':id' => $id,
                ':totpSecret' => $finalEncryptedString,
                ':totpKey' => $hexKey,
                ':totpIv' => $hexIv,
                ':totpTag' => $hexTag
            ));
            unset($_SESSION['tempTotpSecret']);
            echo "Correct code.";
        }else{
            unset($_SESSION['tempTotpSecret']);
            echo "Incorrect code.";
        }
    }
} else {
    echo "User not found.";
}
?>
<form action="" method="post">
    <input type="text" id="code" name="code" placeholder="Introduce the code">
    <input type="submit" value="Submit">
</form>