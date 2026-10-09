<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin']);

$total = (int) $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();
$resolved = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE status = 'Resolved'")->fetchColumn();
$pending = $total - $resolved;
$resolutionRate = $total > 0 ? round(($resolved / $total) * 100) : 0;

$byCategory = $pdo->query("SELECT category, COUNT(*) c FROM cases GROUP BY category ORDER BY c DESC")->fetchAll();
$byStatus = $pdo->query("SELECT status, COUNT(*) c FROM cases GROUP BY status ORDER BY c DESC")->fetchAll();
$byType = $pdo->query("SELECT type, COUNT(*) c FROM cases GROUP BY type ORDER BY c DESC")->fetchAll();
// High -> Medium -> Low reads better in a formal table than "most common first".
$byPriority = $pdo->query("SELECT priority, COUNT(*) c FROM cases GROUP BY priority ORDER BY FIELD(priority, 'High', 'Medium', 'Low')")->fetchAll();

// What students typed when they picked "Others" in submit.php.
// Needs the cases.category_other column. If it hasn't been added yet, skip this
// breakdown instead of crashing the whole report.
// Cases filed before the column existed show as "Not specified", so the sub-rows
// always add up to the "Others" count in the Category table.
$othersSpecified = [];
try {
    $othersSpecified = $pdo->query("SELECT COALESCE(NULLIF(TRIM(category_other), ''), 'Not specified') AS label, COUNT(*) c
                                    FROM cases
                                    WHERE category = 'Others'
                                    GROUP BY label
                                    ORDER BY (label = 'Not specified'), c DESC, label")->fetchAll();
} catch (PDOException $e) {
    $othersSpecified = [];
}

// Height scales with the number of categories so the horizontal bar
// chart stays readable whether there are 3 categories or 15.
$categoryChartHeight = max(180, count($byCategory) * 42 + 40);

// Same first_name/last_name pairing includes/header.php uses for the
// avatar and profile dropdown.
$printedBy = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
$printedBy = $printedBy !== '' ? ucwords(strtolower($printedBy)) : 'Admin';

$generatedAt = new DateTime('now', new DateTimeZone('Asia/Manila'));
$reportRef = 'RPT-' . $generatedAt->format('Ymd-Hi');

function report_pct(int $n, int $total): string
{
    return $total > 0 ? number_format(($n / $total) * 100, 1) . '%' : '0.0%';
}

/**
 * One bordered table: label | No. of Cases | Percentage, plus a Total row.
 * $rows is a fetchAll() result where each row has $labelKey and "c" (the count).
 * $details maps a row label to extra rows (each with "label" and "c") that are
 * printed indented right under that row, e.g. ['Others' => what students specified].
 */
function render_distribution_table(string $numeral, string $title, string $labelHeader, array $rows, string $labelKey, array $details = []): void
{
    $sum = array_sum(array_map('intval', array_column($rows, 'c')));
    ?>
    <section class="rpt-section">
        <h3 class="rpt-heading"><?= e($numeral) ?>. <?= e($title) ?></h3>
        <table class="rpt-table">
            <thead>
                <tr>
                    <th><?= e($labelHeader) ?></th>
                    <th class="num">No. of Cases</th>
                    <th class="num">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e(ucfirst((string) $r[$labelKey])) ?></td>
                            <td class="num"><?= (int) $r['c'] ?></td>
                            <td class="num"><?= report_pct((int) $r['c'], $sum) ?></td>
                        </tr>
                        <?php foreach (($details[(string) $r[$labelKey]] ?? []) as $d): ?>
                            <tr class="rpt-subrow">
                                <td><?= e(ucfirst((string) $d['label'])) ?></td>
                                <td class="num"><?= (int) $d['c'] ?></td>
                                <td class="num"><?= report_pct((int) $d['c'], $sum) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="rpt-empty">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num"><?= $sum ?></td>
                    <td class="num"><?= $sum > 0 ? '100.0%' : '0.0%' ?></td>
                </tr>
            </tfoot>
        </table>
    </section>
    <?php
}

$pageTitle = 'Report · CEIT CvSU';
$activeNav = 'report';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<style>
    /* ---- Official report document: hidden on screen, shown only when printing ---- */
    .official-report {
        display: none;
        background: #fff;
        color: #111;
        max-width: 860px;
        margin: 0 auto 28px;
        padding: 36px 44px;
        border: 1px solid #d9dce1;
        border-radius: 6px;
        font-family: "Times New Roman", Times, serif;
        font-size: 14px;
        line-height: 1.5;
    }
    .rpt-letterhead {
        text-align: center;
        padding-bottom: 12px;
        margin-bottom: 18px;
        border-bottom: 3px double #111;
    }
    .rpt-republic { font-size: 12px; }
    .rpt-univ { font-size: 20px; font-weight: 700; letter-spacing: .5px; }
    .rpt-college { font-size: 15px; font-weight: 700; }
    .rpt-system { font-size: 12px; font-style: italic; }

    .rpt-title {
        text-align: center;
        font-size: 17px;
        font-weight: 700;
        text-transform: uppercase;
        margin: 0 0 4px;
    }
    .rpt-intro { text-align: center; font-size: 13px; margin: 0 0 16px; }

    .rpt-meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 13px; }
    .rpt-meta td { padding: 2px 0; vertical-align: top; }
    .rpt-meta td:first-child { width: 130px; font-weight: 700; }

    .rpt-heading { font-size: 14px; font-weight: 700; margin: 18px 0 6px; }

    .rpt-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 6px; }
    .rpt-table th,
    .rpt-table td { border: 1px solid #111; padding: 6px 10px; text-align: left; }
    .rpt-table thead th { background: #e9ebef; }
    .rpt-table .num { text-align: right; width: 110px; }
    .rpt-table tfoot td { font-weight: 700; background: #f3f4f6; }
    .rpt-empty { text-align: center; font-style: italic; }
    .rpt-subrow td { font-style: italic; }
    .rpt-subrow td:first-child { padding-left: 26px; }
    .rpt-subrow td:first-child::before { content: "– "; }

    .rpt-signatures { display: flex; justify-content: flex-end; margin-top: 46px; }
    .rpt-sig { flex: 0 0 260px; text-align: center; font-size: 13px; }
    .rpt-sig-label { text-align: left; margin-bottom: 34px; }
    .rpt-sig-line { border-top: 1px solid #111; padding-top: 3px; min-height: 22px; font-weight: 700; }
    .rpt-sig-caption { font-size: 11px; }
    .rpt-end { text-align: center; font-size: 11px; font-style: italic; margin-top: 26px; }

    .rpt-toolbar { display: flex; justify-content: flex-end; align-items: center; gap: 14px; }

    @media print {
        @page { size: A4 portrait; margin: 14mm; }
        .no-print { display: none !important; }
        .rpt-charts { display: none !important; }
        .rpt-charts.include-in-print { display: block !important; break-before: page; }
        .official-report {
            display: block;
            max-width: none;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }
        .rpt-table thead th,
        .rpt-table tfoot td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .rpt-section,
        .rpt-signatures { break-inside: avoid; }
        .rpt-table tr { break-inside: avoid; }
    }
</style>

<div class="page-header no-print">
    <h1>Reports &amp; Analytics</h1>
    <p>Summary of complaint and inquiry activity across CEIT.</p>
</div>
<?php render_flash(); ?>

<!-- Print-only: not shown on screen, appears in the print preview when "Print Report" is clicked (or Ctrl+P). -->
<article class="official-report">
    <header class="rpt-letterhead">
        <div class="rpt-republic">Republic of the Philippines</div>
        <div class="rpt-univ">CAVITE STATE UNIVERSITY</div>
        <div class="rpt-college">Don Severino Delas Alas - Main Campus</div>
        <div class="rpt-college">Indang, Cavite</div>
        <div class="rpt-college">College of Engineering and Information Technology</div>
        <div class="rpt-system">Complaint and Inquiry Management System</div>
    </header>

    <h2 class="rpt-title">Summary Report on Complaints and Inquiries</h2>
    <p class="rpt-intro">Covers all cases on record as of <?= e($generatedAt->format('F j, Y')) ?>.</p>

    <table class="rpt-meta">
        <tr><td>Report No.</td><td>: <?= e($reportRef) ?></td></tr>
        <tr><td>Date generated</td><td>: <?= e($generatedAt->format('F j, Y, g:i A')) ?></td></tr>
        <tr><td>Prepared by</td><td>: <?= e($printedBy) ?></td></tr>
    </table>

    <section class="rpt-section">
        <h3 class="rpt-heading">I. Overall Summary</h3>
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th class="num">No. of Cases</th>
                    <th class="num">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total cases received</td>
                    <td class="num"><?= $total ?></td>
                    <td class="num"><?= $total > 0 ? '100.0%' : '0.0%' ?></td>
                </tr>
                <tr>
                    <td>Resolved (resolution rate)</td>
                    <td class="num"><?= $resolved ?></td>
                    <td class="num"><?= report_pct($resolved, $total) ?></td>
                </tr>
                <tr>
                    <td>Pending (not yet resolved)</td>
                    <td class="num"><?= $pending ?></td>
                    <td class="num"><?= report_pct($pending, $total) ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <?php render_distribution_table('II', 'Cases by Type', 'Type', $byType, 'type'); ?>
    <?php render_distribution_table('III', 'Cases by Category', 'Category', $byCategory, 'category', ['Others' => $othersSpecified]); ?>
    <?php render_distribution_table('IV', 'Cases by Priority', 'Priority', $byPriority, 'priority'); ?>
    <?php render_distribution_table('V', 'Cases by Status', 'Status', $byStatus, 'status'); ?>

    <div class="rpt-signatures">
        <div class="rpt-sig">
            <div class="rpt-sig-label">Prepared by:</div>
            <div class="rpt-sig-line"><?= e($printedBy) ?></div>
            <div class="rpt-sig-caption">Name and Signature</div>
        </div>
    </div>

    <div class="rpt-end">— End of report —</div>
</article>

<!-- Charts: always on screen; printed only if "Include charts when printing" is ticked (then on a new page after the tables). -->
<div class="rpt-charts" id="rptCharts">
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
</div>

<!-- Print area: sits at the bottom of the page, below the charts. -->
<div class="panel rpt-toolbar no-print">
    <label class="text-muted" style="font-size:13px;">
        <input type="checkbox" id="includeCharts"> Include charts when printing
    </label>
    <button type="button" onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print Report</button>
</div>

<script>
    document.getElementById('includeCharts').addEventListener('change', function () {
        document.getElementById('rptCharts').classList.toggle('include-in-print', this.checked);
    });
</script>

<script>
    // While printing, hide the site chrome (top bar with logo/date/bell/avatar, sidebar, footer, flash messages)
    // so only the report is on paper. Works from the report's position in the page, so it doesn't depend on
    // class names in header.php / sidebar.php. Everything is restored right after printing.
    (function () {
        var hidden = [];

        function hideChrome() {
            var report = document.querySelector('.official-report');
            if (!report) return;
            var charts = document.getElementById('rptCharts');
            var keepCharts = charts && charts.classList.contains('include-in-print');
            var node = report;
            while (node && node !== document.body) {
                var parent = node.parentElement;
                Array.prototype.forEach.call(parent.children, function (sib) {
                    if (sib === node) return;
                    if (sib.tagName === 'SCRIPT' || sib.tagName === 'STYLE') return;
                    if (keepCharts && sib === charts) return;
                    if (getComputedStyle(sib).display === 'none') return;
                    hidden.push([sib, sib.style.display]);
                    sib.style.display = 'none';
                });
                node = parent;
            }
        }

        function restoreChrome() {
            hidden.forEach(function (h) { h[0].style.display = h[1]; });
            hidden = [];
        }

        window.addEventListener('beforeprint', hideChrome);
        window.addEventListener('afterprint', restoreChrome);
    })();
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