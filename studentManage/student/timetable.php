<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Time Table';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$timetable = [
    'Monday' => [
        ['time' => '09:00-10:00', 'subject' => 'Mathematics', 'teacher' => 'John Teacher', 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'subject' => 'English', 'teacher' => 'Sarah Wilson', 'room' => 'Room 102'],
        ['time' => '11:00-11:15', 'subject' => 'Break', 'teacher' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'subject' => 'Science', 'teacher' => 'Michael Brown', 'room' => 'Lab 1'],
        ['time' => '12:15-01:15', 'subject' => 'History', 'teacher' => 'David Lee', 'room' => 'Room 103'],
    ],
    'Tuesday' => [
        ['time' => '09:00-10:00', 'subject' => 'Physics', 'teacher' => 'Michael Brown', 'room' => 'Lab 2'],
        ['time' => '10:00-11:00', 'subject' => 'Chemistry', 'teacher' => 'Emma Davis', 'room' => 'Lab 3'],
        ['time' => '11:00-11:15', 'subject' => 'Break', 'teacher' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'subject' => 'Mathematics', 'teacher' => 'John Teacher', 'room' => 'Room 101'],
        ['time' => '12:15-01:15', 'subject' => 'Computer Science', 'teacher' => 'Alex Johnson', 'room' => 'Computer Lab'],
    ],
    'Wednesday' => [
        ['time' => '09:00-10:00', 'subject' => 'English', 'teacher' => 'Sarah Wilson', 'room' => 'Room 102'],
        ['time' => '10:00-11:00', 'subject' => 'Geography', 'teacher' => 'Lisa Martin', 'room' => 'Room 104'],
        ['time' => '11:00-11:15', 'subject' => 'Break', 'teacher' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'subject' => 'Biology', 'teacher' => 'Emma Davis', 'room' => 'Lab 1'],
        ['time' => '12:15-01:15', 'subject' => 'Physical Education', 'teacher' => 'Tom Wilson', 'room' => 'Playground'],
    ],
    'Thursday' => [
        ['time' => '09:00-10:00', 'subject' => 'Mathematics', 'teacher' => 'John Teacher', 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'subject' => 'Hindi', 'teacher' => 'Priya Sharma', 'room' => 'Room 105'],
        ['time' => '11:00-11:15', 'subject' => 'Break', 'teacher' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'subject' => 'Science', 'teacher' => 'Michael Brown', 'room' => 'Lab 1'],
        ['time' => '12:15-01:15', 'subject' => 'Art & Craft', 'teacher' => 'Nina Patel', 'room' => 'Art Room'],
    ],
    'Friday' => [
        ['time' => '09:00-10:00', 'subject' => 'Economics', 'teacher' => 'Robert Smith', 'room' => 'Room 106'],
        ['time' => '10:00-11:00', 'subject' => 'English', 'teacher' => 'Sarah Wilson', 'room' => 'Room 102'],
        ['time' => '11:00-11:15', 'subject' => 'Break', 'teacher' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'subject' => 'Mathematics', 'teacher' => 'John Teacher', 'room' => 'Room 101'],
        ['time' => '12:15-01:15', 'subject' => 'Library Period', 'teacher' => 'Library Staff', 'room' => 'Library'],
    ],
    'Saturday' => [
        ['time' => '09:00-10:00', 'subject' => 'Mathematics', 'teacher' => 'John Teacher', 'room' => 'Room 101'],
        ['time' => '10:00-11:00', 'subject' => 'Science', 'teacher' => 'Michael Brown', 'room' => 'Lab 1'],
        ['time' => '11:00-11:15', 'subject' => 'Break', 'teacher' => '-', 'room' => '-'],
        ['time' => '11:15-12:15', 'subject' => 'Economics', 'teacher' => 'Robert Smith', 'room' => 'Room 106'],
        ['time' => '12:15-01:15', 'subject' => 'Self Study', 'teacher' => '', 'room' => 'Anywhere'],
    ],
    'Sunday' => [],
];

$currentDay = date('l');

include '../includes/header.php';
include 'sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-table me-2"></i>Time Table</h1>
        <div>
            <span class="badge bg-primary me-2">Class <?php echo $student['class'] . '-' . $student['section']; ?></span>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer me-2"></i>Print
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Today is <?php echo $currentDay; ?></strong> - Your current schedule is highlighted below
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Weekly Schedule</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="12%">Time</th>
                            <th width="17.6%">Monday</th>
                            <th width="17.6%">Tuesday</th>
                            <th width="17.6%">Wednesday</th>
                            <th width="17.6%">Thursday</th>
                            <th width="17.6%">Friday</th>
                            <th width="17.6%">Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $maxPeriods = 5;
                        for ($i = 0; $i < $maxPeriods; $i++):
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $timetable['Monday'][$i]['time']; ?></td>
                            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday','Saturday'] as $day): ?>
                                <?php 
                                $period = $timetable[$day][$i];
                                $isBreak = $period['subject'] === 'Break';
                                $isToday = $day === $currentDay;
                                ?>
                                <td class="<?php echo $isBreak ? 'table-warning' : ($isToday ? 'table-info' : ''); ?>">
                                    <?php if ($isBreak): ?>
                                        <div class="text-center">
                                            <i class="bi bi-cup-hot"></i>
                                            <strong>Break Time</strong>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <strong class="text-primary"><?php echo $period['subject']; ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?php echo $period['teacher']; ?>
                                                <br>
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

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-day me-2"></i>Today's Schedule</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($timetable[$currentDay] as $period): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1"><?php echo $period['subject']; ?></h6>
                                    <?php if ($period['subject'] !== 'Break'): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i><?php echo $period['teacher']; ?> |
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
                    <h6 class="mb-0"><i class="bi bi-book me-2"></i>Subject Teachers</h6>
                </div>
                <div class="card-body">
                    <?php
                    $subjects = [];
                    foreach ($timetable as $day => $periods) {
                        foreach ($periods as $period) {
                            if ($period['subject'] !== 'Break') {
                                $subjects[$period['subject']] = $period['teacher'];
                            }
                        }
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject => $teacher): ?>
                                <tr>
                                    <td><strong><?php echo $subject; ?></strong></td>
                                    <td><?php echo $teacher; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>