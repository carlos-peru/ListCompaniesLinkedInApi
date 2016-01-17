<?php
session_start();
define('API_KEY','75n066bnuj4hsq');
define('API_SECRET','lNzcx9pfUFJ5SLQS');
define('REDIRECT_URI','http://www.lenguacallejera.com/companies.php');
define('SCOPE','r_basicprofile');


if (isset($_GET['error'])) {
    // LinkedIn returned an error
    print $_GET['error'] . ': ' . $_GET['error_description'];
    exit;
} elseif (isset($_GET['code'])) {
     $state = $_SESSION['state'];

    if ($state==$_GET['state']) {
        // Get token so you can make API calls
        getAccessToken();
        requestAndDisplayCompany($_SESSION['company']);
    } else {
        // CSRF attack? Or did you mix up your states?
        //echo "CSRF attack";
        ?>
        <html>
        <head>
          <title>Callback</title>
        </head>
        <body>
        <h1>What the fuck YO with the states!</h1>
        </body>
        </html>
        <?php
    }
} elseif (!(empty($_SESSION['expires_at']) || time() > $_SESSION['expires_at'] || empty($_SESSION['access_token']))) {
  requestAndDisplayCompany($_GET['company']);
} else {
  restart();
}

function restart() {
  $_SESSION = array();
  $_SESSION['company'] = $_GET['company'];
  getAuthorizationCode();
}

function requestAndDisplayCompany($company) {
  // echo $response;
  $companies = fetch('GET', "/v1/company-search?keywords=".urlencode($company)."&country-code=us&facet=location,us:0&hq-only=true&");
  if ($companies === FALSE) {
    restart();
  } else {
    ?>
    <html>
    <head>
      <title>Companies</title>
    </head>
    <body>
      <h1>Expires at: <?php
      date_default_timezone_set('America/Los_Angeles');
      echo date('l jS \of F Y h:i:s A', $_SESSION['expires_at']);
      ?></h1>
      <br><br>
      <h1><?=$companies?></h1>
    </body>
    </html>
    <?php
  }
}

function getAuthorizationCode() {
  $params = array('response_type' => 'code',
          'client_id' => API_KEY,
          'scope' => SCOPE,
          'state' => 'testsite',
          'redirect_uri' => REDIRECT_URI
    );
  $url = 'https://www.linkedin.com/uas/oauth2/authorization?'.http_build_query($params);
  $_SESSION['state'] = $params['state'];
  header("Location: $url");
  exit;
}

function getAccessToken() {
  // Get token so you can make API calls
  $params = array('grant_type' => 'authorization_code',
                  'client_id' => API_KEY,
                  'client_secret' => API_SECRET,
                  'code' => $_GET['code'],
                  'redirect_uri' => REDIRECT_URI,
            );
  // Access Token request
$url = 'https://www.linkedin.com/uas/oauth2/accessToken?'.http_build_query($params);

  // Tell streams to make a POST request
  $context = stream_context_create(
                  array('http' =>
                      array('method' => 'POST',
                      )
                  )
              );

  // Retrieve access token information
 $response = file_get_contents($url, true, $context);

  // Native PHP object, please
 $token = json_decode($response);
 $_SESSION['access_token'] = $token->access_token;
 $_SESSION['expires_in']   = $token->expires_in;
 $_SESSION['expires_at']   = time() + $_SESSION['expires_in'];
}

function fetch($method, $resource, $body = '') {
    $access_token = $_SESSION['access_token'];
    $params = array('oauth2_access_token' => $access_token,
                    'format' => 'json'
              );

    // Need to use HTTPS
    $url = 'https://api.linkedin.com' . $resource . http_build_query($params);
    // Tell streams to make a (GET, POST, PUT, or DELETE) request
    $context = stream_context_create(
                    array('http' =>
                        array('method' => $method,
                        )
                    )
                );
    $response = file_get_contents($url, false, $context);
    return $response;
}
?>
