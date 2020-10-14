<!-- candidate profiles -->
<?php foreach($candidates as $candidate) { ?>
  <div class="row candidate border-bottom border-dark mt-3 pb-3">
    <div class="col-3 headshot">
      <img class="rounded img-fluid" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'large'}->{'url'} ?>" />
    </div>
    <div class="col name">
      <div class="h3 name"><?= $candidate->{'fields'}->{'Name'} ?></div>
      <div class="mt-1 text-secondary party"><?= $candidate->{'fields'}->{'Party'} ?></div>
      <?php if ($candidate->{'fields'}->{'Website'}) { ?>
      <div class="mt-1 website"><a href="<?= get_url($candidate->{'fields'}->{'Website'}) ?>"><i class="fas fa-external-link"></i>website</a></div>
      <?php } ?>
      <div class="mt-1 endorsements">
        <div class="h6">Endorsements</div>
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
</div>
<?php } ?>
