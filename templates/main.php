<?php
declare(strict_types=1);
/** @var array $_ */
style('wol', 'main');   // apps/wol/css/main.css
script('wol', 'main');  // apps/wol/js/main.js

$devices   = $_['devices'] ?? [];
$statusUrl = \OC::$server->getURLGenerator()->linkToRoute('wol.wol.status');
$rt        = \OC::$server->getCsrfTokenManager()->getToken()->getEncryptedValue();
?>
<meta name="requesttoken" content="<?php p($rt); ?>">

<div id="wol-app" class="wol-container" data-status-url="<?php p($statusUrl); ?>">
  <div class="wol-toolbar">
    <h2 class="wol-title">Wake on LAN</h2>
    <button id="wol-open-add" class="btn primary">+ Add device</button>
  </div>

  <?php if (empty($devices)): ?>
    <div class="wol-empty">
      <div class="wol-empty-title">No devices yet</div>
      <div class="wol-empty-sub">Click “Add device” to create your first WOL target.</div>
    </div>
  <?php else: ?>
    <div id="wol-grid" class="devices-grid">
      <?php foreach ($devices as $d): ?>
        <div class="device-card" data-id="<?= (int)$d['id'] ?>">
          <div class="device-card__header">
            <div class="device-name" title="<?= htmlspecialchars($d['name']) ?>">
              <?= htmlspecialchars($d['name']) ?>
            </div>
            <span class="wol-dot unknown" title="Unknown" data-id="<?= (int)$d['id'] ?>"></span>
          </div>

          <div class="device-meta">
            <div><span class="meta-key">Host</span><span class="meta-val"><?= htmlspecialchars($d['host'] ?: ($d['host'] ?? 'Not Set')) ?></span></div>
            <div><span class="meta-key">MAC</span><span class="meta-val"><?= htmlspecialchars($d['mac']) ?></span></div>
            <div><span class="meta-key">Broadcast</span><span class="meta-val"><?= htmlspecialchars($d['broadcast']) ?></span></div>
            <div><span class="meta-key">Port</span><span class="meta-val"><?= (int)$d['port'] ?></span></div>
          </div>

          <div class="device-actions">
            <button class="btn primary wake-btn" data-devicename="<?= htmlspecialchars($d['name']) ?>">Wake</button>
            <button class="btn subtle del-btn">Delete</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ADD DEVICE MODAL -->
<div id="wol-add-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="wol-add-title" aria-hidden="true">
  <div class="modal__backdrop" data-close="true"></div>
  <div class="modal__dialog">
    <div class="modal__header">
      <h3 id="wol-add-title" class="modal__title">Add device</h3>
      <button class="modal__close" type="button" data-close="true" aria-label="Close">×</button>
    </div>
    <form id="wol-add-form" class="modal__body">
      <label class="fld"><span>Name</span><input name="name" placeholder="My PC" required></label>
      <label class="fld"><span>MAC</span><input name="mac" placeholder="AA:BB:CC:DD:EE:FF" required></label>
      <label class="fld"><span>Host (IP or DNS)</span><input name="host" placeholder="192.168.1.184 or pc.local" required></label>
      <label class="fld"><span>Broadcast</span><input name="broadcast" placeholder="192.168.1.255" required></label>
      <label class="fld fld--inline"><span>Port</span><input name="port" type="number" min="1" max="65535" value="9" required></label>
      <div class="modal__footer">
        <button class="btn" type="button" data-close="true">Cancel</button>
        <button class="btn primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- MESSAGE MODAL (auto-closes after 5s) -->
<div id="wol-msg-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="wol-msg-title" aria-hidden="true">
  <div class="modal__backdrop" data-close="true"></div>
  <div class="modal__dialog modal__dialog--sm">
    <div class="modal__header">
      <h3 id="wol-msg-title" class="modal__title">Message</h3>
      <button class="modal__close" type="button" data-close="true" aria-label="Close">×</button>
    </div>
    <div class="modal__body">
      <div id="wol-msg-text"></div>
    </div>
  </div>
</div>

