<?php
// Variables injected from index.php:
//   $projectId       string
//   $currentSettings array  - see ShoptetApi::getCurrentSettings()
//   $fontsJson       string  - JSON array of {name, category}
//   $defaultHSizes   array   - ['h1'=>'36px', ...]
//   $defaultMobSizes array   - ['h1'=>'27px', ...]
//   $baseUrl         string
//   $isMock          bool
declare(strict_types=1);

$body     = $currentSettings['body']     ?? [];
$headings = $currentSettings['headings'] ?? [];

$iconDesk = '<svg class="sz-icon" viewBox="0 0 16 14" width="15" height="13" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="1" width="14" height="9" rx="1.2"/><path d="M6 10l-1 3M10 10l1 3M5 13h6"/></svg>';
$iconMob  = '<svg class="sz-icon" viewBox="0 0 10 16" width="10" height="15" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="1" width="8" height="14" rx="2"/><circle cx="5" cy="12.5" r=".9" fill="currentColor" stroke="none"/></svg>';
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
  <p>Nastavte písmo pro tělo stránky a nadpisy. Uloží se přes Shoptet API jako CSS injekce.</p>
</div>

<div class="cards-grid">

<!-- Body text section -->
<div class="card" id="section-body">
  <div class="section-title">
    <span class="section-icon">P</span> Tělo stránky
  </div>

  <div class="field-row">
    <label class="field-label">Písmo</label>
    <div class="font-combobox" id="body-combobox">
      <div class="combobox-inner">
        <input type="text" class="font-input" placeholder="Hledat nebo vybrat písmo..."
               autocomplete="off" spellcheck="false"
               value="<?= htmlspecialchars($body['family'] ?? '') ?>">
        <button type="button" class="combobox-clear" title="Odebrat"
                style="<?= empty($body['family']) ? 'display:none' : '' ?>">x</button>
      </div>
      <div class="font-dropdown"></div>
    </div>
  </div>

  <div class="field-row">
    <label class="field-label">Řez (weight)</label>
    <select class="field-select" id="body-weight">
      <?php foreach ([100=>'Thin',200=>'Extra Light',300=>'Light',400=>'Regular',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $w=>$label): ?>
        <option value="<?= $w ?>" <?= ($body['weight'] ?? '400') == $w ? 'selected' : '' ?>><?= $w ?> - <?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field-row">
    <label class="field-label">Velikost písma</label>
    <div class="size-pair-row">
      <?= $iconDesk ?>
      <input type="text" class="size-input" id="body-size"
             placeholder="16px"
             value="<?= htmlspecialchars($body['size'] ?? '') ?>">
      <?= $iconMob ?>
      <input type="text" class="size-input" id="body-mobile-size"
             placeholder="14px"
             value="<?= htmlspecialchars($body['mobileSize'] ?? '') ?>">
    </div>
  </div>

  <div class="field-row">
    <label class="field-label">
      Vlastní selektory
      <span class="field-hint">čárkou oddělené, bude přidáno <code>!important</code></span>
    </label>
    <input type="text" class="field-input" id="body-selectors"
           placeholder=".product-name, #description, .custom-text"
           value="<?= htmlspecialchars($body['extraSelectors'] ?? '') ?>">
  </div>

  <div class="preview-section">
    <label class="field-label">Náhled</label>
    <div class="preview-box" id="body-preview">
      <p>Toto je ukázkový text těla stránky. This is a sample body text.</p>
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </div>
  </div>
</div>

<!-- Headings section -->
<div class="card" id="section-headings" style="min-width:0">
  <div class="section-title">
    <span class="section-icon">H</span> Nadpisy
  </div>

  <div class="field-row">
    <label class="field-label">Písmo</label>
    <div class="font-combobox" id="headings-combobox">
      <div class="combobox-inner">
        <input type="text" class="font-input" placeholder="Hledat nebo vybrat písmo..."
               autocomplete="off" spellcheck="false"
               value="<?= htmlspecialchars($headings['family'] ?? '') ?>">
        <button type="button" class="combobox-clear" title="Odebrat"
                style="<?= empty($headings['family']) ? 'display:none' : '' ?>">x</button>
      </div>
      <div class="font-dropdown"></div>
    </div>
  </div>

  <div class="field-row two-col">
    <div>
      <label class="field-label">
        Výchozí řez
        <span class="field-hint">platí pro všechna H, pokud není přepsána níže</span>
      </label>
      <select class="field-select" id="headings-weight">
        <?php foreach ([100=>'Thin',200=>'Extra Light',300=>'Light',400=>'Regular',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $w=>$label): ?>
          <option value="<?= $w ?>" <?= ($headings['weight'] ?? '700') == $w ? 'selected' : '' ?>><?= $w ?> - <?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><!-- spacer --></div>
  </div>

  <div class="field-row">
    <label class="field-label">
      Vlastní selektory
      <span class="field-hint">carkou oddelene, bude pridano <code>!important</code></span>
    </label>
    <input type="text" class="field-input" id="headings-selectors"
           placeholder=".hero-title, .page-title"
           value="<?= htmlspecialchars($headings['extraSelectors'] ?? '') ?>">
  </div>

  <div class="field-row">
    <label class="field-label">
      Nastavení pro každý nadpis
      <span class="field-hint">přepíše výchozí hodnoty</span>
    </label>
    <?php
    $weightOptions = [100=>'Thin',200=>'Extra Light',300=>'Light',400=>'Regular',
                      500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'];
    foreach (['h1','h2','h3','h4','h5','h6'] as $tag):
      $sz    = $headings['sizes'][$tag]       ?? $defaultHSizes[$tag];
      $msz   = $headings['mobileSizes'][$tag] ?? $defaultMobSizes[$tag];
      $wt    = $headings['weights'][$tag]     ?? '';
      $color = $headings['colors'][$tag]      ?? '';
      $upper = !empty($headings['textTransforms'][$tag]);
    ?>
      <div class="heading-row">
        <span class="heading-row-label"><?= strtoupper($tag) ?></span>

        <div class="heading-row-sizes">
          <div class="heading-row-field heading-row-field--sz">
            <?= $iconDesk ?>
            <input type="text" class="heading-size-input"
                   data-tag="<?= $tag ?>"
                   placeholder="<?= $defaultHSizes[$tag] ?>"
                   value="<?= htmlspecialchars($sz) ?>">
          </div>
          <div class="heading-row-field heading-row-field--sz">
            <?= $iconMob ?>
            <input type="text" class="heading-mobile-size-input"
                   data-tag="<?= $tag ?>"
                   placeholder="<?= $defaultMobSizes[$tag] ?>"
                   value="<?= htmlspecialchars($msz) ?>">
          </div>
        </div>

        <div class="heading-row-controls">
          <div class="heading-row-field">
            <span class="heading-row-sublabel">Řez</span>
            <select class="heading-weight-select" data-tag="<?= $tag ?>">
              <option value="" <?= $wt === '' ? 'selected' : '' ?>>Vychozi</option>
              <?php foreach ($weightOptions as $w => $wLabel): ?>
                <option value="<?= $w ?>" <?= (string)$wt === (string)$w ? 'selected' : '' ?>><?= $w ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="heading-row-field heading-row-field--color">
            <span class="heading-row-sublabel">Barva</span>
            <input type="checkbox" class="heading-color-enable" data-tag="<?= $tag ?>"
                   <?= $color !== '' ? 'checked' : '' ?>>
            <input type="color" class="heading-color-input" data-tag="<?= $tag ?>"
                   value="<?= htmlspecialchars($color ?: '#333333') ?>"
                   <?= $color === '' ? 'disabled' : '' ?>>
          </div>
          <div class="heading-row-field heading-row-field--caps">
            <span class="heading-row-sublabel">Caps</span>
            <input type="checkbox" class="heading-uppercase-check" data-tag="<?= $tag ?>"
                   <?= $upper ? 'checked' : '' ?>>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="preview-section">
    <label class="field-label">Náhled</label>
    <div class="preview-box" id="headings-preview">
      <?php foreach (['h1','h2','h3','h4','h5','h6'] as $tag):
        $sz = $headings['sizes'][$tag] ?? $defaultHSizes[$tag]; ?>
        <<?= $tag ?> style="font-size:<?= htmlspecialchars($sz) ?>;margin:0 0 4px"><?= strtoupper($tag) ?> - Ukázkový nadpis</<?= $tag ?>>
      <?php endforeach; ?>
    </div>
  </div>
</div>

</div><!-- /.cards-grid -->

<!-- Actions -->
<div class="actions-bar">
  <button class="btn btn-primary" id="save-btn">Uložit nastavení</button>
  <button class="btn btn-ghost" id="clear-btn">Odebrat vše</button>
</div>

<div class="toast" id="toast"></div>

<script>
  window.FONTS_DATA          = <?= $fontsJson ?>;
  window.CURRENT_SETTINGS    = <?= json_encode($currentSettings, JSON_UNESCAPED_UNICODE) ?>;
  window.DEFAULT_H_SIZES     = <?= json_encode($defaultHSizes) ?>;
  window.DEFAULT_H_MOB_SIZES = <?= json_encode($defaultMobSizes) ?>;
  window.PROJECT_ID          = <?= json_encode($projectId) ?>;
  window.API_SAVE_URL        = <?= json_encode("{$baseUrl}/api/save") ?>;
</script>
<script src="<?= htmlspecialchars($baseUrl) ?>/assets/admin.js"></script>

</body>
</html>
