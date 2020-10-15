<!-- candidate profiles -->
<?php foreach($candidates as $candidate) { ?>
  <div class="row candidate border-bottom border-dark mt-3 pb-3">
    <div class="col-3 headshot">
      <?php if ($candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'large'}->{'url'}) { ?>
      <img class="rounded img-fluid" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'large'}->{'url'} ?>" />
      <?php } else { ?>
      <i class="far fa-address-card fa-4x <?= $candidate->{'fields'}->{'Party'} ?>"></i>
      <?php } ?>
    </div>
    <div class="col name">
      <div class="h3 name"><?= $candidate->{'fields'}->{'Name'} ?></div>
      <div class="mt-1 text-secondary party"><?= $candidate->{'fields'}->{'Party'} ?></div>
      <?php if ($candidate->{'fields'}->{'Website'}) { ?>
      <div class="mt-1 website"><a href="<?= get_url($candidate->{'fields'}->{'Website'}) ?>"><i class="fas fa-external-link-alt"></i> website</a></div>
      <?php } ?>
      <?php if ($candidate->{'fields'}->{'Endorsed By'}) { ?>
      <div class="mt-3 endorsements">
        <div class="h6">Endorsements</div>
        <?php foreach($candidate->{'fields'}->{'Endorsed By'} as $endorsement) { ?>
          <div class="endorsement"><?= $endorsement ?></div>
        <?php } ?>
      </div>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<!-- candidate issues -->
<?php foreach($QUESTIONS as $field => $question) {
  if (strlen($question) == 0) { continue; }
  // TODO check if any answers exist for any candidate and skip otherwise
  $answers_exist = false;
  foreach($candidates as $c) {
    if (strlen($c->{'fields'}->{$field})) {
      $answers_exist = true;
      break;
    }
  }
  if (!$answers_exist) { continue; }
?>
<div class="row">
 <div class="container-fluid issue mt-3 border-bottom border-dark pb-3">
  <div class="row question mb-2">
    <div class="col">
      <?= $question ?>
    </div>
  </div>
  <?php foreach($candidates as $candidate) { ?>
  <div class="row candidate align-items-center">
    <div class="col-2">
      <?php if ($candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'small'}->{'url'}) { ?>
      <img class="img-thumbnail" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'small'}->{'url'} ?>" />
      <?php } else { ?>
      <i class="far fa-address-card fa-2x <?= $candidate->{'fields'}->{'Party'} ?>"></i>
      <?php } ?>
    </div>
    <div class="col">
      <?= $candidate->{'fields'}->{'last name'} ?>
    </div>
    <div class="col">
      <?= $candidate->{'fields'}->{$field} ?>
    </div>
  </div>
  <?php } ?>
 </div>
</div>
<?php } ?>
