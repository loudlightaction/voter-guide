<?php

  require __DIR__ . '/vendor/autoload.php';
  require __DIR__ . '/helper-lib.php';

  init_env();
  force_ssl_in_production();
  force_hostname(); // checks FORCE_HOSTNAME env var

  $PROFILE = null; # if we have appropriate params, we'll define it.

  if (array_key_exists('address', $_GET) && strlen($_GET['address']) && !array_key_exists('cd', $_GET)) {
    // perform Google Civic API lookup and redirect with ?sd=X&hd=Y
    $address = $_GET['address'];
    $zipcode = $_GET['zipcode'];
    if (isset($_ENV['DISTRICTS_API'])) {
      $voter_info = get_voter_info_via_geo($_GET['lat'], $_GET['lng']);
    } else {
      $voter_info = get_voter_info($address, $zipcode);
    }

    if ($voter_info) {
      // if Google did not have state leg info, try again with openstates
      if (!isset($voter_info['sd'])) {
        get_openstates_via_geo($voter_info, $_GET['lat'], $_GET['lng']);
      }

      if ($zipcode && strpos($address, $zipcode)) {
        $voter_info['a'] = base64_encode($address);
      } else {
        $voter_info['a'] = base64_encode("$address $zipcode");
      }

      $redirect_url = sprintf("%s?%s", get_this_url(), http_build_query($voter_info));

      header('Location: ' . $redirect_url);
      //error_log("Redirecting to $redirect_url");
      print "Redirecting to $redirect_url";
      exit(0);
    }
  }

  if (array_key_exists('cd', $_GET)) {
    // lookup Airtable details, caching if found
    $PROFILE = get_candidate_info(get_sd(), get_hd(), get_cd(), get_county(), get_sbe());
    $QUESTIONS = (array)get_candidate_questions();
    asort($QUESTIONS);

    if (!$PROFILE) {
      $ERROR = sprintf(
        "We're sorry, we could not locate candidates for Senate District %s and House District %s. Please try again later.",
        get_sd(), get_hd()
      );
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
    <script src="https://kit.fontawesome.com/858b4d9129.js" crossorigin="anonymous" SameSite="none Secure"></script>

    <?php if (isset($_ENV['GOOGLE_TAG_MANAGER'])) { ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $_ENV['GOOGLE_TAG_MANAGER'] ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', '<?= $_ENV['GOOGLE_TAG_MANAGER'] ?>');
    </script>
    <?php } ?>

    <title>Kansas <?= $_ENV['ELECTION_YEAR'] ?> Voter Guide</title>

    <style>
      .nav-bg {
        background-color: #111!important;
        /* background-color: #1f3c67!important; */
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
        color: #0015BC;
        border-color: #0015BC!important;
        border-width: 2px;
      }
      .Republican {
        color: #E9141D;
        border-color: #E9141D!important;
        border-width: 2px;
      }
      .Libertarian {
        color: #E5C601;
        border-color: #E5C601!important;
        border-width: 2px;
      }
      .question {
        font-weight: bold;
      }
      .tooltip-inner {
        text-align: left;
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
    <div class="container-fluid border border-light bg-light pt-2">
      <div class="row">
        <div class="col">
          <a class="h4" href="<?= get_this_url() ?>">Lookup by Address</a>
        </div>
      </div>
      <div class="row p-2">
        <div class="col"><?= htmlspecialchars(base64_decode($_GET['a'])) ?></div>
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
          <div class="h2">President</div>
          <?php $candidates = get_president_candidates($PROFILE); include __DIR__ . '/candidates-layout.php'; ?>

          <div class="h2 mt-4">U.S. Senate</div>
          <?php $candidates = get_us_senate_candidates($PROFILE); include __DIR__ . '/candidates-layout.php'; ?>

          <div class="h2 mt-4">U.S. Congressional District #<?= get_cd() ?></div>
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
          <?php $candidates = get_senate_candidates($PROFILE); include __DIR__ . '/candidates-with-questions-layout.php'; ?>

          <div class="h2 mt-4">State House #<?= get_hd() ?></div>
          <?php $candidates = get_house_candidates($PROFILE); include __DIR__ . '/candidates-with-questions-layout.php'; ?>
        </div>
      </div>
    </div><!-- card -->

  <?php
    $sbe_candidates = get_matching_candidates($PROFILE, 'State Board of Education');
    $da_candidates = get_matching_candidates($PROFILE, 'District Attorney');

    if (count($sbe_candidates) || count($da_candidates)) {
  ?>

    <div class="card">
      <div class="card-header nav-bg text-light" id="local-races-header">
        <h3 class="mb-0">
          <i class="fas fa-university"></i> Local/County Races
          <button class="btn btn-link float-right" data-toggle="collapse" data-target="#local-races" aria-expanded="true" aria-controls="local-races"></button>
        </h3>
      </div>

      <div id="local-races" class="collapse show" aria-labelledby="local-races-header" data-parent="#voter-profile">
        <div class="card-body">
         <?php if (count($da_candidates)) { ?>
          <div class="h2">District Attorney</div>
          <?php $candidates = $da_candidates; include __DIR__ . '/candidates-layout.php'; ?>
         <?php } ?>

         <?php if (count($sbe_candidates)) { ?>
          <div class="h2">State Board of Education</div>
          <?php $candidates = $sbe_candidates; include __DIR__ . '/candidates-layout.php'; ?>
         <?php } ?>
        </div>
      </div>
    </div><!-- card -->

  <?php } ?>

    <?php if ($_ENV['DEBUG']) { ?>
    <small>
    <pre>
    <?php print_r($PROFILE) ?>
    <?php print_r($QUESTIONS) ?>
    </pre>
    </small>
    <?php } ?>

  </div><!-- #voter-profile -->

  <div class="container text-center mt-2 mb-2">
    <a class="btn btn-outline-dark" href="<?= get_this_url() ?>">Lookup another address</a>
  </div>

  <?php } else { ?>
   <div class="container mt-2 mb-4">
    <?php if (isset($ERROR)) { ?>
    <div class="alert alert-danger" role="alert"><?= $ERROR ?></div>
    <?php } ?>
    <div class="address-form">
      <form action="<?php print get_this_url() ?>" method="GET">
        <div class="form-group">
          <label for="address">Street Address</label>
          <input type="text" name="address" class="form-control" id="address" aria-describedby="addressHelp" placeholder="123 Main Street">
          <input type="hidden" id="lat" name="lat" value="">
          <input type="hidden" id="lng" name="lng" value="">
          <small id="addressHelp" class="form-text text-muted">The address where you are registered to vote</small>
        </div>
        <div class="form-group">
          <label for="zipcode">ZIP Code</label>
          <input type="text" name="zipcode" class="form-control" id="zipcode" placeholder="12345">
        </div>
        <button type="submit" class="btn btn-outline-dark">Submit</button>
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
          <div><a class="text-light" href="https://loudlightaction.org/methodology">Methodology / Suggestions</a></div>
          <div class="font-italic"><a class="text-light" href="https://loudlightaction.org/">A project of Loud Light Civic Action</a></div>
        </div>
      </div>
  </footer>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <script type="text/javascript">
      $(function () {
        $('[data-toggle="tooltip"]').tooltip()
      });
    </script>

  <?php if (!$PROFILE && array_key_exists('GOOGLE_PLACES_API_KEY', $_ENV)) { ?>
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
        autocomplete.setFields(["address_component", "geometry"]);
        // When the user selects an address from the drop-down, populate the
        // address fields in the form.
        autocomplete.addListener("place_changed", fillInAddress);
      }

      function fillInAddress() {
        // Get the place details from the autocomplete object.
        const place = autocomplete.getPlace();

        document.getElementById('lat').value = place.geometry.location.lat();
        document.getElementById('lng').value = place.geometry.location.lng();

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

  <?php if ($_ENV['DEBUG']) { print "<small><pre>" . var_export($_SERVER, true) . "</pre></small>"; } ?>
  </body>
</html>
