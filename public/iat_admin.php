<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/IatRepository.php';

$repo = new IatRepository($pdo);

$institutes = $repo->getInstitutes();
$categories = $repo->getCategories();

$year = (int)($_POST['year'] ?? $_GET['year'] ?? date('Y'));
$roundNo = (int)($_POST['round_no'] ?? $_GET['round_no'] ?? 1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        foreach ($_POST['cutoff'] ?? [] as $categoryId => $instRows) {
            foreach ($instRows as $instituteId => $rankValue) {
                $rankValue = cleanString($rankValue);

                $closingRank = null;

                if ($rankValue !== '' && $rankValue !== '-') {
                    $closingRank = (int)$rankValue;
                }

                $repo->saveCutoff(
                    $year,
                    $roundNo,
                    (int)$instituteId,
                    (int)$categoryId,
                    $closingRank
                );
            }
        }

        $pdo->commit();
        $message = "IAT cutoff saved successfully for {$year}, Round {$roundNo}.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Unable to save IAT cutoff data.';
    }
}

$matrixRows = $repo->getMatrix($year, $roundNo);

$matrix = [];

foreach ($matrixRows as $row) {
    $catId = $row['category_id'];
    $instId = $row['institute_id'];

    if (!isset($matrix[$catId])) {
        $matrix[$catId] = [
            'category_name' => $row['category_name'],
            'category_code' => $row['category_code'],
            'values' => []
        ];
    }

    $matrix[$catId]['values'][$instId] = $row['closing_rank'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

<div class="page-container">

    <div class="card">
        <div class="card-header">
            <h2>IAT Cutoff Admin</h2>
            <p>Manually enter category-wise IISER closing ranks for each counselling round.</p>
        </div>

        <?php if ($message): ?>
            <div class="success-box"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="get" class="search-form iat-admin-filter">
            <div class="form-group">
                <label>Year</label>
                <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" required>
            </div>

            <div class="form-group">
                <label>Round</label>
                <input type="number" name="round_no" value="<?= htmlspecialchars($roundNo) ?>" min="1" required>
            </div>

            <div class="button-row full-button">
                <button type="submit">Load Matrix</button>
            </div>
        </form>
    </div>

    <form method="post">

        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="round_no" value="<?= htmlspecialchars($roundNo) ?>">

        <div class="card upload-note">
            <div class="table-toolbar">
                <div class="toolbar-left">
                    <h3>Year <?= htmlspecialchars($year) ?> - Round <?= htmlspecialchars($roundNo) ?></h3>
                    <span class="result-count">Manual Entry Matrix</span>
                </div>

                <div class="toolbar-right">
                    <button type="submit" class="filter-btn save-matrix-btn">Save Cutoffs</button>
                </div>
            </div>

            <div class="table-wrapper iat-matrix-wrapper">
                <table class="iat-matrix-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <?php foreach ($institutes as $inst): ?>
                                <th title="<?= htmlspecialchars($inst['name']) ?>">
                                    <?= htmlspecialchars($inst['name']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($cat['display_name']) ?></strong>
                                </td>

                                <?php foreach ($institutes as $inst): ?>
                                    <?php
                                    $value = $matrix[$cat['id']]['values'][$inst['id']] ?? '';
                                    ?>
                                    <td>
                                        <input
                                            type="number"
                                            name="cutoff[<?= (int)$cat['id'] ?>][<?= (int)$inst['id'] ?>]"
                                            value="<?= htmlspecialchars((string)$value) ?>"
                                            placeholder="-">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>



        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>