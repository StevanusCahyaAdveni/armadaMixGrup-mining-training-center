<?php
require_once 'config.php';
echo "--- employee_timesheets ---\n";
$res = mysqli_query($con, "DESCRIBE employee_timesheets");
while ($r = mysqli_fetch_assoc($res)) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}
echo "\n--- hauling_timesheets ---\n";
$res2 = mysqli_query($con, "DESCRIBE hauling_timesheets");
while ($r = mysqli_fetch_assoc($res2)) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}
