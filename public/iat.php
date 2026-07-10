<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/IatRepository.php';

$repo = new IatRepository($pdo);

$years = $repo->getYears();
$categories = $repo->getCategories();

$year = $_GET['year'] ?? '';
$rank = $_GET['rank'] ?? '';
$category = $_GET['category'] ?? '';

$results = [];
$searched = false;
$error = '';

if ($_GET) {
    $searched = true;

    if ((int)$year <= 0 || (int)$rank <= 0 || $category === '') {
        $error = 'Please enter valid year, rank and category.';
    } else {
        $results = $repo->searchLatestRound(
            (int)$year,
            (int)$rank,
            cleanString($category)
        );
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-container">

    <div class="card">
        <div class="card-header pt-0 pb-0">
            <h2>IAT College Predictor</h2>
            <p>Search IISER admission chances based on IAT category-wise closing rank.</p>
        </div>

        <form method="get" class="search-form iat-search-form">

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
                <label>Your IAT Rank</label>
                <input type="number" name="rank" value="<?= htmlspecialchars($rank) ?>" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['code']) ?>" <?= $category === $cat['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['display_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="button-row full-button">
                <button type="submit">Search IISERs</button>
            </div>

        </form>
    </div>

    <?php if ($error): ?>
        <div class="no-result"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($searched && !$error): ?>

        <div class="result-summary">
            <div>
                Showing <strong><?= count($results) ?></strong> IISER options for rank
                <strong><?= htmlspecialchars($rank) ?></strong>
                based on latest available round.
            </div>

            <span class="mode-badge advanced">IAT Counselling</span>
        </div>

        <?php if (!$results): ?>

            <div class="no-result">No IAT cutoff data found for selected year, round and category.</div>

        <?php else: ?>

            <div class="table-wrapper">
                <div class="iat-result-wrapper">
                    <table class="iat-result-table">
                        <thead>
                            <tr>
                                <th>Institute</th>
                                <th>Category</th>
                                <th>Latest Round</th>
                                <th>Closing Rank</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['institute_name']) ?></td>
                                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                                    <td><?= htmlspecialchars($row['round_name']) ?></td>
                                    <td><?= htmlspecialchars($row['closing_rank'] ?? '-') ?></td>
                                    <td>
                                        <span class="status-badge <?= $row['status'] === 'Possible' ? 'possible' : 'not-eligible' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>