<?php
require_once 'room_list.php';

// Function to get hostel statistics
function getHostelStats($connection, $hostel_id) {
    $stats = [
        'total_rooms' => 0,
        'available_rooms' => 0,
        'total_beds' => 0,
        'available_beds' => 0
    ];
    
    // Get room statistics
    $query = "SELECT 
                COUNT(*) as total_rooms,
                SUM(CASE WHEN remain > 0 THEN 1 ELSE 0 END) as available_rooms,
                SUM(number_of_beds) as total_beds,
                SUM(remain) as available_beds
              FROM rooms 
              WHERE hostel_id = ? and status='published'";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $room_stats = $stmt->get_result()->fetch_assoc();
    
    $stats['total_rooms'] = $room_stats['total_rooms'];
    $stats['available_rooms'] = $room_stats['available_rooms'];
    $stats['total_beds'] = $room_stats['total_beds'];
    $stats['available_beds'] = $room_stats['available_beds'];
    
    return $stats;
}

// Function to get hostel attributes
function getHostelAttributes($connection, $hostel_id) {
    $query = "SELECT attribute_key, attribute_value 
              FROM hostel_attributes 
              WHERE hostel_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attributes = [];
    while ($row = $result->fetch_assoc()) {
        $attributes[$row['attribute_key']] = $row['attribute_value'];
    }
    
    return $attributes;
}

// Function to check if student is eligible for hostel
function isStudentEligibleForHostel($hostel, $student_data) {
    // Check gender match
    if (!empty($hostel['gender']) && $hostel['gender'] !== $student_data['gender']) {
        return false;
    }

    // Check year match
    if (!empty($hostel['year'])) {
        $hostel_years = explode(',', $hostel['year']);
        if (!in_array($student_data['yearofstudy'], $hostel_years)) {
            return false;
        }
    }

    // Check college match if hostel has college restriction
    if (!empty($hostel['college']) && !empty($student_data['college'])) {
        $hostel_colleges = explode(',', $hostel['college']);
        if (!in_array($student_data['college'], $hostel_colleges)) {
            return false;
        }
    }

    // Check school match if hostel has school restriction
    if (!empty($hostel['school']) && !empty($student_data['school'])) {
        $hostel_schools = explode(',', $hostel['school']);
        if (!in_array($student_data['school'], $hostel_schools)) {
            return false;
        }
    }

    // Check intake match if hostel has intake restriction
    if (!empty($hostel['intake']) && isset($student_data['intake']) && !empty($student_data['intake'])) {
        $hostel_intakes = explode(',', $hostel['intake']);
        if (!in_array($student_data['intake'], $hostel_intakes)) {
            return false;
        }
    }

    // Check disability match if hostel has disability restriction
    if (!empty($hostel['disability'])) {
        $hostel_disability = intval($hostel['disability']);
        $student_disability = isset($student_data['disability']) ? intval($student_data['disability']) : 0;
        
        // Students with disability (1) can only access hostels for students with disabilities (1)
        // Students without disability (0) can access hostels for students without disabilities (0)
        if ($hostel_disability !== $student_disability) {
            return false;
        }
    } else {
        // If hostel has no disability restriction, only students without disability can access
        // Students with disability need specifically designed hostels
        $student_disability = isset($student_data['disability']) ? intval($student_data['disability']) : 0;
        if ($student_disability === 1) {
            return false;
        }
    }

    return true;
}

// Helper function to get disability text
// Disability values: 1 = Students with disabilities, 0 = Students without disabilities
function getDisabilityText($disability_value) {
    $value = intval($disability_value);
    return $value === 1 ? 'Students with disabilities' : 'Students without disabilities';
}

function displayHostelCard($hostel, $connection) {
    // Get hostel statistics
    $stats = getHostelStats($connection, $hostel['id']);
    
    // Get student data from session with safe defaults
    $student_data = [
        'gender' => $_SESSION['student_gender'] ?? '',
        'yearofstudy' => $_SESSION['student_year'] ?? '',
        'college' => $_SESSION['student_college'] ?? '',
        'school' => $_SESSION['student_school'] ?? '',
        'intake' => $_SESSION['student_intake'] ?? '',
        'disability' => $_SESSION['student_disability'] ?? ''
    ];
    
    // Check eligibility and collect reasons
    $reasons = [];
    
    // Check hostel status
    if ($hostel['status'] !== 'published') {
        $reasons[] = "This hostel is currently not available for applications";
        echo '<div class="alert alert-info mb-3">';
        echo '<h6 class="alert-heading">' . htmlspecialchars($hostel['name']) . ' is not available because:</h6>';
        echo '<ul class="mb-0">';
        foreach ($reasons as $reason) {
            echo '<li>' . htmlspecialchars($reason) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        return;
    }
    
    // Check gender match
    if (!empty($hostel['gender']) && $hostel['gender'] !== $student_data['gender']) {
        $reasons[] = "This hostel is for " . ucfirst($hostel['gender']) . " students only";
    }
    
    // Check year match - handle comma-separated values
    if (!empty($hostel['year'])) {
        $allowed_years = array_map('trim', explode(',', $hostel['year']));
        if (!in_array($student_data['yearofstudy'], $allowed_years)) {
            $years_text = count($allowed_years) > 1 ? 
                "Years " . implode(", ", $allowed_years) : 
                "Year " . $allowed_years[0];
            $reasons[] = "This hostel is for " . $years_text . " students only";
        }
    }
    
    // Check college match - handle comma-separated values
    if (!empty($hostel['college'])) {
        $allowed_colleges = array_map('trim', explode(',', $hostel['college']));
        if (!in_array($student_data['college'], $allowed_colleges)) {
            $colleges_text = count($allowed_colleges) > 1 ? 
                implode(", ", $allowed_colleges) : 
                $allowed_colleges[0];
            $reasons[] = "This hostel is for " . $colleges_text . " students only";
        }
    }
    
    // Check school match - handle comma-separated values
    if (!empty($hostel['school'])) {
        $allowed_schools = array_map('trim', explode(',', $hostel['school']));
        if (!in_array($student_data['school'], $allowed_schools)) {
            $schools_text = count($allowed_schools) > 1 ? 
                implode(", ", $allowed_schools) : 
                $allowed_schools[0];
            $reasons[] = "This hostel is for " . $schools_text . " students only";
        }
    }
    
    // Check intake match - handle comma-separated values
    if (!empty($hostel['intake'])) {
        $allowed_intakes = array_map('trim', explode(',', $hostel['intake']));
        if (!in_array($student_data['intake'], $allowed_intakes)) {
            $intakes_text = count($allowed_intakes) > 1 ? 
                implode(", ", $allowed_intakes) : 
                $allowed_intakes[0];
            $reasons[] = "This hostel is for " . $intakes_text . " intake students only";
        }
    }
    
    // Check disability match
    if (!empty($hostel['disability'])) {
        $hostel_disability = intval($hostel['disability']);
        $student_disability = isset($student_data['disability']) ? intval($student_data['disability']) : 0;
        
        // Students with disability (1) can only access hostels for students with disabilities (1)
        // Students without disability (0) can access hostels for students without disabilities (0)
        if ($hostel_disability !== $student_disability) {
            $reasons[] = "This hostel is for " . getDisabilityText($hostel['disability']) . " only";
        }
    } else {
        // If hostel has no disability restriction, only students without disability can access
        // Students with disability need specifically designed hostels
        $student_disability = isset($student_data['disability']) ? intval($student_data['disability']) : 0;
        if ($student_disability === 1) {
            $reasons[] = "This hostel is not designed for students with disabilities. You need a hostel specifically designed for students with disabilities.";
        }
    }
    
    // Check room availability
    if (!hasAvailableRooms($connection, $hostel['id'])) {
        $reasons[] = "No rooms available in this hostel";
    }
    
    // If there are any reasons, display them
    if (!empty($reasons)) {
        echo '<div class="alert alert-info mb-3">';
        echo '<h6 class="alert-heading">' . htmlspecialchars($hostel['name']) . ' is not available because:</h6>';
        echo '<ul class="mb-0">';
        foreach ($reasons as $reason) {
            echo '<li>' . htmlspecialchars($reason) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        return;
    }
    
    // If we get here, the hostel is eligible and has available rooms
    ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-building me-2"></i><?php echo htmlspecialchars($hostel['name']); ?></span>
                <span class="badge bg-light text-primary"><i class="bi bi-door-open me-1"></i> <?php echo $stats['available_rooms']; ?> Rooms Available</span>
            </div>
            <div class="card-body">
                <!-- Professional Statistics Cards -->
                <div class="stats-container mb-4">
                    <div class="row g-3">
                    <div class="col-4">
                            <div class="stat-card pending-beds">
                                <div class="stat-icon">
                                    <i class="bi bi-building me-2"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $stats['available_rooms']; ?></div>
                                    <div class="stat-label">rooms</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card total-beds">
                            <div class="stat-icon">
                            <i class="bi bi-upload"></i>
    
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $stats['total_beds']; ?></div>
                                    <div class="stat-label">Beds</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card available-beds">
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $stats['available_beds']; ?></div>
                                    <div class="stat-label">Available</div>
                                </div>
                            </div>
                        </div>
                       
                    </div>
                </div>
                <div class="mb-3">
                    <div class="fw-bold mb-1"><i class="bi bi-people me-1"></i> Allowed Students</div>
                    <div class="row g-2">
                        <?php
                        $hasRestriction = false;
                        // Gender
                        if (!empty($hostel['gender'])) {
                            $hasRestriction = true;
                            echo '<div class="col-6">
                                    <div class="card h-100 border-primary shadow-sm" style="border-radius: 12px;">
                                        <div class="card-header py-1 px-2 bg-primary text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">
                                            Gender
                                        </div>
                                        <div class="card-body py-2 px-2 text-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                            <i class="me-1 allowed-pill">' 
                                                . htmlspecialchars($hostel['gender'] === 'F' ? 'Female' : 'Male') . 
                                            '</i>
                                        </div>
                                    </div>
                                </div>';
                        }
                        
                        // Year
                        if (!empty($hostel['year'])) {
                            $hasRestriction = true;
                            $years = array_map('trim', explode(',', $hostel['year']));
                            echo '<div class="col-6"><div class="card h-100 border-success shadow-sm" style="border-radius: 12px;">'
                                .'<div class="card-header py-1 px-2 bg-success text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">Year of Study</div>'
                                .'<div class="card-body py-2 px-2 text-center" style="background:; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
                                .'';
                            if (count($years) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($years as $year) {
                                    $safeYear = htmlspecialchars($year);
                                    echo '<li class="allowed-pill" title="'.$safeYear.'">Year ' . $safeYear . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<li class="allowed-pill">Year ' . htmlspecialchars($years[0]) . '</li>';
                            }
                            echo '</div></div></div>';
                        }
                        // School
                        if (!empty($hostel['school'])) {
                            $hasRestriction = true;
                            $schools = array_map('trim', explode(',', $hostel['school']));
                            echo '<div class="col-6"><div class="card h-100 border-info shadow-sm" style="border-radius: 12px;">'
                                .'<div class="card-header py-1 px-2 bg-info text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">School</div>'
                                .'<div class="card-body py-2 px-2 text-center" style="background:; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
                                .'';
                            if (count($schools) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($schools as $school) {
                                    $safeSchool = htmlspecialchars($school);
                                    echo '<li class="allowed-pill" title="'.$safeSchool.'">' . $safeSchool . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span style="font-size:0.97em;">' . htmlspecialchars($schools[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        // College
                        if (!empty($hostel['college'])) {
                            $hasRestriction = true;
                            $colleges = array_map('trim', explode(',', $hostel['college']));
                            echo '<div class="col-6"><div class="card h-100 border-warning shadow-sm" style="border-radius: 12px;">'
                                .'<div class="card-header py-1 px-2 bg-warning text-dark text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">College</div>'
                                .'<div class="card-body py-2 px-2 text-center" style="background:; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
                                .'';
                            if (count($colleges) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($colleges as $college) {
                                    $safeCollege = htmlspecialchars($college);
                                    echo '<li class="allowed-pill" title="'.$safeCollege.'">' . $safeCollege . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span style="font-size:0.97em;">' . htmlspecialchars($colleges[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        // Intake
                        if (!empty($hostel['intake'])) {
                            $hasRestriction = true;
                            $intakes = array_map('trim', explode(',', $hostel['intake']));
                            echo '<div class="col-6"><div class="card h-100 border-secondary shadow-sm" style="border-radius: 12px;">'
                                .'<div class="card-header py-1 px-2 bg-secondary text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">Intake</div>'
                                .'<div class="card-body py-2 px-2 text-center" style="background:; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
                                .'';
                            if (count($intakes) > 1) {
                                echo '<ul class="list-unstyled allowed-list">';
                                foreach ($intakes as $intake) {
                                    $safeIntake = htmlspecialchars($intake);
                                    echo '<li class="allowed-pill" title="'.$safeIntake.'">' . $safeIntake . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span style="font-size:0.97em;">' . htmlspecialchars($intakes[0]) . '</span>';
                            }
                            echo '</div></div></div>';
                        }
                        // Disability
                        if (!empty($hostel['disability'])) {
                            $hasRestriction = true;
                            echo '<div class="col-6"><div class="card h-100 border-dark shadow-sm" style="border-radius: 12px;">'
                                .'<div class="card-header py-1 px-2 bg-dark text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">Disability</div>'
                                .'<div class="card-body py-2 px-2 text-center" style="background:; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
                                .'<i class="bi bi-wheelchair me-1"></i>' . getDisabilityText($hostel['disability'])
                                .'</div></div></div>';
                        }
                        if (!$hasRestriction) {
                            echo '<div class="col-12"><div class="card h-100 border-success shadow-sm" style="border-radius: 12px;">'
                                .'<div class="card-header py-1 px-2 bg-success text-white text-center fw-semibold" style="border-top-left-radius: 12px; border-top-right-radius: 12px; font-size: 1rem;">All Students</div>'
                                .'<div class="card-body py-2 px-2 text-center text-success" style="background:; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
                                .'<i class="bi bi-check-circle me-1"></i>All students are allowed for this hostel'
                                .'</div></div></div>';
                        }
                        ?>
                    </div>
                </div>
                <button type="button" 
                        class="btn btn-primary w-100 view-rooms-btn"
                        data-bs-toggle="modal" 
                        data-bs-target="#roomsModal"
                        data-hostel-id="<?php echo $hostel['id']; ?>"
                        data-hostel-name="<?php echo htmlspecialchars($hostel['name']); ?>">
                    <i class="bi bi-door-open me-2"></i> View Available Rooms
                </button>
            </div>
        </div>
    </div>
    <?php
}
?>

<style>
.hostel-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

.hostel-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.hostel-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* Professional Statistics Cards Styling */
.stats-container {
    margin-bottom: 1.5rem;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #007bff, #0056b3);
}

.stat-card.total-beds::before {
    background: linear-gradient(90deg, #6c757d, #495057);
}

.stat-card.available-beds::before {
    background: linear-gradient(90deg, #28a745, #1e7e34);
}

.stat-card.pending-beds::before {
    background: linear-gradient(90deg, #ffc107, #e0a800);
}

.stat-icon {
    margin-bottom: 0.5rem;
}

.stat-icon i {
    font-size: 1.8rem;
    color: #6c757d;
}

.stat-card.total-beds .stat-icon i {
    color: #6c757d;
}

.stat-card.available-beds .stat-icon i {
    color: #28a745;
}

.stat-card.pending-beds .stat-icon i {
    color: #ffc107;
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hostel-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    text-align: center;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}

.stat-item i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.stat-item .stat-value {
    font-size: 1.25rem;
    font-weight: 500;
    color: #0d6efd;
}

.stat-item .stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.hostel-attributes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.attribute-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.attribute-item i {
    color: #0d6efd;
}

.btn-view-rooms {
    width: 100%;
    margin-top: 1rem;
}

.allowed-list {
  padding-left: 0;
  margin-bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}
.allowed-pill {
  font-size: 0.97em;
  background:rgb(233, 229, 229);
  border-radius: 3px;
  padding: 4px 18px;
  /* margin: 4px 0; */
  display: inline-block;
  font-weight: 500;
  letter-spacing: 0.5px;
  width: 100%;
  /* overflow: hidden; */
  text-overflow: ellipsis;
  /* white-space: nowrap; */
}
</style>

<?php
function renderHostelCard($hostel, $connection) {
    $stats = getHostelStats($connection, $hostel['id']);
    $attributes = getHostelAttributes($connection, $hostel['id']);
    ?>
    <div class="card hostel-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-building me-2"></i>
                <?php echo htmlspecialchars($hostel['hostel_name']); ?>
            </h5>
        </div>
        <div class="card-body">
            <!-- Hostel Statistics -->
            <div class="hostel-stats row g-3">
    <div class="col-6 stat-item text-center">
        <i class="bi bi-door-open text-primary fs-3"></i>
        <div class="stat-value fw-bold"><?php echo $stats['available_rooms']; ?>/<?php echo $stats['total_rooms']; ?></div>
        <div class="stat-label text-muted">Rooms</div>
    </div>

    <div class="col-6 stat-item text-center">
        <i class="bi bi-people text-success fs-3"></i>
        <div class="stat-value fw-bold"><?php echo $stats['available_beds']; ?>/<?php echo $stats['total_beds']; ?></div>
        <div class="stat-label text-muted">Beds</div>
    </div>

    <!-- Add more stat items here if needed -->
    <!-- Example:
    <div class="col-6 stat-item text-center">
        <i class="bi bi-journal-check text-info fs-3"></i>
        <div class="stat-value fw-bold">2</div>
        <div class="stat-label text-muted">Applications</div>
    </div>
    -->
</div>


            <!-- Hostel Attributes -->
            <div class="hostel-attributes">
                <?php if (isset($attributes['location'])): ?>
                <div class="attribute-item">
                    <i class="bi bi-geo-alt"></i>
                    <span><?php echo htmlspecialchars($attributes['location']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (isset($attributes['price'])): ?>
                <div class="attribute-item">
                    <i class="bi bi-currency-dollar"></i>
                    <span><?php echo htmlspecialchars($attributes['price']); ?> per semester</span>
                </div>
                <?php endif; ?>

                <?php if (isset($attributes['gender'])): ?>
                <div class="attribute-item">
                    <i class="bi bi-gender-ambiguous"></i>
                    <span><?php echo htmlspecialchars($attributes['gender']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (isset($attributes['year_of_study'])): ?>
                <div class="attribute-item">
                    <i class="bi bi-mortarboard"></i>
                    <span>Year <?php echo htmlspecialchars($attributes['year_of_study']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- View Rooms Button -->
            <button type="button" 
                    class="btn btn-primary btn-view-rooms" 
                    data-bs-toggle="modal" 
                    data-bs-target="#roomsModal"
                    data-hostel-id="<?php echo $hostel['id']; ?>"
                    data-hostel-name="<?php echo htmlspecialchars($hostel['hostel_name']); ?>">
                <i class="bi bi-door-open me-2"></i>
                View Available Rooms
            </button>
        </div>
    </div>
    <?php
}
?> 