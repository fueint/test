<?php 
function otpConnection($url,$jsonRequest) {
    $customerKey = "TEST314168"; //Get from mini orange account
    $apiKey = "qoiiaEQJLtNlcwiJTJCq7sgWOyngBo6z"; //Get from mini orange account
    $currentTimeInMillis = round(microtime(true) * 1000);
    $stringToHash = $customerKey . number_format ( $currentTimeInMillis, 0, '', '' ) .
    $apiKey;
    $hashValue = hash("sha512", $stringToHash);
    $jsonRequestString = json_encode($jsonRequest);
    $customerKeyHeader = "Customer-Key: " . $customerKey;
    $timestampHeader = "Timestamp: " . number_format ( $currentTimeInMillis, 0, '', ''
    );
    $authorizationHeader = "Authorization: " . $hashValue;
    /* Initialize curl */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json",
    $customerKeyHeader,$timestampHeader, $authorizationHeader));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequestString);
    curl_setopt($ch, CURLOPT_POST, 1);
    /* Calling the rest API */
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo curl_error($ch);
    } else {
        curl_close($ch);
    }
    /* If a valid response is received, get the JSON response */
    $response = (array)json_decode($result);
    $status = $response['status'];
    if($status == 'SUCCESS') {
        return $response;
    } else {
        return "FAILED: " . $response['message'];
    }
}

/* Generate and send a OTP message to mobile phone */
$generateUrl = "https://login.xecurify.com/moas/api/auth/challenge";
$customerKey = "314168";
$mobile_number = "+917094420181"; // Enter a valid mobile number (number should have country code ex:+91)
$jsonRequest = array(
    "customerKey" => $customerKey,
    "phone" => $mobile_number,
    "authType" => "SMS",
    "transactionName" => "BUGGERSPOT-OTP-VERIFICATION",
);
$send_otp = otpConnection($generateUrl,$jsonRequest);

/* Check OTP status */
if(is_array($send_otp) && $send_otp['status'] == "SUCCESS") {
    // You have to pass the data to validation step so you can pass the data with help of form submission
    ?>
    <form>
      <label>Enter Your OTP</label>
      <!-- Enter Your OTP here -->
      <input type="text" name="otp">
      <!-- Pass txID in hidden field -->
      <input type="hidden" name="txID" value="<?= $send_otp['txId']; ?>">
      <input type="submit" name="submit">
    </form>
<?php 

/* Validate OTP */
if(isset($_POST['submit'])) {
  $validateUrl = "https://login.xecurify.com/moas/api/auth/validate";
  $jsonRequest = array('txId' => $_POST['txId'],
  'token' => $_POST['otp']);
  $validate_otp = otpConnection($validateUrl,$jsonRequest);
  if(is_array($validate_otp) && $validate_otp['status'] == "SUCCESS") {
    echo "Your OTP verified successfully";
  }
}
