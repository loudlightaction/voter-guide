<?php include 'candidates-layout.php'; ?>

<!-- candidate issues -->
<?php
foreach($QUESTIONS as $field => $q) {
  $question = $q->{'question'};
  if (strlen($question) == 0) { continue; }
  // TODO check if any answers exist for any candidate and skip otherwise
  $answers_exist = false;
  foreach($candidates as $c) {
    if (isset($c->{'fields'}->{$field}) && $c->{'fields'}->{$field}) {
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
      <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="<?= htmlspecialchars($q->{'explainer'}) ?>"></i>
    </div>
  </div>
  <?php foreach($candidates as $candidate) { ?>
  <div class="row mb-2 candidate align-items-center">
    <div class="col-2">
      <?php if (isset($candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'small'}->{'url'})) { ?>
      <img class="img-thumbnail <?= $candidate->{'fields'}->{'Party'} ?>" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'small'}->{'url'} ?>" />
      <?php } else { ?>
      <i class="far fa-address-card fa-2x <?= $candidate->{'fields'}->{'Party'} ?>"></i>
      <?php } ?>
    </div>
    <div class="col">
      <?= $candidate->{'fields'}->{'last name'} ?>
    </div>
    <div class="col">
     <?php if (is_string($candidate->{'fields'}->{$field})) { ?>
      <?= $candidate->{'fields'}->{$field} ?>
     <?php } else { ?>
      <?= $candidate->{'fields'}->{$field}[0] ?>
     <?php } ?>
    </div>
  </div>
  <?php } ?>
 </div>
</div>
<?php } ?>
