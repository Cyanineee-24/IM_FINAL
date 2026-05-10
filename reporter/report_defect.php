<?php
// reporter/report_defect.php — mirrors reporter-report-a-defect-view.fxml + ReportDefectController.java
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth();
if ($user['role'] === 'TSG Personnel') redirect_to_dashboard('TSG Personnel');

$db = get_db();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workstation_id = $_POST['workstation_id'] ?? null;
    $component = $_POST['component'] ?? null;
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    
    // Validation
    if (!$workstation_id) $errors[] = "Please select a workstation.";
    if (!$component) $errors[] = "Please select a defective component.";
    if (empty($description)) $errors[] = "Please provide a description of the defect.";
    
    if (empty($errors)) {
        try {
            // Insert into DefectReport table
            $stmt = $db->prepare("
                INSERT INTO DefectReport (Status, Component, Description, DateFiled, WorkstationID)
                VALUES ('Pending', :component, :description, NOW(), :workstation_id)
            ");
            
            $stmt->execute([
                ':component' => $component,
                ':description' => $description,
                ':workstation_id' => $workstation_id
            ]);
            
            $report_id = $db->lastInsertId();
            
            // Connect the report to the student or faculty
            if ($user['role'] === 'Student') {
                $stmt = $db->prepare("INSERT INTO ReportStudent (ReportID, StudentID) VALUES (:report_id, :student_id)");
                $stmt->execute([':report_id' => $report_id, ':student_id' => $user['roleID']]);
            } elseif ($user['role'] === 'Faculty') {
                $stmt = $db->prepare("INSERT INTO ReportFaculty (ReportID, FacultyID) VALUES (:report_id, :faculty_id)");
                $stmt->execute([':report_id' => $report_id, ':faculty_id' => $user['roleID']]);
            }
            // Handle photo upload if present
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = ROOT . '/uploads/defect_photos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'defect_' . $report_id . '_' . time() . '.' . $file_ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                    // Update the report with photo path (you'll need to add PhotoPath column if you want this)
                    // $stmt = $db->prepare("UPDATE DefectReport SET PhotoPath = :photo WHERE ReportID = :report_id");
                    // $stmt->execute([':photo' => 'uploads/defect_photos/' . $filename, ':report_id' => $report_id]);
                }
            }
            
            $_SESSION['report_success'] = "Defect report #$report_id has been submitted successfully!";
            header('Location: report_defect.php');
            exit;
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

$success = $_SESSION['report_success'] ?? null;
unset($_SESSION['report_success']);
$error = $_SESSION['report_error'] ?? $error ?? null;
unset($_SESSION['report_error']);

// Load labs (mirrors cmbLabLocation)
$labs = $db->query('SELECT LabID, LabName FROM Lab ORDER BY LabName')->fetchAll();

// Load workstations for AJAX (initial page load returns all; JS filters by lab)
$workstations = $db->query('SELECT WorkstationID, WorkstationNo, LabID FROM Workstation ORDER BY LabID, WorkstationNo')->fetchAll();

layout_head('Report a Defect');
layout_sidebar($user, 'report');
?>

<div class="report-form-container">
    <div class="card report-defect-card">
        <div class="card-body" style="padding: 3rem;">
            <h1 class="text-center" style="font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 800; color: var(--maroon); letter-spacing: 1px; margin-bottom: 2rem;">REPORT A DEFECT</h1>
            <hr style="border: 0; border-top: 1px solid #EAEAEA; margin-bottom: 2rem;">

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

            <!-- Location confirmed banner (mirrors hboxLocationConfirmed) -->
            <div id="locationBanner" class="location-banner mb-3" style="display:none">
                <span style="color: #4CAF50; font-weight: bold; font-size: 16px;">✓</span> 
                <span>Location confirmed: <strong id="locationText" style="font-family: 'Montserrat', sans-serif; color: #2E7D32;"></strong></span>
            </div>

            <!-- FIXED: Added form tag with proper attributes -->
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Lab + Workstation dropdowns -->
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label" for="labSelect" style="font-size: 11px; font-weight: 700; color: #1a1a1a;">Laboratory Location<span style="color:red">*</span></label>
                        <div class="select-wrapper">
                            <select class="form-control" id="labSelect" required>
                                <option value="">Select Laboratory</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?= $lab['LabID'] ?>"><?= htmlspecialchars($lab['LabName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="workstationSelect" style="font-size: 11px; font-weight: 700; color: #1a1a1a;">PC/Workstation Number<span style="color:red">*</span></label>
                        <div class="select-wrapper">
                            <select class="form-control" id="workstationSelect" name="workstation_id" required disabled>
                                <option value="">Select Workstation</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Component cards -->
                <div class="form-group">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; color: #1a1a1a;">Select Defective Component<span style="color:red">*</span></label>
                    <div class="component-grid" id="componentGrid">
                        <?php
                        $components = [
                            'MOUSE'       => 'ic_mouse.png',
                            'KEYBOARD'    => 'ic_keyboard.png',
                            'DISPLAY'     => 'ic_display.png',
                            'RAM'         => 'ic_ram.png',
                            'SYSTEM UNIT' => 'ic_systemunit.png',
                            'AUDIO'       => 'ic_audio.png',
                        ];
                        foreach ($components as $name => $icon):
                        ?>
                        <div class="component-card" data-component="<?= htmlspecialchars($name) ?>"
                             onclick="selectComponent(this)">
                            <div class="comp-icon">
                                <img src="../assets/images/<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($name) ?>">
                            </div>
                            <div class="comp-label"><?= htmlspecialchars($name) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="component" id="componentInput" required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label class="form-label" for="description" style="font-size: 11px; font-weight: 700; color: #1a1a1a;">Description of Defect<span style="color:red">*</span></label>
                    <textarea class="form-control defect-textarea" id="description" name="description"
                              placeholder="Provide details about the issue..." rows="4" required></textarea>
                </div>

                <!-- Upload photo -->
                <div class="form-group">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; color: #1a1a1a;">Upload Photo (Optional)</label>
                    <label class="upload-zone" for="photoUpload">
                        <input type="file" id="photoUpload" name="photo" accept="image/*"
                               onchange="previewPhoto(this)">
                        <img src="../assets/images/ic_upload.png" alt="Upload" style="width: 32px; opacity: 0.4; margin-bottom: 0.5rem;">
                        <span id="uploadLabel" style="font-size: 11px; color: #999;">Click to upload image of the defect</span>
                    </label>
                    <img id="photoPreview" src="" alt="" style="display:none;margin-top:1rem;max-height:150px;border-radius:6px; margin: 1rem auto 0 auto;">
                </div>

                <div class="d-flex gap-2" style="justify-content:flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="font-size: 10px; font-weight: 700; padding: 0.5rem 1.5rem; letter-spacing: 0.5px; border-radius: 4px;" onclick="clearForm()">CLEAR</button>
                    <button type="submit" class="btn btn-maroon" style="font-size: 10px; font-weight: 700; padding: 0.5rem 1.5rem; letter-spacing: 0.5px; border-radius: 4px;">SUBMIT A REPORT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Workstation data from PHP
const workstations = <?= json_encode($workstations) ?>;

// Filter workstations by selected lab
document.getElementById('labSelect').addEventListener('change', function () {
    const labId = parseInt(this.value);
    const ws    = document.getElementById('workstationSelect');
    ws.innerHTML = '<option value="">Select Workstation</option>';
    ws.disabled  = !labId;

    if (labId) {
        workstations
            .filter(w => w.LabID == labId)
            .forEach(w => {
                const opt = document.createElement('option');
                opt.value       = w.WorkstationID;
                opt.textContent = w.WorkstationNo;
                ws.appendChild(opt);
            });
    }
    updateLocationBanner();
});

document.getElementById('workstationSelect').addEventListener('change', updateLocationBanner);

function updateLocationBanner() {
    const labSel = document.getElementById('labSelect');
    const wsSel  = document.getElementById('workstationSelect');
    const banner = document.getElementById('locationBanner');
    const locTxt = document.getElementById('locationText');

    if (labSel.value && wsSel.value) {
        const labName = labSel.options[labSel.selectedIndex].text;
        const wsName  = wsSel.options[wsSel.selectedIndex].text;
        locTxt.textContent = labName + ' — PC ' + wsName;
        banner.style.display = 'flex';
    } else {
        banner.style.display = 'none';
    }
}

let selectedCard = null;

function selectComponent(card) {
    if (selectedCard) selectedCard.classList.remove('selected');
    selectedCard = card;
    card.classList.add('selected');
    document.getElementById('componentInput').value = card.dataset.component;
}

function clearForm() {
    document.getElementById('labSelect').value = '';
    document.getElementById('workstationSelect').innerHTML = '<option value="">Select Workstation</option>';
    document.getElementById('workstationSelect').disabled = true;
    document.getElementById('locationBanner').style.display = 'none';
    document.getElementById('description').value = '';
    document.getElementById('componentInput').value = '';
    document.getElementById('photoPreview').style.display = 'none';
    document.getElementById('uploadLabel').textContent = 'Click to upload image of the defect';
    if (selectedCard) { selectedCard.classList.remove('selected'); selectedCard = null; }
}

function previewPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('photoPreview');
        img.src = e.target.result;
        img.style.display = 'block';
        document.getElementById('uploadLabel').textContent = file.name;
    };
    reader.readAsDataURL(file);
}
</script>

<?php layout_foot(); ?>