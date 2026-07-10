<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/NeetSearchService.php';

$service = new NeetSearchService($pdo);

$years = $service->getYears();
$year = (int)($_GET['year'] ?? ($years[0]['year'] ?? date('Y')));
$rank = $_GET['rank'] ?? '';
$threshold = $_GET['threshold'] ?? 500;
$quota = $_GET['quota'] ?? '';

$quotas = $year ? $service->getQuotas($year) : [];

$results = [];
$searched = false;

if ($_GET && $rank && $quota) {
    $searched = true;
    $results = $service->search($year, (int)$rank, (int)$threshold, $quota);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <div class="card">
        <div class="card-header pt-0 pb-0">
            <h2>NEET UG College Predictor</h2>
            <p>Search NEET UG counselling allotment by rank range and quota.</p>
        </div>

        <form method="get" class="search-form">
            <div class="form-group">
                <label>Year</label>
                <select name="year" required onchange="this.form.submit()">
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y['year']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Your Rank</label>
                <input type="number" name="rank" value="<?= htmlspecialchars($rank) ?>" required>
            </div>

            <div class="form-group">
                <label>Threshold ±</label>
                <input type="number" name="threshold" value="<?= htmlspecialchars($threshold) ?>" required>
            </div>

            <div class="form-group">
                <label>Quota</label>
                <select name="quota" required>
                    <option value="">Select Quota</option>
                    <?php foreach ($quotas as $q): ?>
                        <option value="<?= htmlspecialchars($q) ?>" <?= $quota === $q ? 'selected' : '' ?>>
                            <?= htmlspecialchars($q) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bottom-row">
                <button type="submit" style="margin-left: 0px;">Search NEET Colleges</button>
            </div>
        </form>
    </div>

    <?php if ($searched): ?>

        <div class="result-summary">
            <div>
                Showing <strong id="neetResultCount"><?= count($results) ?></strong>
                results for rank range:
                <strong><?= max(1, (int)$rank - (int)$threshold) ?></strong>
                to
                <strong><?= (int)$rank + (int)$threshold ?></strong>
            </div>

            <span class="mode-badge main">
                NEET UG Counselling
            </span>
        </div>

        <?php if (!$results): ?>

            <div class="no-result">
                No NEET UG allotment found for the selected criteria.
            </div>

        <?php else: ?>

            <div class="table-wrapper neet-table-wrapper">

                <div class="table-toolbar">

                    <div class="toolbar-left">
                        <h3>NEET UG Search Results</h3>

                        <span class="result-count">
                            <span id="neetVisibleCount"><?= count($results) ?></span>
                            Results Found
                        </span>
                    </div>

                    <div class="toolbar-right">

                        <button type="button"
                            id="neetToggleFilters"
                            class="filter-btn">
                            🔎 Show Filters
                        </button>

                        <button type="button"
                            id="neetResetFilters"
                            class="filter-btn reset-btn">
                            Reset Filters
                        </button>

                    </div>

                </div>

                <div id="neetFilterPanel"
                    class="filter-panel"
                    style="display:none;">

                    <div class="filter-row">

                        <div class="filter-item">
                            <label>Status</label>

                            <select id="neetFilterStatus">
                                <option value="">All Statuses</option>
                            </select>
                        </div>

                        <div class="filter-item institute">
                            <label>Institute</label>

                            <select id="neetFilterInstitute">
                                <option value="">All Institutes</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Course</label>

                            <select id="neetFilterCourse">
                                <option value="">All Courses</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Quota</label>

                            <select id="neetFilterQuota">
                                <option value="">All Quotas</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Category</label>

                            <select id="neetFilterCategory">
                                <option value="">All Categories</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Round</label>

                            <select id="neetFilterRound">
                                <option value="">All Rounds</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="neet-table-scroll">

                    <table id="neetResultTable">

                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Institute</th>
                                <th>Course</th>
                                <th>Quota</th>
                                <th>Category</th>
                                <th>Round</th>
                                <th>Option</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($results as $r): ?>

                                <?php
                                $categoryDisplay = trim(
                                    ($r['allotted_category'] ?? '') .
                                        (
                                            !empty($r['candidate_category'])
                                            ? ' / ' . $r['candidate_category']
                                            : ''
                                        )
                                );

                                if ($categoryDisplay === '') {
                                    $categoryDisplay = $r['final_category'] ?? '-';
                                }

                                $statusText = statusLabel($r);
                                ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars((string)$r['rank']) ?>
                                    </td>

                                    <td>
                                        <span class="neet-status-badge
                                    <?= !empty($r['is_upgraded'])
                                        ? 'upgraded'
                                        : (!empty($r['is_fresh_allotted'])
                                            ? 'fresh'
                                            : 'retained') ?>">

                                            <?= htmlspecialchars($statusText) ?>

                                        </span>
                                    </td>

                                    <td class="neet-institute-cell">

                                        <?php if (!empty($r['is_upgraded'])): ?>

                                            <div class="neet-seat-change">

                                                <div class="previous-seat">
                                                    <span class="seat-label">
                                                        Previous
                                                    </span>

                                                    <?= htmlspecialchars(
                                                        $r['previous_institute'] ?? '-'
                                                    ) ?>
                                                </div>

                                                <div class="seat-arrow">
                                                    ↓
                                                </div>

                                                <div class="current-seat">
                                                    <span class="seat-label">
                                                        Upgraded To
                                                    </span>

                                                    <?= htmlspecialchars(
                                                        $r['final_institute'] ?? '-'
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php else: ?>

                                            <?= htmlspecialchars(
                                                $r['final_institute'] ?? '-'
                                            ) ?>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $r['final_course'] ?? '-'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $r['final_quota'] ?? '-'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($categoryDisplay) ?>
                                    </td>

                                    <td>
                                        R<?= htmlspecialchars(
                                                (string)$r['round_no']
                                            ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            !empty($r['option_no'])
                                                ? (string)$r['option_no']
                                                : '-'
                                        ) ?>
                                    </td>

                                    <td class="neet-remarks-cell">
                                        <?= htmlspecialchars(
                                            $r['remarks'] ?? '-'
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    <?php endif; ?>
</div>

<?php
function statusLabel(array $r): string
{
    return match ($r['status']) {
        'FRESH_ALLOTTED_R1' => 'Seat allotted in Round 1',
        'FRESH_ALLOTTED' => 'Fresh allotted in R' . $r['round_no'],
        'UPGRADED' => 'Upgraded in R' . $r['round_no'],
        'NO_UPGRADATION' => 'Seat retained',
        'DID_NOT_OPT_UPGRADATION' => 'Retained; no upgradation opted',
        'DID_NOT_FILL_CHOICES' => 'Retained; fresh choices not filled',
        'NOT_ALLOTTED' => 'Not allotted',
        'SEAT_SURRENDERED' => 'Seat surrendered',
        default => 'Seat retained'
    };
}
?>
<link rel="stylesheet"
      href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>

<script src="<?= BASE_URL ?>/assets/js/neet-result-table.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>