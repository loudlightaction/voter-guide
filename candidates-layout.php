<!-- candidate profiles -->
<?php foreach($candidates as $candidate) { ?>
  <div class="row candidate border-bottom border-dark mt-3 pb-3">
    <div class="col-3 headshot">
      <?php if (isset($candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'large'}->{'url'})) { ?>
      <img class="img-thumbnail <?= $candidate->{'fields'}->{'Party'} ?>" src="<?= $candidate->{'fields'}->{'Photo'}[0]->{'thumbnails'}->{'large'}->{'url'} ?>" />
      <?php } else { ?>
      <i class="far fa-address-card fa-4x <?= $candidate->{'fields'}->{'Party'} ?>"></i>
      <?php } ?>
    </div>
    <div class="col name">
      <div class="h4 name"><?= $candidate->{'fields'}->{'Name'} ?></div>
      <div class="mt-1 text-secondary party"><?= $candidate->{'fields'}->{'Party'} ?></div>
      <?php if (isset($candidate->{'fields'}->{'Website'})) { ?>
      <div class="mt-1 website">
        <a class="text-muted font-weight-bold" target="_blank" href="<?= get_url($candidate->{'fields'}->{'Website'}) ?>">
          <i class="fas fa-external-link-alt"></i> website
        </a>
      </div>
      <?php } ?>
      <?php if (isset($candidate->{'fields'}->{'Endorsed By'})) { ?>
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
