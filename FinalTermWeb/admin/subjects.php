<?php
require 'auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes/Subject.php';
require_once __DIR__ . '/classes/Grade.php'; //hehe include para ma delete ang subject and its grade record

$gradeModel = new Grade();
$subjectModel = new Subject();
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $data = [
                'code' => strtoupper(trim($_POST['code'])),
                'name' => trim($_POST['name']),
                'teacher' => trim($_POST['teacher']),
                'units' => (int) $_POST['units'],
                'schedule' => trim($_POST['schedule']),
                'status' => 'Active',
            ];
            $subjectModel->add($data);
            $_SESSION['flash'] = '"' . $data['name'] . '" added successfully.';
        } elseif ($action === 'edit') {
            $edit_id = (int) $_POST['edit_id'];
            $data = [
                'code' => strtoupper(trim($_POST['code'])),
                'name' => trim($_POST['name']),
                'teacher' => trim($_POST['teacher']),
                'units' => (int) $_POST['units'],
                'schedule' => trim($_POST['schedule']),
            ];
            $subjectModel->update($edit_id, $data);
            $_SESSION['flash'] = '"' . $data['name'] . '" updated successfully.';
        } elseif ($action === 'delete') {
            $delete_id = (int) $_POST['delete_id'];

            //subject name before deleting 
            $subjectToDelete = $subjectModel->getById($delete_id);
            if ($subjectToDelete) {
                $gradeModel->deleteBySubject($subjectToDelete['name']);
            }

            $subjectModel->delete($delete_id);
            $_SESSION['flash'] = 'Subject and its grade record deleted successfully.';
        }

        header('Location: subjects.php');
        exit;
    }
}

if (isset($_SESSION['flash'])) {
    $success_message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Pagination
$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$subjects = $subjectModel->getAll($perPage, $offset);
$total_subjects = $subjectModel->countAll();
$stats = $subjectModel->stats();
$total_units = (int) ($stats['total_units'] ?? array_sum(array_column($subjects, 'units')));


// ----------------------------------------------------------
// PAGE TITLES
// ----------------------------------------------------------
$active_page = 'subjects';
$page_title  = 'Subjects';
$page_icon   = '<i class="bi bi-journal-text"></i>';

include 'header.php';
?>
<main class="content">
    <?php if (!empty($success_message)): ?>
        <div class="alert-success"> <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Subjects</div>
            <div class="stat-value blue"><?= $total_subjects ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Units</div>
            <div class="stat-value green"><?= $total_units ?></div>
        </div>
    </div>

    <div style="margin-bottom: 24px;">
        <button type="button" class="btn-add" onclick="openModal()">
            <i class="bi bi-plus-square"></i> Add Subject
        </button>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">Enrolled Subjects</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Teacher</th>
                    <th>Units</th>
                    <th>Schedule</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_subjects === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">
                        No subjects yet. Use the form above to add one.
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($subjects as $i => $subject): ?>
                <tr>
                    <td class="id-cell"><?= $offset + $i + 1 ?></td>
                    <td class="code-cell"><?= htmlspecialchars($subject['code']) ?></td>
                    <td><?= htmlspecialchars($subject['name']) ?></td>
                    <td><?= htmlspecialchars($subject['teacher']) ?></td>
                    <td class="id-cell"><?= $subject['units'] ?> units</td>
                    <td class="schedule-tag"><?= htmlspecialchars($subject['schedule']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn-action btn-edit" onclick="editSubject(<?= $subject['id'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn-action btn-delete" onclick="deleteSubject(<?= $subject['id'] ?>, '<?= htmlspecialchars($subject['name']) ?>')">
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
    <?php if ($total_subjects > $perPage):
        $totalPages = (int) ceil($total_subjects / $perPage);
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

    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Subject</h2>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="code">Subject Code</label>
                            <input type="text" id="code" name="code" placeholder="e.g. MATH102" required maxlength="10">
                        </div>
                        <div class="form-group">
                            <label for="name">Subject Name</label>
                            <input type="text" id="name" name="name" placeholder="e.g. Statistics and Probability" required>
                        </div>
                        <div class="form-group">
                            <label for="teacher">Teacher</label>
                            <input type="text" id="teacher" name="teacher" placeholder="e.g. Ms. Cruz" required>
                        </div>
                        <div class="form-group">
                            <label for="units">Units</label>
                            <select id="units" name="units" required>
                                <option value="">— Select —</option>
                                <option value="1">1 unit</option>
                                <option value="2">2 units</option>
                                <option value="3">3 units</option>
                                <option value="4">4 units</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="schedule">Schedule</label>
                            <input type="text" id="schedule" name="schedule" placeholder="e.g. MWF 7:30–8:30" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="bi bi-plus-square"></i> Add Subject</button>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- Edit Subject Modal -->
<div id="editSubjectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Subject</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_code">Subject Code</label>
                        <input type="text" id="edit_code" name="code" placeholder="e.g. MATH102" required maxlength="10">
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Subject Name</label>
                        <input type="text" id="edit_name" name="name" placeholder="e.g. Statistics and Probability" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_teacher">Teacher</label>
                        <input type="text" id="edit_teacher" name="teacher" placeholder="e.g. Ms. Cruz" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_units">Units</label>
                        <select id="edit_units" name="units" required>
                            <option value="1">1 unit</option>
                            <option value="2">2 units</option>
                            <option value="3">3 units</option>
                            <option value="4">4 units</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_schedule">Schedule</label>
                        <input type="text" id="edit_schedule" name="schedule" placeholder="e.g. MWF 7:30–8:30" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="bi bi-check-circle"></i> Update Subject</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('addSubjectModal').classList.add('show');
}

function closeModal() {
    document.getElementById('addSubjectModal').classList.remove('show');
    document.getElementById('editSubjectModal').classList.remove('show');
}

function editSubject(id) {
    <?php foreach ($subjects as $subject): ?>
        if (<?= $subject['id'] ?> === id) {
            document.getElementById('edit_id').value = '<?= $subject['id'] ?>';
            document.getElementById('edit_code').value = '<?= htmlspecialchars($subject['code']) ?>';
            document.getElementById('edit_name').value = '<?= htmlspecialchars($subject['name']) ?>';
            document.getElementById('edit_teacher').value = '<?= htmlspecialchars($subject['teacher']) ?>';
            document.getElementById('edit_units').value = '<?= $subject['units'] ?>';
            document.getElementById('edit_schedule').value = '<?= htmlspecialchars($subject['schedule']) ?>';
        }
    <?php endforeach; ?>
    
    document.getElementById('editSubjectModal').classList.add('show');
}

function deleteSubject(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
        document.getElementById('deleteForm').elements['delete_id'].value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('addSubjectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('editSubjectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && (document.getElementById('addSubjectModal').classList.contains('show') || document.getElementById('editSubjectModal').classList.contains('show'))) {
        closeModal();
    }
});
</script>

<?php include 'footer.php'; ?>


