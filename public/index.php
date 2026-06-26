<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/SearchService.php';

$service = new SearchService($pdo);

$years = $service->getYears();
$categories = $service->getCategories();

$results = [];
$searched = false;

$year = $_GET['year'] ?? '';
$rank = $_GET['rank'] ?? '';
$category = $_GET['category'] ?? '';
$threshold = $_GET['threshold'] ?? 500;

$isJeeMain = 1;
if ($_GET) {
    $isJeeMain = isset($_GET['is_jee_main']) ? 1 : 0;
}

if ($_GET) {
    $searched = true;

    $results = $service->search([
        'year' => (int)$year,
        'rank' => (int)$rank,
        'category' => $category,
        'threshold' => (int)$threshold,
        'is_jee_main' => $isJeeMain
    ]);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-container">

    <div class="card">
        <div class="card-header">
            <h2>JEE College Predictor</h2>
            <p>Search colleges based on opening and closing rank.</p>
        </div>

        <form method="get" id="searchForm" class="search-form">

            <div class="form-group">
                <label>Year</label>
                <select name="year" required>
                    <option value="">Select Year</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y['year'] ?>" <?= ($year == $y['year']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y['year']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Your Rank</label>
                <input type="number" name="rank" id="rank" value="<?= htmlspecialchars($rank) ?>" placeholder="Enter rank" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['code']) ?>" <?= ($category == $cat['code']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Threshold ±</label>
                <input type="number" name="threshold" id="threshold" value="<?= htmlspecialchars($threshold) ?>" required>
            </div>

            <div class="bottom-row">

                <div class="checkbox-row">
                    <label>
                        <input type="checkbox"
                            name="is_jee_main"
                            value="1"
                            <?= $isJeeMain ? 'checked' : '' ?>>
                        This is JEE Mains Rank
                    </label>

                    <small>
                        Checked : NIT / IIIT / GFTI
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Unchecked : IIT only
                    </small>
                </div>

                <button type="submit">
                    Search Colleges
                </button>

            </div>

        </form>
    </div>

    <?php if ($searched): ?>

        <div class="result-summary">
            <div>
                Showing <strong><?= count($results) ?></strong> results for rank range:
                <strong><?= max(1, (int)$rank - (int)$threshold) ?></strong>
                to
                <strong><?= (int)$rank + (int)$threshold ?></strong>
            </div>

            <span class="mode-badge <?= $isJeeMain ? 'main' : 'advanced' ?>">
                <?= $isJeeMain ? 'JEE Main Mode' : 'JEE Advanced Mode' ?>
            </span>
        </div>

        <?php if (!$results): ?>

            <div class="no-result">
                No colleges found for selected criteria.
            </div>

        <?php else: ?>

            <div class="table-wrapper">
                <div class="table-toolbar">

                    <div class="toolbar-left">
                        <h3>Search Results</h3>

                        <span class="result-count">
                            <?= count($results) ?> Colleges Found
                        </span>
                    </div>

                    <div class="toolbar-right">

                        <button type="button"
                            id="toggleFilters"
                            class="filter-btn">

                            🔎
                            Show Filters

                        </button>

                        <button type="button"
                            id="resetFilters"
                            class="filter-btn reset-btn">

                            Reset Filters

                        </button>

                    </div>

                </div>
                <div id="filterPanel" class="filter-panel" style="display:none;">

                    <!-- Row 1 -->
                    <div class="filter-row">
                        <div class="filter-item type">
                            <label>Institute Type</label>
                            <select id="filterType">
                                <option value="">All Types</option>
                            </select>
                        </div>

                        <div class="filter-item institute">
                            <label>Institute</label>
                            <select id="filterInstitute">
                                <option value="">All Institutes</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="filter-row">

                        <div class="filter-item branch">
                            <label>Branch</label>
                            <input type="text"
                                id="filterBranch"
                                placeholder="Computer Science, Mechanical...">
                        </div>

                        <div class="filter-item">
                            <label>Quota</label>
                            <select id="filterQuota">
                                <option value="">All Quota</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Gender</label>
                            <select id="filterGender">
                                <option value="">All Gender</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Round</label>
                            <select id="filterRound">
                                <option value="">All Rounds</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label>Preparatory</label>
                            <select id="filterPrep">
                                <option value="">All</option>
                            </select>
                        </div>

                    </div>

                </div>
                <table id="resultTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Institute</th>
                            <th>Branch</th>
                            <th>Quota</th>
                            <th>Category</th>
                            <th>Gender</th>
                            <th>Round</th>
                            <th>Cutoff</th>
                            <th>Prep</th>
                        </tr>

                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['institute_type'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['institute']) ?></td>
                                <td><?= htmlspecialchars($row['program_name']) ?></td>
                                <td><?= htmlspecialchars($row['quota']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><?= htmlspecialchars($row['round_name']) ?></td>
                                <td><?= htmlspecialchars($row['opening_rank_raw'] ?: $row['opening_rank']) ?> -> <?= htmlspecialchars($row['closing_rank_raw'] ?: $row['closing_rank']) ?></td>
                                <td><?= $row['is_preparatory'] ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/result-table.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>