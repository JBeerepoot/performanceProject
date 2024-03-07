<?php
require_once 'jesperbeerepoot/config.php';
// Now you can use constants DB_USER, DB_PASS, CLIENT_ID, and CLIENT_SECRET


// Use the constants from config.php
$dbHost = 'localhost:3366';
$dbUsername = DB_USER; // From config.php
$dbPassword = DB_PASS; // From config.php
$dbName = 'msrzipssml_oAuthToken'; // Define your database name if not in config.php

// OAuth parameters from config.php
$clientId = CLIENT_ID;
$clientSecret = CLIENT_SECRET;

// Connect to the database
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get the authorization code from the AJAX request
$authCode = isset($_POST['code']) ? $_POST['code'] : null;

if ($authCode) {
    // Exchange the authorization code for access token and refresh token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
        'code' => $authCode
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if (isset($responseData['access_token']) && isset($responseData['refresh_token'])) {
        // Store the tokens in the database
        $accessToken = $responseData['access_token'];
        $refreshToken = $responseData['refresh_token'];
        $expiresIn = $responseData['expires_in']; // You might need to calculate the actual expiration time based on current time + expires_in

        $stmt = $conn->prepare("INSERT INTO oauth_tokens (service, access_token, refresh_token, expires_in) VALUES (?, ?, ?, ?)");
        $service = 'wahoo'; // Or 'garmin', depending on the service
        $stmt->bind_param("sssi", $service, $accessToken, $refreshToken, $expiresIn);
        $stmt->execute();
        if ($stmt->error) {
            echo "Error storing tokens: " . $stmt->error;
        } else {
            echo "Tokens stored successfully";
        }
        $stmt->close();
    } else {
        // Handle errors, e.g., log them or return an error response
        echo "Error exchanging code for tokens";
    }
} else {
    echo "No authorization code provided";
}

$conn->close();
?>
