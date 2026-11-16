<?php

$pageTitle  = "Dashboard";
$activePage = "dashboard";
require 'header.php';
date_default_timezone_set('Europe/Oslo');

$naaDato = date('Y-m-d');
$naaTid = date('H:i:s');


/* $minBedriftsId settes i header.php */

$antallAnsatte  = countRows($conn, "SELECT COUNT(*) FROM EMPLOYEE WHERE BedriftsId = ?", 'i', [$minBedriftsId]);
$antallKontorer = countRows($conn, "SELECT COUNT(*) FROM OFFICE WHERE BedriftsId = ?", 'i', [$minBedriftsId]);
$antallMoterom  = countRows($conn, "SELECT COUNT(*) FROM MEETINGROOM WHERE BedriftsId = ?", 'i', [$minBedriftsId]);
$ledigeMoterom  = countRows($conn,
    "SELECT COUNT(*)
       FROM MEETINGROOM m
      WHERE m.BedriftsId = ?
        AND NOT EXISTS (SELECT 1 FROM BOOKING b
                         WHERE b.MoteromId = m.MoteromId
                           AND b.Dato = ?
                           AND b.Starttid <= ?
                           AND b.Sluttid > ?)",
    'isss', [$minBedriftsId, $naaDato, $naaTid, $naaTid]);

if ($role === 'employee') {
    $apneOppgaver = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE AnsattId = ? AND Status <> 'Done'", 'i', [$userId]);
    $forfalt      = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE AnsattId = ? AND Status <> 'Done' AND Frist < CURDATE()", 'i', [$userId]);
    $antallAvvik  = countRows($conn, "SELECT COUNT(*) FROM DEVIATION WHERE AnsattId = ?", 'i', [$userId]);
} else {
    $apneOppgaver = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE BedriftsId = ? AND Status <> 'Done'", 'i', [$minBedriftsId]);
    $forfalt      = countRows($conn, "SELECT COUNT(*) FROM TASKS WHERE BedriftsId = ? AND Status <> 'Done' AND Frist < CURDATE()", 'i', [$minBedriftsId]);
    $antallAvvik  = countRows($conn, "SELECT COUNT(*) FROM DEVIATION WHERE BedriftsId = ?", 'i', [$minBedriftsId]);
}

/* Belegg på møterom i prosent */
$belegg = 0;
if ($antallMoterom) {
    $belegg = (int) round((($antallMoterom - (int) $ledigeMoterom) / $antallMoterom) * 100);
}

/* --- LISTER ---------------------------------------------------------- */

if ($role === 'employee') {
    $oppgaver = fetchRows(
        $conn,
        "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
           FROM TASKS t
           LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
          WHERE t.AnsattId = ?
          ORDER BY t.Frist ASC
          LIMIT 6",
        'i',
        [$userId]
    );
} else {
    $oppgaver = fetchRows(
        $conn,
        "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
           FROM TASKS t
           LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
          WHERE t.BedriftsId = ?
          ORDER BY t.Frist ASC
          LIMIT 6",
        'i',
        [$minBedriftsId]
    );
}

$avvik = fetchRows(
    $conn,
    "SELECT d.OppgaveId, d.Beskrivelse, d.Frist, e.AnsattEpost
       FROM DEVIATION d
       LEFT JOIN EMPLOYEE e ON e.AnsattId = d.AnsattId
      WHERE d.BedriftsId = ?
      ORDER BY d.OppgaveId DESC
      LIMIT 5",
    'i',
    [$minBedriftsId]
);

$ansatte = fetchRows(
    $conn,
    "SELECT AnsattId, AnsattEpost, Tilgjengelighet
       FROM EMPLOYEE
      WHERE BedriftsId = ?
      ORDER BY AnsattId DESC
      LIMIT 6",
    'i',
    [$minBedriftsId]
);

$managers = fetchRows(
    $conn,
    "SELECT BedriftsId, Epost, Tilgjengelighet
       FROM COMPANYMANAGER
      WHERE BedriftsId = ?
      ORDER BY Epost ASC",
    'i',
    [$minBedriftsId]
);
?>

<div class="dashboard">

    <h1 class="page-title">Hello, <?= htmlspecialchars($userName) ?></h1>
    <p class="page-subtitle">
        Overview for <?= htmlspecialchars(strtolower($roleLabel)) ?> &middot;
        <?= date('j M Y') ?>
    </p>

    <!-- NØKKELTALL -->
    <div class="kpi-grid">

        <div class="info-box">
            <div class="label">Employees</div>
            <div class="value"><?= num($antallAnsatte) ?></div>
            <div class="note">Registered in the system</div>
        </div>

        <div class="info-box green">
            <div class="label">Offices</div>
            <div class="value"><?= num($antallKontorer) ?></div>
            <div class="note"><?= num($ledigeMoterom) ?> meeting rooms available</div>
        </div>

        <div class="info-box yellow">
            <div class="label"><?= $role === 'employee' ? 'My tasks' : 'Open tasks' ?></div>
            <div class="value"><?= num($apneOppgaver) ?></div>
            <div class="note"><?= num($forfalt) ?> overdue</div>
        </div>

        <div class="info-box red">
            <div class="label">Deviations</div>
            <div class="value"><?= num($antallAvvik) ?></div>
            <div class="note">Reported issues</div>
        </div>

    </div>

    <div class="panel-grid">

        <!-- VENSTRE KOLONNE -->
        <div>

            <div class="panel">
                <h3>
                    <?= $role === 'employee' ? 'My tasks' : 'Upcoming deadlines' ?>
                    <a href="reports.php">View all</a>
                </h3>

                <?php if ($oppgaver): ?>
                    <table>
                        <tr>
                            <th>Description</th>
                            <th>Assigned to</th>
                            <th>Due date</th>
                        </tr>
                        <?php foreach ($oppgaver as $o): ?>
                            <tr>
                                <td><?= htmlspecialchars($o['Beskrivelse'] ?? '') ?></td>
                                <td><?= htmlspecialchars($o['AnsattEpost'] ?? 'Unassigned') ?></td>
                                <td>
                                    <?php if (erForfalt($o['Frist'])): ?>
                                        <span class="badge late">
                                            <?= htmlspecialchars(date('j M Y', strtotime($o['Frist']))) ?>
                                        </span>
                                    <?php elseif ($o['Frist']): ?>
                                        <span class="badge">
                                            <?= htmlspecialchars(date('j M Y', strtotime($o['Frist']))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge">No due date</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No tasks registered.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3>
                    Latest deviations
                    <a href="reports.php">View all</a>
                </h3>

                <?php if ($avvik): ?>
                    <table>
                        <tr>
                            <th>Description</th>
                            <th>Reported by</th>
                            <th>Due date</th>
                        </tr>
                        <?php foreach ($avvik as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['Beskrivelse'] ?? '') ?></td>
                                <td><?= htmlspecialchars($a['AnsattEpost'] ?? 'Unknown') ?></td>
                                <td>
                                    <?= $a['Frist']
                                        ? htmlspecialchars(date('j M Y', strtotime($a['Frist'])))
                                        : '–' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No deviations reported.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- HØYRE KOLONNE -->
        <div>

            <div class="panel">
                <h3>Meeting room occupancy</h3>
                <div class="progress">
                    <span style="width: <?= (int) $belegg ?>%"></span>
                </div>
                <p style="font-size:13px;color:#6b7c8f;margin:0;">
                    <?= (int) $belegg ?>% occupied &middot;
                    <?= num($ledigeMoterom) ?> of <?= num($antallMoterom) ?> available
                </p>
            </div>

            <div class="panel">
                <h3>
                    Employees
                    <a href="employees.php">View all</a>
                </h3>

                <?php if ($ansatte): ?>
                    <table>
                        <?php foreach ($ansatte as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['AnsattEpost'] ?? '') ?></td>
                                <td style="text-align:right;">
                                    <span class="badge <?= statusClass($e['Tilgjengelighet']) ?>">
                                        <?= htmlspecialchars($e['Tilgjengelighet'] ?: 'Unknown') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="empty">No employees registered.</p>
                <?php endif; ?>
            </div>
          
            <?php if($managers): ?>
            <div class="panel">
                <h3>Managers</h3>
                <table>
                    <?php foreach ($managers as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['Epost'] ?? '') ?></td>
                            <td style="text-align:right;">
                                <span class="badge <?= statusClass($m['Tilgjengelighet']) ?>">
                                    <?= htmlspecialchars($m['Tilgjengelighet'] ?: 'Unknown') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
            <div class="panel quick-links">
                <h3>Shortcuts</h3>
                <?php if ($role !== 'employee'): ?>
                    <a href="add_employees.php?action=new">+ Add employee</a>
                    <a href="equipment.php?action=new">+ Register equipment</a>
                    <a href="add_offices.php?action=new">+ Create office</a>
                    <a href="reports.php">Delegate task</a>
                <?php else: ?>
                    <a href="office.php">Book meeting room</a>
                    <a href="equipment.php">Reserve equipment</a>
                    <a href="reports.php?action=new">Report deviation</a>
                    <a href="settings.php">Change availability</a>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<?php require 'footer.php'; ?>
