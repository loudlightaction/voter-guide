<?php

  require __DIR__ . '/vendor/autoload.php';
  require __DIR__ . '/helper-lib.php';

  init_env();
  force_ssl_in_production();

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
    $PROFILE = get_candidate_info(get_sd(), get_hd());
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

    <title>Kansas Voter Guide</title>

    <style>
      .nav-bg {
        background-color: #1f3c67!important;
      }
      .border-dark {
        border-color: #1f3c67!important;
      }
    </style>
  </head>
  <body>
    <div class="container-fluid p-0 mb-2">
      <ul class="nav nav-bg">
        <?php if ($PROFILE) { ?>
        <li class="nav-item">
          <a class="nav-link text-light" href="<?= get_this_url() ?>">
            <i class="fas fa-university"></i> State Races
          </a>
        </li>
        <?php } ?>
        <li class="nav-item"><!-- TODO drop? -->
          <a class="nav-link text-light" href="<?= get_this_url() ?>">Lookup by Address</a>
        </li>
      </ul>
    </div>
    <div class="container">

  <?php if ($PROFILE) {
    $senate_candidates = get_senate_candidates($PROFILE);
  ?>

    <div class="h2">
      State Senate #<?= get_sd() ?>
    </div>

    <!-- candidate profiles -->
    <?php foreach($senate_candidates as $candidate) { ?>
      <div class="row candidate border-bottom border-dark mt-3 pb-3">
        <div class="col-3 headshot">
          <img class="rounded img-fluid" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'large'}->{'url'} ?>" />
        </div>
        <div class="col name">
          <div class="h3 name"><?= $candidate->{'fields'}->{'Name'} ?></div>
          <div class="mt-2 text-secondary party"><?= $candidate->{'fields'}->{'Party'} ?></div>
          <div class="mt-2 endorsements">
            <div class="h5">Endorsements</div>
            <?php foreach($candidate->{'fields'}->{'Endorsed By'} as $endorsement) { ?>
              <div class="endorsement"><?= $endorsement ?></div>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } ?>

    <!-- candidate issues -->
    <?php foreach($QUESTIONS as $field => $question) {
      if (strlen($question) == 0) { continue; }
    ?>
     <div class="container issue mt-3 border-bottom border-dark pb-3">
      <div class="row question">
        <div class="col">
          <?= $question ?>
        </div>
      </div>
      <?php foreach($senate_candidates as $candidate) { ?>
      <div class="row candidate">
        <div class="col-2">
          <img class="img-thumbnail" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'small'}->{'url'} ?>" />
        </div>
        <div class="col">
          <?= $candidate->{'fields'}->{'Name'} ?>
        </div>
        <div class="col">
          <?= $candidate->{'fields'}->{$field} ?>
        </div>
      </div>
      <?php } ?>
     </div>
    <?php } ?>

<!--
    <small>
    <pre>
    <?php print_r($PROFILE) ?>
    <?php print_r($QUESTIONS) ?>
    </pre>
    </small>
-->

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
