<?php
// Proměnné dostupné z index.php:
// $projectId    string
// $currentFont  string|null
// $fonts        array
// $fontsJson    string
// $baseUrl      string
// $isMock       bool
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Custom Fonts</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/admin.css">
</head>
<body>

<div class="header">
  <h1>
    Vlastní písmo
    <?php if ($isMock): ?>
      <span class="mock-badge">MOCK</span>
    <?php endif; ?>
  </h1>
  <p>Vyberte písmo z Google Fonts, které se použije v celém eshopu.</p>
</div>

<?php if ($currentFont): ?>
<div class="current-font-banner" id="current-font-banner">
  <div class="dot"></div>
  <span id="current-font-label">Aktivní písmo: <?= htmlspecialchars($currentFont) ?></span>
</div>
<?php else: ?>
<div class="current-font-banner" id="current-font-banner" style="display:none">
  <div class="dot"></div>
  <span id="current-font-label"></span>
</div>
<?php endif; ?>

<div class="card">
  <div class="search-row">
    <input type="text" id="font-search" placeholder="Hledat písmo…" autocomplete="off">
  </div>

  <div class="filter-row" id="filter-row">
    <!-- Vyplní admin.js -->
  </div>

  <div class="font-list" id="font-list">
    <!-- Vyplní admin.js -->
  </div>

  <div class="preview-section">
    <label>Náhled</label>
    <div class="preview-box" id="preview-box">
      <h3><?= $currentFont ? htmlspecialchars($currentFont) : 'Žádný font nevybrán' ?></h3>
      <p>Příklad textu • Sample text 123</p>
    </div>
  </div>

  <div class="actions">
    <button class="btn btn-primary" id="save-btn">Uložit nastavení</button>
    <?php if ($currentFont): ?>
      <button class="btn btn-ghost" id="clear-btn">Odebrat písmo</button>
    <?php else: ?>
      <button class="btn btn-ghost" id="clear-btn" style="display:none">Odebrat písmo</button>
    <?php endif; ?>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
  window.FONTS_DATA   = <?= $fontsJson ?>;
  window.CURRENT_FONT = <?= $currentFont ? json_encode($currentFont) : 'null' ?>;
  window.PROJECT_ID   = <?= json_encode($projectId) ?>;
  window.API_SAVE_URL = <?= json_encode($baseUrl . '/api/save') ?>;
</script>
<script src="<?= htmlspecialchars($baseUrl) ?>/assets/admin.js"></script>

</body>
</html>
