<?php
require 'auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../classes/Subject.php';
require_once __DIR__ . '/../classes/Grade.php'; //Add include para sa subject name dropdown

$gradeModel   = new Grade();
$subjectModel = new Subject();
$success_message = '';

$enrolledSubjects = $subjectModel->getAll(1000, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $new_subject = trim($_POST['subject']);
            $new_prelim  = (int) $_POST['prelim'];
            $new_midterm = (int) $_POST['midterm'];
            $new_final   = (int) $_POST['final'];
            $new_grade   = round(($new_prelim + $new_midterm + $new_final) / 3);
            $new_remarks = $new_grade >= 75 ? 'Passed' : 'Failed';

            // Check for duplicatesss! HAHAHAHA strikta sori
            if ($gradeModel->existsBySubject($new_subject)) {
                $_SESSION['flash_error'] = '"' . $new_subject . '" already has a grade record.';
                header('Location: grades.php');
                exit;
            }

            $gradeModel->add([
                'subject' => $new_subject,
                'prelim' => $new_prelim,
                'midterm' => $new_midterm,
                'final' => $new_final,
                'grade' => $new_grade,
                'remarks' => $new_remarks,
                'status' => 'Active',
            ]);

            $_SESSION['flash'] = '"' . $new_subject . '" grade added successfully.';
        } elseif ($action === 'edit') {
            $edit_id = (int) $_POST['edit_id'];
            $edit_subject = trim($_POST['subject']);
            $edit_prelim = (int) $_POST['prelim'];
            $edit_midterm = (int) $_POST['midterm'];
            $edit_final = (int) $_POST['final'];
            $edit_grade = round(($edit_prelim + $edit_midterm + $edit_final) / 3);
            $edit_remarks = $edit_grade >= 75 ? 'Passed' : 'Failed';

            $gradeModel->update($edit_id, [
                'subject' => $edit_subject,
                'prelim' => $edit_prelim,
                'midterm' => $edit_midterm,
                'final' => $edit_final,
                'grade' => $edit_grade,
                'remarks' => $edit_remarks,
            ]);

            $_SESSION['flash'] = '"' . $edit_subject . '" grade updated successfully.';
        } elseif ($action === 'delete') {
            $delete_id = (int) $_POST['delete_id'];
            $gradeModel->delete($delete_id);
            $_SESSION['flash'] = 'Grade record deleted successfully.';
        }

        header('Location: grades.php');
        exit;
    }
}

if (isset($_SESSION['flash'])) {
    $success_message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$error_message = '';
if (isset($_SESSION['flash_error'])) {
    $error_message = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Pagination
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$grades = $gradeModel->getAll($perPage, $offset);
$allGrades = $gradeModel->getAll(1000, 0); //fetch sa tanan para ma detect kung taken na sya eme ang subject 
$takenSubjects = array_column($allGrades, 'subject');
$total_grades = $gradeModel->countAll();

// based sa subjects table, naka by id ang order
$subjectOrder = [];
foreach ($enrolledSubjects as $idx => $s) {
    $subjectOrder[$s['name']] = $idx;
}

// mirror the order of subjects 
usort($grades, function($a, $b) use ($subjectOrder) {
    $posA = $subjectOrder[$a['subject']] ?? PHP_INT_MAX;
    $posB = $subjectOrder[$b['subject']] ?? PHP_INT_MAX;
    return $posA - $posB;
});

$s = $gradeModel->stats();
$avg_grade = isset($s['avg_grade']) ? round($s['avg_grade'], 1) : 0;
$highest = isset($s['highest']) ? (int)$s['highest'] : 0;
$lowest = isset($s['lowest']) ? (int)$s['lowest'] : 0;

// PAGE TITLE
$active_page = 'grades';
$page_title  = 'My Grades';
$page_icon   = '<i class="bi bi-trophy-fill"></i>';

include 'header.php';
?>

<?php if (!empty($success_message)): ?>
<div class="alert-success"> <?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert-error"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Avg Grade</div>
        <div class="stat-value blue"><?= $avg_grade ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Highest</div>
        <div class="stat-value green"><?= $highest ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lowest</div>
        <div class="stat-value red"><?= $lowest ?></div>
    </div>
</div>

<div style="margin-bottom: 24px;">
    <button type="button" class="btn-add" onclick="openModal()">
        <i class="bi bi-plus-square"></i> Add Grade Record
    </button>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">Grade Report – 1st Semester</div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Subject</th>
                <th>Prelim</th>
                <th>Midterm</th>
                <th>Final Exam</th>
                <th>Final Grade</th>
                <th>Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($grades)): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">
                    No grade records yet. Use the form above to add one.
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($grades as $i => $g): ?>
            <tr>
                <td class="id-cell"><?= $offset + $i + 1 ?></td>
                <td><?= htmlspecialchars($g['subject']) ?></td>
                <td class="id-cell"><?= $g['prelim'] ?></td>
                <td class="id-cell"><?= $g['midterm'] ?></td>
                <td class="id-cell"><?= $g['final'] ?></td>
                <td>
                    <?php 
                    $fg = $g['grade'];
                    $gc = $fg >= 90 ? 'grade-high' : ($fg >= 85 ? 'grade-mid' : 'grade-low'); 
                    ?>
                    <span class="<?= $gc ?>"><?= $fg ?></span>
                </td>
                <td>
                    <span class="badge <?= $g['remarks'] === 'Passed' ? 'badge-active' : 'badge-probation' ?>">
                        <?= $g['remarks'] ?>
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn-action btn-edit" onclick="editGrade(<?= $g['id'] ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn-action btn-delete" onclick="deleteGrade(<?= $g['id'] ?>, '<?= htmlspecialchars($g['subject']) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <!-- Pagination / Limit to 5 -->
    <?php if ($total_grades > $perPage):
        $totalPages = (int) ceil($total_grades / $perPage);
    ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php if ($p === $page): ?>
                <span class="page-link current"><?= $p ?></span>
            <?php else: ?>
                <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="page-link" href="?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<form id="deleteForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="delete_id" value="">
</form>

<!-- Add Grade Modal -->
<div id="addGradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add Grade Record</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-hint">Final Grade is auto-computed: (Prelim + Midterm + Final Exam) ÷ 3</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">— Select an enrolled subject —</option>
                            <?php foreach ($enrolledSubjects as $s): ?>
                                <?php $taken = in_array($s['name'], $takenSubjects); ?>
                                <option value="<?= htmlspecialchars($s['name']) ?>" <?= $taken ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($s['code']) ?> – <?= htmlspecialchars($s['name']) ?>
                                    <?= $taken ? '(already graded)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($enrolledSubjects)): ?>
                            <small style="color:var(--text-muted); margin-top:4px; display:block;">
                                <i class="bi bi-info-circle"></i> Go to <a href="subjects.php">Subjects</a> to enroll subjects first.
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="prelim">Prelim Score</label>
                        <input type="number" id="prelim" name="prelim" min="0" max="100" placeholder="0 – 100" required>
                    </div>
                    <div class="form-group">
                        <label for="midterm">Midterm Score</label>
                        <input type="number" id="midterm" name="midterm" min="0" max="100" placeholder="0 – 100" required>
                    </div>
                    <div class="form-group">
                        <label for="final">Final Exam Score</label>
                        <input type="number" id="final" name="final" min="0" max="100" placeholder="0 – 100" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="bi bi-plus-square"></i> Add Grade Record</button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Grade Modal -->
<div id="editGradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Grade Record</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-hint">Final Grade is auto-computed: (Prelim + Midterm + Final Exam) ÷ 3</p>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="edit_subject">Subject</label>
                        <select id="edit_subject" name="subject" required>
                            <option value="">— Select an enrolled subject —</option>
                            <?php foreach ($enrolledSubjects as $s): ?>
                                <option value="<?= htmlspecialchars($s['name']) ?>">
                                    <?= htmlspecialchars($s['code']) ?> – <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_prelim">Prelim Score</label>
                        <input type="number" id="edit_prelim" name="prelim" min="0" max="100" placeholder="0 – 100" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_midterm">Midterm Score</label>
                        <input type="number" id="edit_midterm" name="midterm" min="0" max="100" placeholder="0 – 100" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_final">Final Exam Score</label>
                        <input type="number" id="edit_final" name="final" min="0" max="100" placeholder="0 – 100" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="bi bi-check-circle"></i> Update Grade Record</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('addGradeModal').classList.add('show');
}

function closeModal() {
    document.getElementById('addGradeModal').classList.remove('show');
    document.getElementById('editGradeModal').classList.remove('show');
}

function editGrade(id) {
    <?php foreach ($grades as $grade): ?>
        if (<?= $grade['id'] ?> === id) {
            document.getElementById('edit_id').value = '<?= $grade['id'] ?>';
            document.getElementById('edit_subject').value = '<?= htmlspecialchars($grade['subject']) ?>';
            document.getElementById('edit_prelim').value = '<?= $grade['prelim'] ?>';
            document.getElementById('edit_midterm').value = '<?= $grade['midterm'] ?>';
            document.getElementById('edit_final').value = '<?= $grade['final'] ?>';
        }
    <?php endforeach; ?>
    
    document.getElementById('editGradeModal').classList.add('show');
}

function deleteGrade(id, subject) {
    if (confirm('Are you sure you want to delete "' + subject + '" grade record? This action cannot be undone.')) {
        document.getElementById('deleteForm').elements['delete_id'].value = id;
        document.getElementById('deleteForm').submit();
    }
}


document.getElementById('addGradeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('editGradeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && (document.getElementById('addGradeModal').classList.contains('show') || document.getElementById('editGradeModal').classList.contains('show'))) {
        closeModal();
    }
});
</script>

<?php include 'footer.php'; ?>