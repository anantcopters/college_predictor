<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

require_once __DIR__ . '/../classes/RankParser.php';
require_once __DIR__ . '/../classes/CutoffRepository.php';
require_once __DIR__ . '/../classes/ExcelImporter.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $year = (int)($_POST['year'] ?? 0);

    if ($year <= 0) {
        $error = 'Please enter valid year.';
    } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['excel_file']['error'] ?? null;

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'Uploaded file exceeds server upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'Uploaded file exceeds form MAX_FILE_SIZE.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write uploaded file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
        ];

        $error = $uploadErrors[$errorCode] ?? 'Please upload a valid Excel file.';
    } else {

        $file = $_FILES['excel_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $error = 'Only .xlsx or .xls files are allowed.';
        } else {

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0775, true);
            }

            $safeName = 'jee_cutoff_' . $year . '_' . date('Ymd_His') . '.' . $ext;
            $destination = UPLOAD_DIR . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $error = 'Unable to save uploaded file.';
            } else {
                try {
                    $repo = new CutoffRepository($pdo);
                    $importer = new ExcelImporter($pdo, $repo);

                    $result = $importer->import($destination, $year);

                    $message = "Import completed successfully. Total: {$result['total']}, Saved: {$result['saved']}, Skipped: {$result['skipped']}";
                } catch (Exception $e) {
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
            <h2>Upload JEE Cutoff Excel</h2>
            <p>Upload JoSAA / JEE opening and closing rank Excel file for a selected year.</p>
        </div>

        <?php if ($message): ?>
            <div class="success-box">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="upload-form">

            <div class="form-group">
                <label>Year</label>
                <input type="number" name="year" value="2025" required>
            </div>

            <div class="form-group file-group">
                <label>Excel File<span class="field-help">(.xlsx, .xls)</span></label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required>
            </div>

            <button type="submit">Upload & Import</button>

        </form>

    </div>

    <div class="card upload-note">

        <div class="card-header">
            <h2>Expected Excel Format</h2>
            <p>
                Round sheets should be named <strong>R1</strong>, <strong>R2</strong>, etc.
                Institute type sheets should be named <strong>IIT</strong>, <strong>NIT</strong>,
                <strong>IIIT</strong>, or <strong>GFTI</strong>.
            </p>
        </div>

        <div class="table-wrapper simple-table">
            <table>
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Expected Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Institute</td>
                        <td>College / institute name</td>
                    </tr>
                    <tr>
                        <td>Academic Program Name</td>
                        <td>Branch / program name</td>
                    </tr>
                    <tr>
                        <td>Quota</td>
                        <td>AI, HS, OS etc.</td>
                    </tr>
                    <tr>
                        <td>Seat Type</td>
                        <td>OPEN, EWS, OBC-NCL, SC, ST etc.</td>
                    </tr>
                    <tr>
                        <td>Gender</td>
                        <td>Gender-Neutral / Female-only etc.</td>
                    </tr>
                    <tr>
                        <td>Opening Rank</td>
                        <td>Rank or rank with P suffix, example 1250P</td>
                    </tr>
                    <tr>
                        <td>Closing Rank</td>
                        <td>Rank or rank with P suffix, example 2300P</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>