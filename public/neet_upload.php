<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/NeetUploadService.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = (int)($_POST['year'] ?? 0);
    $roundNo = (int)($_POST['round_no'] ?? 0);

    if ($year <= 0 || $roundNo <= 0) {
        $error = 'Please enter valid year and round.';
    } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload valid Excel file.';
    } else {
        $file = $_FILES['excel_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $error = 'Only .xlsx or .xls allowed.';
        } else {
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0775, true);
            }

            $safeName = 'neet_ug_' . $year . '_r' . $roundNo . '_' . date('Ymd_His') . '.' . $ext;
            $destination = UPLOAD_DIR . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $error = 'Unable to save uploaded file.';
            } else {
                try {
                    $service = new NeetUploadService($pdo);
                    $result = $service->import($destination, $year, $roundNo, $file['name']);
                    $message = "NEET UG import completed. Saved: {$result['saved']}, Skipped: {$result['skipped']}";
                } catch (Throwable $e) {
                    $error = 'Import failed: ' . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <div class="card">
        <div class="card-header">
            <h2>Upload NEET UG Counselling Excel</h2>
            <p>Upload Excel against each year and round.</p>
        </div>

        <?php if ($message): ?>
            <div class="success-box"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label>Year</label>
                <input type="number" name="year" value="2025" required>
            </div>

            <div class="form-group">
                <label>Round</label>
                <select name="round_no" required>
                    <option value="1">Round 1</option>
                    <option value="2">Round 2</option>
                    <option value="3">Round 3</option>
                    <option value="4">Round 4</option>
                </select>
            </div>

            <div class="form-group file-group">
                <label>Excel File</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required>
            </div>

            <button type="submit">Upload NEET UG Data</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>