<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

// Handle cancellation before any HTML is sent, so the redirect remains valid.
if (isset($_GET['clear'])) {
    unset($_SESSION['pdf_questions'], $_SESSION['pdf_import_id'], $_SESSION['pdf_subject_id'], $_SESSION['pdf_chapter_id']);
    redirect('pdf_import.php');
}

define('PAGE_TITLE', 'PDF Question Import');

$subjects = db()->fetchAll("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$chapters = db()->fetchAll("SELECT * FROM chapters WHERE is_active=1 ORDER BY subject_id, sort_order");

$errors = [];
$extracted = [];
$pdfName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $val = validatePdfUpload($_FILES['pdf_file']);
        if (!$val['success']) {
            $errors[] = $val['message'];
        } else {
            $saved = saveUploadedFile($_FILES['pdf_file'], UPLOAD_PDFS);
            if ($saved['success']) {
                $pdfName = $saved['filename'];
                $originalName = $_FILES['pdf_file']['name'];
                $pdfPath = UPLOAD_PDFS . $pdfName;

                // Record import
                $importId = db()->insert(
                    "INSERT INTO pdf_imports (original_name, stored_name, file_size, status, subject_id, imported_by) VALUES (?,?,?,?,?,?)",
                    [$originalName, $pdfName, $_FILES['pdf_file']['size'], 'processed',
                     sanitizeInt($_POST['subject_id'] ?? 0) ?: null, $_SESSION['admin_id']]
                );

                // Extract text from PDF
                $extracted = extractQuestionsFromPdf($pdfPath);
                if (empty($extracted)) {
                    db()->execute("UPDATE pdf_imports SET status='failed' WHERE id=?", [$importId]);
                    $errors[] = 'No questions were found in this PDF. Please use a PDF with selectable text and standard numbered MCQs.';
                } else {
                    $_SESSION['pdf_import_id'] = $importId;
                    $_SESSION['pdf_questions'] = $extracted;
                    $_SESSION['pdf_subject_id'] = sanitizeInt($_POST['subject_id'] ?? 0);
                    $_SESSION['pdf_chapter_id'] = sanitizeInt($_POST['chapter_id'] ?? 0);
                }
            } else {
                $errors[] = $saved['message'];
            }
        }
    }
}

// Save reviewed questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_questions'])) {
    $qTexts  = $_POST['q_text']    ?? [];
    $qOptA   = $_POST['q_opt_a']   ?? [];
    $qOptB   = $_POST['q_opt_b']   ?? [];
    $qOptC   = $_POST['q_opt_c']   ?? [];
    $qOptD   = $_POST['q_opt_d']   ?? [];
    $qCorr   = $_POST['q_correct'] ?? [];
    $qMarks  = $_POST['q_marks']   ?? [];
    $qNeg    = $_POST['q_neg']     ?? [];
    $subId   = sanitizeInt($_POST['save_subject'] ?? 0) ?: null;
    $chId    = sanitizeInt($_POST['save_chapter'] ?? 0) ?: null;

    if (!$subId) {
        setFlash('error', 'Please select a category / subject before saving questions.');
        redirect('pdf_import.php');
    }
    if ($chId && !db()->fetchOne('SELECT id FROM chapters WHERE id=? AND subject_id=? AND is_active=1', [$chId, $subId])) {
        setFlash('error', 'Selected chapter does not belong to the chosen category.');
        redirect('pdf_import.php');
    }

    $saved = 0;
    foreach ($qTexts as $i => $text) {
        $text = trim($text);
        if (empty($text)) continue;
        $optA = trim($qOptA[$i] ?? ''); if (!$optA) $optA = '—';
        $optB = trim($qOptB[$i] ?? ''); if (!$optB) $optB = '—';
        $optC = trim($qOptC[$i] ?? ''); if (!$optC) $optC = '—';
        $optD = trim($qOptD[$i] ?? ''); if (!$optD) $optD = '—';
        $corr = strtoupper(trim($qCorr[$i] ?? 'A'));
        if (!in_array($corr, ['A','B','C','D'])) $corr = 'A';
        $marks = (float)($qMarks[$i] ?? 1);
        $neg   = (float)($qNeg[$i] ?? 0);

        db()->insert("INSERT INTO questions (subject_id,chapter_id,question_text,option_a,option_b,option_c,option_d,correct_option,marks,negative_marks,is_active,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,1,?)",
            [$subId, $chId, $text, $optA, $optB, $optC, $optD, $corr, $marks, $neg, $_SESSION['admin_id']]);
        $saved++;
    }
    // Update import record
    if (!empty($_SESSION['pdf_import_id'])) {
        db()->execute("UPDATE pdf_imports SET total_extracted=?, status='published' WHERE id=?",
            [$saved, $_SESSION['pdf_import_id']]);
    }
    unset($_SESSION['pdf_questions'], $_SESSION['pdf_import_id']);
    setFlash('success', "$saved questions saved to Question Bank!");
    redirect('questions.php');
}

/**
 * Basic PDF text extraction without external libraries.
 * Parses the raw PDF for text streams and attempts pattern matching.
 */
function decodePdfTextString(string $value): string {
    $value = preg_replace_callback('/\\\\([0-7]{1,3})/', fn(array $m): string => chr(octdec($m[1])), $value);

    return strtr($value, [
        '\\\\' => '\\\\', '\\(' => '(', '\\)' => ')', '\\n' => "\n",
        '\\r' => "\r", '\\t' => "\t", '\\b' => "\b", '\\f' => "\f",
    ]);
}

function decodeAscii85(string $value): string|false {
    $value = preg_replace('/\s+/', '', $value);
    $value = preg_replace('/^<~|~>$/', '', $value);
    $decoded = '';
    $group = [];

    foreach (str_split($value) as $char) {
        if ($char === 'z' && empty($group)) {
            $decoded .= "\0\0\0\0";
            continue;
        }
        $code = ord($char);
        if ($code < 33 || $code > 117) {
            continue;
        }
        $group[] = $code - 33;
        if (count($group) === 5) {
            $number = 0;
            foreach ($group as $digit) $number = ($number * 85) + $digit;
            $decoded .= chr(($number >> 24) & 255) . chr(($number >> 16) & 255)
                . chr(($number >> 8) & 255) . chr($number & 255);
            $group = [];
        }
    }
    if (!empty($group)) {
        $length = count($group);
        while (count($group) < 5) $group[] = 84;
        $number = 0;
        foreach ($group as $digit) $number = ($number * 85) + $digit;
        $bytes = chr(($number >> 24) & 255) . chr(($number >> 16) & 255)
            . chr(($number >> 8) & 255) . chr($number & 255);
        $decoded .= substr($bytes, 0, $length - 1);
    }

    return $decoded;
}

function extractPdfLiteralText(string $content): string {
    $text = '';
    if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $content, $matches)) {
        foreach ($matches[0] as $literal) {
            $literal = trim(decodePdfTextString(substr($literal, 1, -1)));
            if ($literal !== '') $text .= $literal . "\n";
        }
    }
    return $text;
}

function extractQuestionsFromPdf(string $pdfPath): array {
    $raw = @file_get_contents($pdfPath);
    if (!$raw) return [];

    // Extract text from PDF content streams
    $text = '';
    if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $raw, $matches)) {
        foreach ($matches[1] as $stream) {
            $stream = trim($stream);
            if (str_contains($stream, '~>')) {
                $ascii85 = decodeAscii85($stream);
                if ($ascii85 !== false && $ascii85 !== '') $stream = $ascii85;
            }

            // Support common PDF stream-compression variants.
            $decompressed = @gzuncompress($stream);
            if ($decompressed === false) $decompressed = @gzdecode($stream);
            if ($decompressed === false) $decompressed = @gzinflate($stream);
            if ($decompressed !== false) {
                $text .= $decompressed . "\n";
                $text .= extractPdfLiteralText($decompressed);
            } else {
                $text .= $stream . "\n";
                $text .= extractPdfLiteralText($stream);
            }
        }
    }

    // Also try direct text extraction from PDF objects
    if (preg_match_all('/\(([^)]{5,})\)/', $raw, $directMatches)) {
        foreach ($directMatches[1] as $m) {
            $decoded = preg_replace_callback('/\\\\([0-7]{3})/', function($e) {
                return chr(octdec($e[1]));
            }, $m);
            $text .= ' ' . $decoded;
        }
    }

    // Clean up text
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    // Preserve selectable checkmarks long enough for detectCorrectOption().
    $text = str_replace(['✓', '✔', '☑', '☒'], '[x]', $text);
    $text = preg_replace('/[^\x20-\x7E\n]/', ' ', $text);
    $text = preg_replace('/[ \t\x0B\f]+/', ' ', $text);
    $text = preg_replace('/ *\n */', "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $lines = array_map('trim', explode("\n", $text));
    $text = implode("\n", array_filter($lines));

    return parseQuestionsFromText($text);
}

function normaliseAnswerText(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim($value);
}

function detectCorrectOption(string $questionBody, array $options): string {
    // Formats such as: Answer: B, Correct option (C), or Ans - D.
    if (preg_match('/(?:correct\s*)?(?:answer|ans|option)\s*[:\-]?\s*(?:option\s*)?\(?([A-D])\)?\b/i', $questionBody, $match)) {
        return strtoupper($match[1]);
    }

    // Textual tick/check markers beside an option (for text-based PDFs).
    foreach ($options as $letter => $option) {
        if (preg_match('/(?:^|\s)(?:\[x\]|\[\*\]|\*|correct)(?:\s|$)/i', trim($option))) {
            return $letter;
        }
    }

    // Formats such as: Answer: Islamabad. Match the answer text to an option.
    if (preg_match('/(?:correct\s*)?(?:answer|ans)\s*[:\-]\s*(.+?)(?=\n|$)/i', $questionBody, $match)) {
        $answer = normaliseAnswerText($match[1]);
        if ($answer !== '') {
            foreach ($options as $letter => $option) {
                $candidate = normaliseAnswerText($option);
                if ($candidate !== '' && ($candidate === $answer || str_contains($candidate, $answer) || str_contains($answer, $candidate))) {
                    return $letter;
                }
            }
        }
    }

    return 'A';
}

function parseQuestionsFromText(string $text): array {
    $questions = [];

    // Pattern: Question number followed by text, then options A) B) C) D)
    $patterns = [
        // Pattern 1: Q1. or 1. question then A. B. C. D.
        '/(?:^|\n)\s*(?:Q\.?\s*)?(\d+)[\.\)]\s*(.+?)(?=\n\s*[Aa][\.\):]|\n\s*\(a\))/s',
        // Pattern 2: Questions numbered without options
        '/(?:^|\n)\s*(\d+)[\.\)]\s*([A-Z][^0-9]{10,200})(?=\n\s*\d+[\.\)]|\n\s*[A-Z][\.\)]|$)/s',
    ];

    // Normalize common MCQ formats
    // Split by question markers
    $blocks = preg_split('/(?=\n\s*(?:Q\.?\s*)?\d+[\.\)]\s+[A-Z])/i', $text);

    foreach ($blocks as $block) {
        $block = trim($block);
        if (strlen($block) < 20) continue;

        // Try to extract question number and text
        if (!preg_match('/^(?:Q\.?\s*)?(\d+)[\.\)]\s*(.+)/s', $block, $qm)) continue;

        $qNum  = (int)$qm[1];
        $qBody = trim($qm[2]);

        // Extract standard A) / A. / (A) option blocks.
        $options = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];
        foreach (array_keys($options) as $letter) {
            $pattern = '/(?:^|\n)\s*\(?' . $letter . '\)?[\.\):\-]\s*(.+?)(?=\n\s*\(?[A-D]\)?[\.\):\-]|$)/is';
            if (preg_match($pattern, $qBody, $optionMatch)) {
                $options[$letter] = trim(preg_replace('/\s+/', ' ', $optionMatch[1]));
            }
        }
        $optA = $options['A'];
        $optB = $options['B'];
        $optC = $options['C'];
        $optD = $options['D'];

        // Extract question text (before options)
        $qText = trim(preg_replace('/\n\s*\(?A\)?[\.\):\-].*/is', '', $qBody));
        $qText = trim(preg_replace('/\s+/', ' ', $qText));

        if (strlen($qText) < 5) continue;

        $questions[] = [
            'num'     => $qNum,
            'text'    => $qText,
            'opt_a'   => $optA ?: '',
            'opt_b'   => $optB ?: '',
            'opt_c'   => $optC ?: '',
            'opt_d'   => $optD ?: '',
            'correct' => detectCorrectOption($qBody, $options),
            'marks'   => 1,
            'neg'     => 0,
        ];
    }

    // If no structured questions found, try simple line-by-line
    if (empty($questions)) {
        $lines = array_values(array_filter(explode("\n", $text), fn($l) => strlen(trim($l)) > 15));
        $i = 0;
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            if (preg_match('/^(?:Q\.?\s*)?(\d+)[\.\)]\s*(.+)/', $line, $m)) {
                $qText = trim($m[2]);
                $opts  = ['', '', '', ''];
                $j = $i + 1;
                while ($j < count($lines) && $j < $i + 6) {
                    $ol = trim($lines[$j]);
                    if (preg_match('/^[Aa][\.\)]\s*(.+)/', $ol, $om)) $opts[0] = trim($om[1]);
                    elseif (preg_match('/^[Bb][\.\)]\s*(.+)/', $ol, $om)) $opts[1] = trim($om[1]);
                    elseif (preg_match('/^[Cc][\.\)]\s*(.+)/', $ol, $om)) $opts[2] = trim($om[1]);
                    elseif (preg_match('/^[Dd][\.\)]\s*(.+)/', $ol, $om)) $opts[3] = trim($om[1]);
                    $j++;
                }
                if (strlen($qText) >= 5) {
                    $questions[] = [
                        'num' => (int)$m[1], 'text' => $qText,
                        'opt_a' => $opts[0], 'opt_b' => $opts[1],
                        'opt_c' => $opts[2], 'opt_d' => $opts[3],
                        'correct' => 'A', 'marks' => 1, 'neg' => 0,
                    ];
                }
                $i = $j;
            } else {
                $i++;
            }
        }
    }

    return array_slice($questions, 0, 200);
}

$reviewQuestions = $_SESSION['pdf_questions'] ?? [];
include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="questions.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF Question Import</h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if (empty($reviewQuestions)): ?>
<!-- Upload Form -->
<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-upload me-2"></i>Upload PDF Question Paper</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <?= csrfField() ?>
                    <div class="upload-zone mb-3" onclick="document.getElementById('pdfFile').click()"
                         ondragover="event.preventDefault();this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')"
                         ondrop="handleDrop(event)">
                        <i class="fas fa-file-pdf"></i>
                        <p id="uploadLabel" class="mb-1 fw-semibold">Click or drag & drop PDF here</p>
                        <p class="text-muted small mb-0">Maximum 20MB · PDF files only</p>
                    </div>
                    <input type="file" name="pdf_file" id="pdfFile" style="display:none" accept="application/pdf,.pdf"
                           onchange="if (this.files.length) document.getElementById('uploadLabel').textContent=this.files[0].name">
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select">
                                <option value="0">— Select Subject —</option>
                                <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chapter (Optional)</label>
                            <select name="chapter_id" class="form-select">
                                <option value="0">— Select Chapter —</option>
                                <?php foreach ($chapters as $ch): ?>
                                <option value="<?= $ch['id'] ?>"><?= sanitize($ch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Upload & Extract Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Supported PDF Formats</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item py-2"><i class="fas fa-check text-success me-2"></i>Chapter-based MCQs</li>
                    <li class="list-group-item py-2"><i class="fas fa-check text-success me-2"></i>Numbered questions (1. 2. 3.)</li>
                    <li class="list-group-item py-2"><i class="fas fa-check text-success me-2"></i>Options with A. B. C. D.</li>
                    <li class="list-group-item py-2"><i class="fas fa-check text-success me-2"></i>Options with A) B) C) D)</li>
                    <li class="list-group-item py-2"><i class="fas fa-check text-success me-2"></i>Large multi-chapter papers</li>
                    <li class="list-group-item py-2"><i class="fas fa-info text-info me-2"></i>After extraction, you can edit every question, add options, and set correct answers</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Question Review -->
<div class="alert alert-info">
    <i class="fas fa-magic me-2"></i>
    <strong><?= count($reviewQuestions) ?> question(s) extracted.</strong>
    Review and edit each question, select correct answers, then save to Question Bank.
</div>

<form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="save_questions" value="1">

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto"><label class="form-label mb-0">Save to Subject:</label></div>
                <div class="col-sm-3">
                    <select name="save_subject" class="form-select form-select-sm">
                        <option value="0">— Select —</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($_SESSION['pdf_subject_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
                            <?= sanitize($s['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <select name="save_chapter" class="form-select form-select-sm">
                        <option value="0">— Chapter —</option>
                        <?php foreach ($chapters as $ch): ?>
                        <option value="<?= $ch['id'] ?>"><?= sanitize($ch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Save All to Question Bank
                    </button>
                    <a href="pdf_import.php?clear=1" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($reviewQuestions as $i => $q): ?>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <span class="question-number me-2"><?= $i+1 ?></span>
            <span class="fw-semibold">Question <?= $q['num'] ?></span>
            <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
                    onclick="this.closest('.card').remove()">
                <i class="fas fa-trash me-1"></i>Remove
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Question Text <span class="text-danger">*</span></label>
                    <textarea name="q_text[]" class="form-control" rows="2" required><?= sanitize($q['text']) ?></textarea>
                </div>
                <div class="col-md-6"><label class="form-label">Option A</label>
                    <input type="text" name="q_opt_a[]" class="form-control" value="<?= sanitize($q['opt_a']) ?>" placeholder="Option A">
                </div>
                <div class="col-md-6"><label class="form-label">Option B</label>
                    <input type="text" name="q_opt_b[]" class="form-control" value="<?= sanitize($q['opt_b']) ?>" placeholder="Option B">
                </div>
                <div class="col-md-6"><label class="form-label">Option C</label>
                    <input type="text" name="q_opt_c[]" class="form-control" value="<?= sanitize($q['opt_c']) ?>" placeholder="Option C">
                </div>
                <div class="col-md-6"><label class="form-label">Option D</label>
                    <input type="text" name="q_opt_d[]" class="form-control" value="<?= sanitize($q['opt_d']) ?>" placeholder="Option D">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                    <select name="q_correct[]" class="form-select">
                        <?php foreach (['A','B','C','D'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $q['correct'] === $opt ? 'selected' : '' ?>>Option <?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Marks</label>
                    <input type="number" name="q_marks[]" class="form-control" min="0" step="0.5" value="<?= $q['marks'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Neg. Marks</label>
                    <input type="number" name="q_neg[]" class="form-control" min="0" step="0.25" value="<?= $q['neg'] ?>">
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="sticky-bottom bg-white py-2 border-top mt-3">
        <button type="submit" class="btn btn-success btn-lg px-5">
            <i class="fas fa-save me-2"></i>Save All Questions to Bank
        </button>
    </div>
</form>
<?php endif; ?>

<script>
document.getElementById('uploadForm')?.addEventListener('submit', function (event) {
    const fileInput = document.getElementById('pdfFile');
    if (!fileInput.files || fileInput.files.length === 0) {
        event.preventDefault();
        document.getElementById('uploadLabel').textContent = 'Please select a PDF file first';
    }
});

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('pdfFile').files = e.dataTransfer.files;
        document.getElementById('uploadLabel').textContent = file.name;
    }
}
</script>
<?php include 'includes/footer.php'; ?>
