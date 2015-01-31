# AJAX Process Updater

## Update the client on the progress of your long AJAX requests

If you make a long AJAX request on your client facing code, chances are a simple loader icon won't cut it, you want to give real time feedback to the browser on just what is going on in the code that is making it take so long.

Due to the nature of AJAX requests, there is no well defined way of sending data to the client before the entire request is repeated.

This library offers a free and easy solution to that problem using a simple progress file that is polled by anothe AJAX request.

## Installation

Clone or download this git repository into your project, then simply include the single library file:

```php
<?php
include 'Manticorp/ProgressUpdater.php';
```

## Usage

To use, instantiate a new ProgressUpdater:

```php
<?php
$pu = new Manticorp\ProgressUpdater();
```

There are several options you can pass to the constructor:

```php
<?php
/**
 * Options for implemenation
 * 
 *     string   lineBreak   What to use as the linebreak char, if need be.
 *     string   filename    The output JSON progress file.
 *     int      totalStages How many stages there will be in the process.
 *     boolean  autocalc    Whether to autocalculate certain values such as the
 *                          percentComplete, rate, etc. Setting this to false will
 *                          mean that you will provide these figures. Recommended to
 *                          keep this set to True, unless you're sure what you're doing
 * @var array of options
 */
$options = array(
    'lineBreak'   => "\n",
    'filename'    => null,
    'totalStages' => 1,
    'autocalc'    => True,
);

$pu = new Manticorp\ProgressUpdater($options);
```

Then, if possible, divide your long process into 'stages'. To demarcate a stage, precede it with:

```php
<?php
$pu->nextStage($stageOptions);
```

Where the stage options take the following variables in an array:

```php
<?php
/**
 * Status that is written to the outfile
 * @var array of stage variables
 */
$stageOptions = array(
    'name'          => 'A name for the stage',
    'message'       => 'A message to be passed to the user',
    'totalItems'    => 100, // The total amount of items processed in this stage
);
```

The 'totalItems' option should be used to indicate how many items are being iterated over in this stage. This could be 1 if your process isn't an iterative one.

If your process **is** iterative, then in each iteration (or each n iterations) you can use the following to update the progress file each iteration:

```php
<?php
/**
 * Increments the completed items counter for the current stage
 * @param  integer $n             Number to increment the completed items by, defaults to 1
 * @param  boolean $publishStatus Whether to publish the status file or not. Useful for 
 *                                when the process iterates fast, so you might only want
 *                                to update the status every x iterations to stop the
 *                                progress file from constantly being accessed and changed.
 *                                Defaults to False.
 * @return object                 this
 */
$pu->incrementStageItems($n, $publishStatus);

//---or---//

$pu->stage->increment($n, $publishStatus);
```

Then, once all stages are complete and all items processed, call the 'totallyComplete' method to send the complete status. Note that you can pass a message to this to be sent along with the final output:

```php
<?php
$msg = 'Totally Completed';
$pu->totallyComplete($msg);
```

Note that this method directly outputs one final message to the browser (NOT to the progress file) while will be sent to the client once the application has terminated. This may or may not be what you want, and this last method is entirely optional, depending on your implementation of reading the output JSON files and how your program is structured.

The final status is outputted as a JSON string, an example of which:

    {"message":"Process Complete","totalStages":1,"remaining":0,"error":false,"complete":true}

## Examples

[An example implentation of this library.](http://examples.hmp.is.it/ajaxProgressUpdater/index.html)

### Single, long running process

```php
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
```

### Multi stage, long running process

```php
<?php

include "Manticorp/ProgressUpdater.php";

set_time_limit(600);

$options = array(
    'filename' => __DIR__.DIRECTORY_SEPARATOR.'progress.json',
    'autoCalc' => True,
    'totalStages' => 4
);
$pu = new Manticorp\ProgressUpdater($options);

/*************** STAGE 1 ***************/

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

/*************** STAGE 2 ***************/

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

/*************** STAGE 3 ***************/

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

/*************** STAGE 4 ***************/

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
```

### Handling the output

```javascript
<div id="output"></div>
<script>

pollTime = 100;
window.progressInterval;

$.getJSON('longProcess.php',
    function(data){
        clearInterval(window.progressInterval);
        $('#output').html('Finished!');
    }
).error(function(data){
    clearInterval(window.progressInterval);
    $('#output').html('Error!');
});

window.progressInterval = setInterval(checkProgress, pollTime);

function checkProgress(){
    $.getJSON("progress.json", function(data){
        $('#output').html(Math.floor(data.stage.pcComplete*100) + '% Complete');
        return null;
    }).fail(function(){
        clearInterval(window.progressInterval);
    });
}
</script>
```

### Detailed handling example

A detailed handling example can be found in the 'index.html' file in the root of the project.

## Example JSON output

Note: all times are in seconds, rate is measured in items/second

```JSON
{  
    "message":null,
    "totalStages":1,
    "remaining":0,
    "error":false,
    "complete":false,
    "stage":{  
        "name":"This AJAX process takes a long time",
        "message":"But this will keep the user updated on it's actual progress!",
        "stageNum":1,
        "totalItems":100,
        "completeItems":35,
        "pcComplete":0.35,
        "rate":9.7360365086513,
        "startTime":1421609160.7145,
        "curTime":1421609164.3094,
        "timeRemaining":6.6762280464172
    }
}
```

# Contributing

Contributions are encouraged! Just submit a pull request.

Please follow the PSR2 coding standard:

[PSR2 Coding Standard](http://www.php-fig.org/psr/psr-2/)