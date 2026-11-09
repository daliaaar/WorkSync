<?php
$pageTitle = "Meeting room details";
$activePage = "meetingroom";
require 'header.php';
date_default_timezone_set('Europe/Oslo');

$msg = '';
$msgOk = true;
$romId = (int) ($_GET['id'] ?? $_POST['rom_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if ($role !== 'manager') {
        http_response_code(403);
        exit('Not allowed');
    }
    $navn = trim($_POST['navn'] ?? '');
    $kapRaw = trim($_POST['kapasitet'] ?? '');
    $avdeling = trim($_POST['avdeling'] ?? '');
    $etasje = trim($_POST['etasje'] ?? '');
    $bygning = trim($_POST['bygning'] ?? '');

    $kapasitet = $kapRaw === '' ? null : (int) $kapRaw;
    $avdeling = $avdeling === '' ? null : $avdeling;
    $etasje = $etasje === '' ? null : $etasje;
    $bygning = $bygning === '' ? null : $bygning;

    if ($kapasitet !== null && $kapasitet < 1) {
        $msg = 'Capacity must be at least 1';
        $msgOk = false;
    } else {
        try {
            $u = $conn->prepare(
                "UPDATE MEETINGROOM SET Navn = ?, Kapasitet = ?, Avdeling = ?, Etasje = ?, Bygning = ? WHERE 
                MoteromId = ?"
            );
            $u->bind_param("sisssi", $navn, $kapasitet, $avdeling, $etasje, $bygning, $romId);
            $u->execute();
            $msg = 'Meeting room updated';

        } catch (Exception $e) {
            $msg = 'Could not update meeting room';
            $msgOk = false;
        }
    }
}

$s = $conn->prepare(
    "SELECT MoteromId, Navn, Kapasitet, Avdeling, Etasje, Bygning FROM MEETINGROOM WHERE MoteromId = ?"
);
$s->bind_param("i", $romId);
$s->execute();
$rom = $s->get_result()->fetch_assoc();

$plassering = [];
if($rom) {
    $plassering = array_filter([
        $rom['Bygning'],
        !empty($rom['Etasje']) ? 'Floor ' . $rom['Etasje'] : null,
        $rom['Avdeling'],
    ]);
}
?>
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 20px;
        margin: 0;
    }
    .detail-item dt {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        opacity: .65;
        margin-bottom: 4px;
    }
    .detail-item dd {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
    }
    .detail-item dd.empty {
        font-weight: 400;
        opacity: .5;
    }
    .edit-form { max-width: 480px; }
    .edit-form label {
        display: block;
        font-size: .85rem;
        opacity: .85;
        margin-bottom: 12px;
    }
    .edit-form input,
    .edit-form select {
        display: block;
        width: 100%;
        box-sizing: border-box;
        margin-top: 4px;
    }
</style>
<div class="dashboard">
    <a href="office.php" class="back-button">&larr; Back to offices</a>

    <?php if (!$rom): ?>
        <p class="page-title">Meeting room not found</p>
    <?php else: ?>
        <h1 class="page-title">
            Room <?= (int) $rom['MoteromId'] ?><?= $rom['Navn'] ? ' - ' . htmlspecialchars($rom['Navn']) : '' ?>
        </h1>

        <?php if ($plassering): ?>
            <p class="page-subtitle"><?= htmlspecialchars(implode(', ', $plassering)) ?></p>
        <?php endif; ?>

        <?php if ($msg): ?>
            <p class="badge <?= $msgOk ? 'ok' : 'busy' ?>"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>

        <div class="panel">
            <?php if ($role === 'manager'): ?>
                <form method="post" class="edit-form" action="?id=<?= (int) $rom['MoteromId'] ?>">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="rom_id" value="<?= (int) $rom['MoteromId'] ?>">
                    <label>Name 
                        <input type="text" name="navn" maxlength="100" 
                                value="<?= htmlspecialchars($rom['Navn'] ?? '') ?>">
                    </label>
                    <label>Capacity
                        <input type="number" name="kapasitet" min="1" 
                                value="<?= $rom['Kapasitet'] ? (int) $rom['Kapasitet'] : '' ?>">
                    </label>
                    <label>Department
                        <input type="text" name="avdeling" maxlength="100" 
                                value="<?= htmlspecialchars($rom['Avdeling'] ?? '') ?>">
                    </label>
                    <label>Floor
                        <input type="text" name="etasje" maxlength="20" 
                                value="<?= htmlspecialchars($rom['Etasje'] ?? '') ?>">
                    </label>
                    <label>Building
                        <input type="text" name="bygning" maxlength="100" 
                                value="<?= htmlspecialchars($rom['Bygning'] ?? '') ?>">
                    </label>
                    <button type="submit" class="btn-small">Save changes</button>
                    <a href="office.php">Cancel</a>
                </form>
            <?php else: ?>
                <dl class="detail-grid">
                    <?php
                    $felt = [
                        'Building' => $rom['Bygning'],
                        'Floor' => $rom['Etasje'],
                        'Department' => $rom['Avdeling'],
                        'Capacity' => $rom['Kapasitet'] ? (int) $rom['Kapasitet'] . ' people' : null,
                    ];
                    foreach ($felt as $etikett => $verdi): ?>
                        <div class="detail-item">
                            <dt><?= $etikett ?></dt>
                            <dd class="<?= $verdi ? '' : 'empty' ?>">
                                <?= $verdi ? htmlspecialchars($verdi) : 'Not specified' ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
