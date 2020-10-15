<?php

  require __DIR__ . '/vendor/autoload.php';
  require __DIR__ . '/helper-lib.php';

  init_env();
  force_ssl_in_production();

  $PROFILE = null; # if we have appropriate params, we'll define it.

  if (array_key_exists('address', $_GET) && !array_key_exists('hd', $_GET)) { 
    // perform Google Civic API lookup and redirect with ?sd=X&hd=Y
    $address = $_GET['address'];
    $zipcode = $_GET['zipcode'];
    $voter_info = get_voter_info($address, $zipcode);

    if ($zipcode && strpos($address, $zipcode)) {
      $voter_info['address'] = $address;
    } else {
      $voter_info['address'] = "$address $zipcode";
    }

    $redirect_url = sprintf("%s?%s", get_this_url(), http_build_query($voter_info));

    header('Location: ' . $redirect_url);
    error_log("Redirecting to $redirect_url");
    print "Redirecting to $redirect_url";
    exit(0);

  }

  if (array_key_exists('sd', $_GET) && array_key_exists('hd', $_GET)) {
    // lookup Airtable details, caching if found
    $PROFILE = get_candidate_info(get_sd(), get_hd(), get_cd());
    $QUESTIONS = get_candidate_questions();

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

    <!-- icons -->
    <script src="https://kit.fontawesome.com/858b4d9129.js" crossorigin="anonymous"></script>

    <title>Kansas <?= $_ENV['ELECTION_YEAR'] || '2020' ?> Voter Guide</title>

    <style>
      .nav-bg {
        background-color: #1f3c67!important;
      }
      .border-dark {
        border-color: #1f3c67!important;
      }
      .voter-profile .card-header button:after {
        font-family: 'FontAwesome';  
        content: "\f102";
        color: #fff;
      }
      .voter-profile .card-header button.collapsed:after {
        content: "\f103";
        color: #fff;
      }
      .Democratic {
        color: blue;
      }
      .Republican {
        color: red;
      }
    </style>
  </head>
  <body>
    <div class="container-fluid nav-bg">
      <div class="text-light p-2">
        <div class="h1 text-center">Kansas <?= $_ENV['ELECTION_YEAR'] ?><br/>Voter Guide</div>
      </div>
    </div>
    <?php if ($PROFILE) { ?>
    <div class="container-fluid bg-info pt-2">
      <div class="row">
        <div class="col">
          <a class="h4 text-light" href="<?= get_this_url() ?>">Lookup by Address</a>
        </div>
      </div>
      <div class="row p-2 text-light">
        <div class="col"><?= htmlspecialchars($_GET['address']) ?></div>
      </div>
    </div>
    <?php } ?>

  <?php if ($PROFILE) { ?>
  <div class="voter-profile">
    <!-- toggle-able section -->
    <div class="card">
      <div class="card-header nav-bg text-light" id="fed-races-header">
        <h3 class="mb-0">
          <i class="fas fa-university"></i> Federal Races
          <button class="btn btn-link float-right collapsed" data-toggle="collapse" data-target="#fed-races" aria-expanded="true" aria-controls="fed-races"></button>
        </h3>
      </div>

      <div id="fed-races" class="collapse" aria-labelledby="fed-races-header" data-parent="#voter-profile">
        <div class="card-body">
          <div class="h2">Congressional District #<?= get_cd() ?></div>
          <?php $candidates = get_congressional_candidates($PROFILE); include __DIR__ . '/candidates-layout.php'; ?>
        </div>
      </div>
    </div><!-- card -->

    <div class="card">
      <div class="card-header nav-bg text-light" id="state-races-header">
        <h3 class="mb-0">
          <i class="fas fa-university"></i> State Races
          <button class="btn btn-link float-right" data-toggle="collapse" data-target="#state-races" aria-expanded="true" aria-controls="state-races"></button>
        </h3>
      </div>

      <div id="state-races" class="collapse show" aria-labelledby="state-races-header" data-parent="#voter-profile">
        <div class="card-body">
          <div class="h2">State Senate #<?= get_sd() ?></div>
          <?php $candidates = get_senate_candidates($PROFILE); include __DIR__ . '/candidates-layout.php'; ?>

          <div class="h2 mt-4">State House #<?= get_hd() ?></div>
          <?php $candidates = get_house_candidates($PROFILE); include __DIR__ . '/candidates-layout.php'; ?>
        </div>
      </div>
    </div><!-- card -->

<!--
    <small>
    <pre>
    <?php print_r($PROFILE) ?>
    <?php print_r($QUESTIONS) ?>
    </pre>
    </small>
-->
  </div><!-- #voter-profile -->

  <div class="container text-center mt-2 mb-2">
    <a class="btn btn-primary" href="<?= get_this_url() ?>">Lookup another address</a>
  </div>

  <?php } else { ?>
   <div class="container mt-2">
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
   </div>
  <?php } ?>

  <footer>
    <div class="container-fluid mt-2">
      <div class="row nav-bg">
        <div class="col text-center p-2">
          <div><a class="text-light" href="https://www.ksvotes.org/r/ksvoterguide">Voting Location</a></div>
          <div><a class="text-light" href="https://www.ksvotes.org/r/ksvoterguide">Check Voter Registration</a></div>
          <div><a class="text-light" href="#">Issue Stances Details</a></div>
          <div class="font-italic"><a class="text-light" href="https://loudlightaction.org/">A project of Loud Light Civic Action</a></div>
        </div>
      </div>
  </footer>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

  <?php if (!$PROFILE && array_key_exists($_ENV, 'GOOGLE_PLACES_API_KEY')) { ?>
    <!-- address autocomplete -->
    <script type="text/javascript">
      // This sample uses the Autocomplete widget to help the user select a
      // place, then it retrieves the address components associated with that
      // place, and then it populates the form fields with those details.
      // This sample requires the Places library. Include the libraries=places
      // parameter when you first load the API. For example:
      // <script
      // src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">
      let placeSearch;
      let autocomplete;

      function initAutocomplete() {
        // Create the autocomplete object, restricting the search predictions to
        // geographical location types.
        autocomplete = new google.maps.places.Autocomplete(
          document.getElementById("address"),
          { types: ["geocode"] }
        );
        // Avoid paying for data that you don't need by restricting the set of
        // place fields that are returned to just the address components.
        autocomplete.setFields(["address_component"]);
        // When the user selects an address from the drop-down, populate the
        // address fields in the form.
        autocomplete.addListener("place_changed", fillInAddress);
      }

      function fillInAddress() {
        // Get the place details from the autocomplete object.
        const place = autocomplete.getPlace();

        // Get each component of the address from the place details,
        // and then fill-in the corresponding field on the form.
        for (const component of place.address_components) {
          const addressType = component.types[0];

          if (addressType == 'postal_code') {
            const val = component.short_name;
            document.getElementById('zipcode').value = val;
          }
        }
      }

      // Bias the autocomplete object to the user's geographical location,
      // as supplied by the browser's 'navigator.geolocation' object.
      function geolocate() {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition((position) => {
            const geolocation = {
              lat: position.coords.latitude,
              lng: position.coords.longitude,
            };
            const circle = new google.maps.Circle({
              center: geolocation,
              radius: position.coords.accuracy,
            });
            autocomplete.setBounds(circle.getBounds());
          });
        }
      }
    </script>
    <script defer src="https://maps.googleapis.com/maps/api/js?key=<?= $_ENV['GOOGLE_PLACES_API_KEY'] ?>&libraries=places&callback=initAutocomplete"></script>
  <?php } ?>
  </body>
</html>
