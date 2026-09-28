<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'My Schedule';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$schedule = [
    'Monday' => [
        ['time' => '09:00-10:00', 'class' => '10-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'class' => '10-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '11:00-11:15', 'class' => 'Break', 'subject' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'class' => '9-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
    ],
    'Tuesday' => [
        ['time' => '09:00-10:00', 'class' => '9-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'class' => '10-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '11:00-11:15', 'class' => 'Break', 'subject' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'class' => '10-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
    ],
    'Wednesday' => [
        ['time' => '09:00-10:00', 'class' => '10-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'class' => 'Free Period', 'subject' => '-', 'room' => '-'],
        ['time' => '11:00-11:15', 'class' => 'Break', 'subject' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'class' => '9-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
    ],
    'Thursday' => [
        ['time' => '09:00-10:00', 'class' => '10-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'class' => '9-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '11:00-11:15', 'class' => 'Break', 'subject' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'class' => '10-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
    ],
    'Friday' => [
        ['time' => '09:00-10:00', 'class' => '9-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'class' => '10-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '11:00-11:15', 'class' => 'Break', 'subject' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'class' => 'Free Period', 'subject' => '-', 'room' => '-'],
    ],
    'Saturday' => [
        ['time' => '09:00-10:00', 'class' => '9-A', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'class' => '10-B', 'subject' => $teacher['subject'], 'room' => 'Room 101'],
        ['time' => '11:00-11:15', 'class' => 'Break', 'subject' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'class' => 'Free Period', 'subject' => '-', 'room' => '-'],
    ],
];

$currentDay = date('l');

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-table me-2"></i>My Schedule</h1>
        <div>
            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($teacher['subject']); ?> Teacher</span>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer me-2"></i>Print
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Today is <?php echo $currentDay; ?></strong>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Weekly Teaching Schedule</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Time</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $maxPeriods = 4;
                        for ($i = 0; $i < $maxPeriods; $i++):
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $schedule['Monday'][$i]['time']; ?></td>
                            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day): ?>
                                <?php 
                                $period = $schedule[$day][$i];
                                $isBreak = $period['class'] === 'Break';
                                $isFree = $period['class'] === 'Free Period';
                                $isToday = $day === $currentDay;
                                ?>
                                <td class="<?php echo $isBreak ? 'table-warning' : ($isFree ? 'table-secondary' : ($isToday ? 'table-info' : '')); ?>">
                                    <?php if ($isBreak): ?>
                                        <div class="text-center">
                                            <i class="bi bi-cup-hot"></i>
                                            <strong>Break</strong>
                                        </div>
                                    <?php elseif ($isFree): ?>
                                        <div class="text-center text-muted">
                                            <i class="bi bi-dash-circle"></i>
                                            Free Period
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <strong class="text-primary">Class <?php echo $period['class']; ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-door-open"></i> <?php echo $period['room']; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-day me-2"></i>Today's Classes</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($schedule[$currentDay] as $period): ?> <!-- line 144 -->
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1">
                                        <?php if ($period['class'] === 'Break'): ?>
                                            <i class="bi bi-cup-hot me-1"></i>Break Time
                                        <?php elseif ($period['class'] === 'Free Period'): ?>
                                            <i class="bi bi-dash-circle me-1"></i>Free Period
                                        <?php else: ?>
                                            Class <?php echo $period['class']; ?>
                                        <?php endif; ?>
                                    </h6>
                                    <?php if ($period['class'] !== 'Break' && $period['class'] !== 'Free Period'): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-door-open me-1"></i><?php echo $period['room']; ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-primary"><?php echo $period['time']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Quick Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Total Classes per Week</small>
                        <h5>
                            <?php
                            $total_classes = 0;
                            foreach ($schedule as $day => $periods) {
                                foreach ($periods as $period) {
                                    if ($period['class'] !== 'Break' && $period['class'] !== 'Free Period') {
                                        $total_classes++;
                                    }
                                }
                            }
                            echo $total_classes;
                            ?>
                        </h5>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Subject Teaching</small>
                        <h6><?php echo htmlspecialchars($teacher['subject']); ?></h6>
                    </div>
                    
                    <div>
                        <small class="text-muted">Classes Assigned</small>
                        <h6>10-A, 10-B, 9-A, 9-B</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>