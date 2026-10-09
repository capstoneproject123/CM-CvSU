<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$total = (int) $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();
$resolved = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE status = 'Resolved'")->fetchColumn();
$pending = $total - $resolved;
$resolutionRate = $total > 0 ? round(($resolved / $total) * 100) : 0;

$byCategory = $pdo->query("SELECT category, COUNT(*) c FROM cases GROUP BY category ORDER BY c DESC")->fetchAll();
$byStatus = $pdo->query("SELECT status, COUNT(*) c FROM cases GROUP BY status ORDER BY c DESC")->fetchAll();
$byType = $pdo->query("SELECT type, COUNT(*) c FROM cases GROUP BY type ORDER BY c DESC")->fetchAll();
$byPriority = $pdo->query("SELECT priority, COUNT(*) c FROM cases GROUP BY priority ORDER BY c DESC")->fetchAll();

// Height scales with the number of categories so the horizontal bar
// chart stays readable whether there are 3 categories or 15.
$categoryChartHeight = max(180, count($byCategory) * 42 + 40);

// Same first_name/last_name pairing includes/header.php uses for the
// avatar and profile dropdown.
$printedBy = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
$printedBy = $printedBy !== '' ? ucwords(strtolower($printedBy)) : 'Admin';

$pageTitle = 'Report · CEIT CvSU';
$activeNav = 'report';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Reports &amp; Analytics</h1>
    <p>Summary of complaint and inquiry activity across CEIT.</p>
</div>
<?php render_flash(); ?>

<div class="stat-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Total Cases</div>
            <div class="stat-value"><?= $total ?></div>
        </div>
        <div class="stat-icon">📁</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Resolved</div>
            <div class="stat-value"><?= $resolved ?></div>
        </div>
        <div class="stat-icon">✅</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= $pending ?></div>
        </div>
        <div class="stat-icon">⏳</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Resolution Rate</div>
            <div class="stat-value"><?= $resolutionRate ?>%</div>
        </div>
        <div class="stat-icon">📈</div>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Cases by Category</h2></div>
    <?php if ($byCategory): ?>
        <div class="chart-wrap" style="height:<?= $categoryChartHeight ?>px;">
            <canvas id="categoryChart"></canvas>
        </div>
    <?php else: ?>
        <div class="empty-state">No data yet.</div>
    <?php endif; ?>
</div>

<div class="report-grid">
    <div class="panel">
        <div class="panel-head"><h2>By Status</h2></div>
        <?php if ($byStatus): ?>
            <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
        <?php else: ?>
            <div class="empty-state">No data yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>By Priority</h2></div>
        <?php if ($byPriority): ?>
            <div class="chart-wrap"><canvas id="priorityChart"></canvas></div>
        <?php else: ?>
            <div class="empty-state">No data yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>By Type</h2></div>
        <?php if ($byType): ?>
            <div class="chart-wrap"><canvas id="typeChart"></canvas></div>
        <?php else: ?>
            <div class="empty-state">No data yet.</div>
        <?php endif; ?>
    </div>
</div>

<div class="panel report-actions">
    <div class="text-muted print-only" id="printMeta">Printed by <?= e($printedBy) ?> </div>
    <button type="button" onclick="printReport()" class="btn btn-outline btn-sm no-print">🖨️ Print Report</button>
</div>

<script>
    function printReport() {
        var meta = document.getElementById('printMeta');
        if (meta) {
            meta.textContent = 'Printed by '
                + <?= json_encode($printedBy, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        }
        window.print();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const style  = getComputedStyle(document.documentElement);
    const cssVar = (name) => style.getPropertyValue(name).trim();
    const fallbackPalette = [
        cssVar('--green'), cssVar('--purple'), cssVar('--orange'),
        cssVar('--red'), cssVar('--gray-400'), '#3b4fb0', '#b0399b',
    ];

    // Substring-matched so a chart still gets the "right" color even if
    // the exact wording of a status/priority in the database shifts.
    function colorFor(rules, label, index) {
        const key = label.toLowerCase();
        for (const [needle, color] of rules) {
            if (key.includes(needle)) return color;
        }
        return fallbackPalette[index % fallbackPalette.length];
    }
    const statusRules = [
        ['resolv', cssVar('--green')],
        ['progress', cssVar('--orange')],
        ['review', cssVar('--purple')],
        ['submit', cssVar('--gray-400')],
    ];
    const priorityRules = [
        ['high', cssVar('--red')],
        ['medium', cssVar('--orange')],
        ['low', cssVar('--green')],
    ];
    const typeRules = [
        ['complaint', '#3b4fb0'],
        ['inquiry', '#b0399b'],
    ];

    function legendWithCounts(chart) {
        const ds = chart.data.datasets[0];
        return chart.data.labels.map((label, i) => ({
            text: `${label} (${ds.data[i]})`,
            fillStyle: ds.backgroundColor[i],
            strokeStyle: ds.backgroundColor[i],
            index: i,
        }));
    }

    function buildPieChart(canvasId, labels, data, rules) {
        const el = document.getElementById(canvasId);
        if (!el || !labels.length) return;
        new Chart(el, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: labels.map((label, i) => colorFor(rules, label, i)),
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 }, generateLabels: legendWithCounts },
                    },
                },
            },
        });
    }

    const categoryLabels = <?= json_encode(array_column($byCategory, 'category'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const categoryData   = <?= json_encode(array_map('intval', array_column($byCategory, 'c'))) ?>;
    const categoryEl = document.getElementById('categoryChart');
    if (categoryEl && categoryLabels.length) {
        new Chart(categoryEl, {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: cssVar('--green'),
                    borderRadius: 6,
                    maxBarThickness: 28,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    buildPieChart(
        'statusChart',
        <?= json_encode(array_column($byStatus, 'status'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        <?= json_encode(array_map('intval', array_column($byStatus, 'c'))) ?>,
        statusRules
    );
    buildPieChart(
        'priorityChart',
        <?= json_encode(array_column($byPriority, 'priority'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        <?= json_encode(array_map('intval', array_column($byPriority, 'c'))) ?>,
        priorityRules
    );
    buildPieChart(
        'typeChart',
        <?= json_encode(array_column($byType, 'type'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        <?= json_encode(array_map('intval', array_column($byType, 'c'))) ?>,
        typeRules
    );
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
