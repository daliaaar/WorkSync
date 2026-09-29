<?php
/* WorkSync – Reports
   Viser oppgaver og innmeldte avvik. Ansatte ser bare sine egne. */

$pageTitle  = "Reports";
$activePage = "reports";
require 'header.php';

if ($role === 'employee') {
    $oppgaver = fetchRows(
        $conn,
        "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
           FROM TASKS t
           LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
          WHERE t.AnsattId = ?
          ORDER BY t.Frist ASC",
        'i',
        [$userId]
    );
    $avvik = fetchRows(
        $conn,
        "SELECT d.OppgaveId, d.Beskrivelse, d.Frist, e.AnsattEpost
           FROM DEVIATION d
           LEFT JOIN EMPLOYEE e ON e.AnsattId = d.AnsattId
          WHERE d.AnsattId = ?
          ORDER BY d.OppgaveId DESC",
        'i',
        [$userId]
    );
} else {
    $oppgaver = fetchRows(
        $conn,
        "SELECT t.OppgaveId, t.Beskrivelse, t.Frist, e.AnsattEpost
           FROM TASKS t
           LEFT JOIN EMPLOYEE e ON e.AnsattId = t.AnsattId
          ORDER BY t.Frist ASC"
    );
    $avvik = fetchRows(
        $conn,
        "SELECT d.OppgaveId, d.Beskrivelse, d.Frist, e.AnsattEpost
           FROM DEVIATION d
           LEFT JOIN EMPLOYEE e ON e.AnsattId = d.AnsattId
          ORDER BY d.OppgaveId DESC"
    );
}
?>

<div class="dashboard">

    <h1 class="page-title">Reports</h1>
    <p class="page-subtitle">Tasks and reported deviations</p>

    <div class="panel">
        <h3>
            <?= $role === 'employee' ? 'My tasks' : 'All tasks' ?>
            <?php if ($role !== 'employee'): ?>
                <a href="manager_dashboard.php">+ Delegate task</a>
            <?php endif; ?>
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
            Deviations
            <a href="reports.php?action=new">+ Report deviation</a>
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

<?php require 'footer.php'; ?>
