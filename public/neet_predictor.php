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
                <button type="submit">Search NEET Colleges</button>
            </div>
        </form>
    </div>

    <?php if ($searched): ?>
        <div class="result-summary">
            Showing <strong><?= count($results) ?></strong> results for rank range:
            <strong><?= max(1, (int)$rank - (int)$threshold) ?></strong>
            to
            <strong><?= (int)$rank + (int)$threshold ?></strong>
        </div>

        <?php if (!$results): ?>
            <div class="no-result">No NEET UG allotment found.</div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
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
                            <tr>
                                <td><?= htmlspecialchars($r['rank']) ?></td>
                                <td><?= htmlspecialchars(statusLabel($r)) ?></td>
                                <td>
                                    <?php if ($r['is_upgraded']): ?>
                                        <?= htmlspecialchars($r['previous_institute']) ?>
                                        →
                                    <?php endif; ?>
                                    <?= htmlspecialchars($r['final_institute']) ?>
                                </td>
                                <td><?= htmlspecialchars($r['final_course']) ?></td>
                                <td><?= htmlspecialchars($r['final_quota']) ?></td>
                                <td><?= htmlspecialchars($r['final_category'] ?? '-') ?></td>
                                <td>R<?= htmlspecialchars($r['round_no']) ?></td>
                                <td><?= htmlspecialchars($r['option_no'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['remarks']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>