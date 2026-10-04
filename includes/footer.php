<?php $bp = base_path(); ?>
<footer class="bayan-footer mt-5">
  <div class="container py-4">
    <div class="row gy-3">
      <div class="col-md-4">
        <h6 class="fw-bold text-white"><i class="fa-solid fa-triangle-exclamation"></i> BayanAlert PH</h6>
        <p class="small text-light-emphasis mb-1">Alerto sa Bayan. Ligtas ang Lahat.</p>
        <p class="small text-light-emphasis">A community safety and disaster preparedness prototype. Community reports are not official government alerts until verified.</p>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold text-white">Official Sources</h6>
        <ul class="list-unstyled small">
          <li><a href="https://www.pagasa.dost.gov.ph" target="_blank" rel="noopener">PAGASA</a></li>
          <li><a href="https://www.phivolcs.dost.gov.ph" target="_blank" rel="noopener">PHIVOLCS</a></li>
          <li><a href="https://mgb.gov.ph" target="_blank" rel="noopener">DENR - MGB</a></li>
          <li><a href="https://ndrrmc.gov.ph" target="_blank" rel="noopener">NDRRMC</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold text-white">Disclaimer</h6>
        <p class="small text-light-emphasis">BayanAlert PH does not automatically contact emergency services or dispatch responders. Community reports are shown as submitted and are not official government alerts until verified. Always call official hotlines in a real emergency.</p>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="small text-center text-light-emphasis mb-0">&copy; <?= date('Y') ?> BayanAlert PH &mdash; College IT Project Prototype</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e($bp) ?>assets/js/app.js"></script>
</body>
</html>
