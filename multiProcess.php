<?php

include "Manticorp/ProgressUpdater.php";

set_time_limit(600);

$options = array(
    'filename' => __DIR__.DIRECTORY_SEPARATOR.'progress.json',
    'autoCalc' => True,
    'totalStages' => 4
);
$pu = new Manticorp\ProgressUpdater($options);


$stageOptions = array(
    'name' => 'Foo 1',
    'message' => 'Some Message',
    'totalItems' => 100,
);

$pu->nextStage($stageOptions);

for($i = 0; $i <= $stageOptions['totalItems']; $i++){
    usleep(50*1000);
    $pu->incrementStageItems(1, true);
}


$stageOptions = array(
    'name' => 'Foo 2',
    'message' => 'Another Message',
    'totalItems' => 50,
);

$pu->nextStage($stageOptions);

for($i = 0; $i <= $stageOptions['totalItems']; $i++){
    usleep(50*1000);
    $pu->incrementStageItems(1, true);
}


$stageOptions = array(
    'name' => 'Spam 1',
    'message' => 'This is eggs (check out the messages)',
    'totalItems' => 200,
);

$pu->nextStage($stageOptions);

for($i = 0; $i <= $stageOptions['totalItems']; $i++){
    usleep(50*1000);
    $pu->setStageMessage("Processing Item $i");
    $pu->incrementStageItems(1, true);
}


$stageOptions = array(
    'name' => 'Banana',
    'message' => 'Peel',
    'totalItems' => 150,
);

$pu->nextStage($stageOptions);

for($i = 0; $i <= $stageOptions['totalItems']; $i++){
    usleep(50*1000);
    $pu->incrementStageItems(1, true);
}

$pu->totallyComplete();