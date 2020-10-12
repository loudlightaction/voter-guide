<?php

  require __DIR__ . '/vendor/autoload.php';

  if (file_exists(__DIR__ . '.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
  }

  // require ssl when in production
  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "http") {
    if(!headers_sent()) {
      header("Status: 301 Moved Permanently");
      header(sprintf(
          'Location: https://%s%s',
          $_SERVER['HTTP_HOST'],
          $_SERVER['REQUEST_URI']
      ));
      exit();
    }
  }

  use \TANIOS\Airtable\Airtable;

  function get_scheme() {
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
      $scheme .= 's';
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "http") {
      $scheme = 'https';
    }
    return $scheme;
  }
  
  function get_this_url($scheme=null) {
    if (!$scheme) { $scheme = get_scheme(); }
    return sprintf("%s://%s%s",
      $scheme,
      $_SERVER['SERVER_NAME'],
      $_SERVER['PHP_SELF']);
  }
  
  function get_voter_info($address, $zipcode) {
    $api_url = sprintf(
      'https://www.googleapis.com/civicinfo/v2/representatives?key=%s&address=%s&electionId=%s',
      $_ENV['GOOGLE_API_KEY'],
      urlencode("$address $zipcode"),
      $_ENV['GOOGLE_ELECTION_ID']
    );
  
  //  error_log("api url: $api_url");
  
    $response = Requests::get($api_url, array('Content-Type' => 'application/json'));
  
  //  error_log(var_export($response, true));
  //  error_log(var_export($response->body, true));
  
    $voter_info = array();
    $resp = json_decode($response->body, true);
  
    foreach(array_keys($resp['divisions']) as $div) {
      if (preg_match("/ocd-division\/country:us\/state:ks\/sldu:(\w+)/", $div, $matches)) {
        $voter_info['sd'] = $matches[1];
      } elseif (preg_match("/ocd-division\/country:us\/state:ks\/sldl:(\w+)/", $div, $matches)) {
        $voter_info['hd'] = $matches[1];
      }
    }
  
    return $voter_info;
  }
  
  function get_candidate_info($senate_district, $house_district) {
    $airtable = new Airtable(array(
      'api_key' => $_ENV['AIRTABLE_API_KEY'],
      'base'    => $_ENV['AIRTABLE_BASE_ID']
    ));

    $params = array(
      'filterByFormula' => "OR( Race = 'State House District $house_district', Race = 'State Senate District $senate_district' )"
    );

    //error_log(var_export($params, true));
  
    $request = $airtable->getContent('Table 1', $params);
  
    $records = $request->getResponse()['records'];
  
    return $records;
  }

?>

<?php

  $PROFILE = null; # if we have appropriate params, we'll define it.

  if (array_key_exists('address', $_GET)) { 
    // perform Google Civic API lookup and redirect with ?sd=X&hd=Y
    $voter_info = get_voter_info($_GET['address'], $_GET['zipcode']);

    $redirect_url = sprintf("%s?%s", get_this_url(), http_build_query($voter_info));

    header('Location: ' . $redirect_url);
    //error_log("Redirecting to $redirect_url");
    print "Redirecting to $redirect_url";
    exit(0);

  }

  if (array_key_exists('sd', $_GET) && array_key_exists('hd', $_GET)) {
    // lookup Airtable details, caching if found
    $sd = $_GET['sd'];
    $hd = $_GET['hd'];

    $PROFILE = get_candidate_info($sd, $hd);

    if (!$PROFILE) {
      $PROFILE = array('error' => "We're sorry, we could not locate candidates for Senate District $sd and House District $hd");
    }
  }
?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>Kansas Voter Guide</title>
  </head>
  <body>
   <div class="container">

  <?php if ($PROFILE) { ?>
    Your candidate info here.

    <pre>
    <?php print_r($PROFILE) ?>
    </pre>

    <a class="btn btn-primary" href="<?php print get_this_url() ?>">Search again</a>

  <?php } else { ?>
    <h1>Voter Guide</h1>
    <div class="address-form">
      <form action="<?php print get_this_url() ?>" method="GET">
        <div class="form-group">
          <label for="address">Street Address</label>
          <input type="text" name="address" class="form-control" id="address" aria-describedby="addressHelp" placeholder="123 Main Street">
          <small id="addressHelp" class="form-text text-muted">The address where you are registered to vote</small>
        </div>
        <div class="form-group">
          <label for="zipcode">ZIP Code</label>
          <input type="text" name="zipcode" class="form-control" id="zipcode" placeholder="12345">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
      </form>
    </div>
  <?php } ?>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
   </div><!-- container -->
  </body>
</html>
