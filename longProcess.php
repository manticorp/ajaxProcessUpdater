<?php

include "Manticorp/ProgressUpdater.php";

set_time_limit(600);

$options = array(
    'filename' => __DIR__.DIRECTORY_SEPARATOR.'progress.json',
    'autoCalc' => True,
    'totalStages' => 1
);
$pu = new Manticorp\ProgressUpdater($options);


$stageOptions = array(
    'name' => 'This AJAX process takes a long time',
    'message' => 'But this will keep the user updated on it\'s actual progress!',
    'totalItems' => 100,
);

$pu->nextStage($stageOptions);

for($i = 0; $i <= 100; $i++){
    usleep(100*1000);
    $pu->incrementStageItems(1, True);
}

$pu->totallyComplete();
