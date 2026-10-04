<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$pageTitle = 'Learn & Prepare';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$guides = [
    'Earthquake' => ['icon' => 'fa-house-crack', 'color' => 'text-danger', 'content' => [
        'Before' => 'Secure heavy furniture to walls. Prepare a "go bag" with water, food, flashlight, and first aid kit. Identify safe spots in each room (under sturdy tables, away from windows).',
        'During' => 'Drop, Cover, and Hold On. If indoors, stay there. If outdoors, move to an open area away from buildings and power lines.',
        'After' => 'Check yourself and others for injuries. Expect aftershocks. Inspect your home for damage before re-entering. Avoid using elevators.',
    ]],
    'Typhoon' => ['icon' => 'fa-wind', 'color' => 'text-primary', 'content' => [
        'Before' => 'Monitor PAGASA bulletins. Secure loose outdoor items. Stock up on food, water, batteries, and charge devices. Know your nearest evacuation center.',
        'During' => 'Stay indoors, away from windows. Avoid unnecessary travel. Follow local evacuation orders promptly.',
        'After' => 'Avoid downed power lines and flooded roads. Wait for official "all clear" before returning home.',
    ]],
    'Flood' => ['icon' => 'fa-water', 'color' => 'text-info', 'content' => [
        'Before' => 'Know your area\'s flood history. Move valuables to higher ground. Prepare an emergency kit and evacuation plan.',
        'During' => 'Move to higher ground immediately. Never walk or drive through floodwater — 15cm of moving water can knock you down.',
        'After' => 'Avoid floodwater as it may be contaminated. Watch for structural damage before entering buildings.',
    ]],
    'Fire' => ['icon' => 'fa-fire', 'color' => 'text-orange', 'content' => [
        'Before' => 'Install smoke detectors. Plan and practice a fire escape route. Keep flammable materials away from heat sources.',
        'During' => 'Get low and go if there is smoke. Feel doors before opening. If your clothes catch fire: Stop, Drop, and Roll.',
        'After' => 'Do not re-enter a burned building. Call the Bureau of Fire Protection to confirm it is fully extinguished.',
    ]],
    'Landslide' => ['icon' => 'fa-mountain', 'color' => 'text-secondary', 'content' => [
        'Warning Signs' => 'Cracks appearing in the ground or pavement, tilting trees or fences, unusual sounds like cracking trees or boulders.',
        'Safety' => 'Move away from the path of a landslide immediately, not straight downhill. Stay alert during and after heavy rainfall in hilly areas.',
    ]],
    'Tsunami' => ['icon' => 'fa-water', 'color' => 'text-primary', 'content' => [
        'Warning Signs' => 'A strong earthquake near the coast, sudden rise or withdrawal of sea water — these are natural tsunami warnings.',
        'Evacuation' => 'Move immediately to higher ground or as far inland as possible, without waiting for an official alert if you feel a strong quake near the coast.',
    ]],
    'Extreme Heat' => ['icon' => 'fa-temperature-high', 'color' => 'text-danger', 'content' => [
        'Prevention' => 'Stay hydrated, avoid strenuous outdoor activity during peak heat hours, wear light-colored and loose clothing.',
        'Warning Signs' => 'Heat exhaustion symptoms include heavy sweating, weakness, and dizziness. Move to a cool place and seek medical help if symptoms worsen.',
    ]],
];
?>
<div class="container py-4">
  <h4 class="fw-bold mb-1"><i class="fa-solid fa-book-open text-warning"></i> Learn &amp; Prepare</h4>
  <p class="text-muted mb-4">Clear, simple safety guidance for common hazards in the Philippines.</p>

  <div class="accordion" id="prepareAccordion">
    <?php $i = 0; foreach ($guides as $hazard => $g): $i++; ?>
    <div class="accordion-item">
      <h2 class="accordion-header">
        <button class="accordion-button <?= $i > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#h<?= $i ?>">
          <i class="fa-solid <?= e($g['icon']) ?> hazard-icon <?= e($g['color']) ?>"></i> <?= e($hazard) ?>
        </button>
      </h2>
      <div id="h<?= $i ?>" class="accordion-collapse collapse <?= $i === 1 ? 'show' : '' ?>" data-bs-parent="#prepareAccordion">
        <div class="accordion-body">
          <?php foreach ($g['content'] as $label => $text): ?>
            <h6 class="fw-bold mt-2"><?= e($label) ?></h6>
            <p class="small"><?= e($text) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="alert alert-info mt-4 small">
    <i class="fa-solid fa-circle-info"></i> This guidance is general safety information for education purposes. For official, detailed protocols, refer to <a href="https://ndrrmc.gov.ph" target="_blank" rel="noopener">NDRRMC</a>, <a href="https://www.pagasa.dost.gov.ph" target="_blank" rel="noopener">PAGASA</a>, and <a href="https://www.phivolcs.dost.gov.ph" target="_blank" rel="noopener">PHIVOLCS</a>.
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
