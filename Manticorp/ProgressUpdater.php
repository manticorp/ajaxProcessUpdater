<?php

namespace Manticorp;

/**
 * 
 */
class ProgressUpdater {
    /**
     * Options for implemenation
     * 
     *     string   lineBreak   What to use as the linebreak char, if need be
     *     string   filename    The output progress json file
     *     int      totalStages How many stages there will be
     *     boolean  autocalc    Whether to autocalculate certain values such as the
     *                          percentComplete, rate, etc. Setting this to false will
     *                          mean that you will provide these figures. Recommended to
     *                          keep this set to True
     * @var array of options
     */
    private $options = array(
        'lineBreak'   => "\n",
        'filename'    => null,
        'totalStages' => 1,
        'autocalc'    => True,
    );

    /**
     * This is where our stage will sit
     */
    public $stage = null;

    /**
     * Status that is written to the outfile, + stage params
     * @var array of status variables
     */
    private $status = array(
        'message'       => null,
        'totalStages'   => null,
        'remaining'     => 1,
        'error'         => False,
        'complete'      => False,
    );

    private $errorStatus = array(
        'message' => "An error has occurred", 
        "error"   => true,
        'info'    => "No further information available"
    );

    /**
     * Constructs the object using the options provided, if not null. If no filename is
     * given, a filename will be generated, defaulting to the __DIR__.DIRECTORY_SEPARATOR.'Manticorp-ProgressUpdater.json'
     * @param array $options An array of options for instantiating this object
     */
    public function __construct($options = null){
        if(is_array($options)){
            $this->options = array_merge($this->options, $options);
        }
        if($this->options['filename'] == null){
            $this->generateFilename();
        }
        $this->status['totalStages'] = $this->status['remaining'] = $this->options['totalStages'];
        $this->stage = new Stage($this);
        return $this;
    }

    /**
     * Writes $this->status to the outfile in JSON format
     * @param  array  $status (optional) The status to write.
     * @return object         this
     */
    public function publishStatus($status = null){
        if($status == null){
            $status = $this->getStatusArray();
        }
        try {
            @file_put_contents($this->options['filename'], json_encode($status));
        } Catch(\Exception $e){
            // We have to do this seperately, because we cannot write to the file!
            $status = array_merge($this->errorStatus, array(
                'message' => "Error writing to progress file :".$this->options['filename'], 
                "error"   => true,
                'info'    => $e->getMessage()
            ));
            echo json_encode($status);
            exit();
        }
        return $this;
    }

    /**
     * Gets the status & stage as an array.
     * @return array The status + stage as an array
     */
    public function getStatusArray(){
        $status = $this->status;
        $status['stage'] = $this->stage->toArray();
        return $status;
    }

    /**
     * Publishes an error status, exiting the program
     * @param  array  $status The error status
     * @return null           Doesn't return, exits php
     */
    public function doError($status, $exit = True) {
        if(is_string($status)){
            $m = $status;
            $status = $this->errorStatus;
            $status['message'] = $m;
        }
        publishStatus($status);
        if($exit){
            echo json_encode($status);
            exit();
        }
    }

    /**
     * Generates a filename for the output file
     * @return object this
     */
    public function generateFilename(){
        $this->options['filename'] = __DIR__.DIRECTORY_SEPARATOR.'Manticorp-ProgressUpdater.json';
        return $this;
    }

    /**
     * Sets an option for the $this->options array, checking if that option exists
     * @param string $name Name of the option
     * @param mixed  $val  What to set $this->options[$name] to
     */
    public function setOpt($name, $val = null){
        if(!isset($this->options[$name])){
            throw new \UnexpectedValueException(get_class()." has no option ".$name);
        }
        $this->options[$name] = $val;
        if(isset($this->status[$name])){
            $this->status[$name] = $val;
        }
        return $this;
    }

    /**
     * Alias for setOpt
     * @see setOpt
     */
    public function setOption($name, $val = null){
        return $this->setOpt($name, $val);
    }

    /**
     * Sets the options array to the argument
     * @param array $options The options array
     */ 
    public function setOpts($options = null){
        if($options != null && !(count($options)==0))
            $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Alias for setOpts
     * @see setOpts
     */
    public function setOptions($options = null){
        return $this->setOpts($options);
    }

    /**
     * Returns the options array
     * @return array The options
     */
    public function getOptions(){
        return $this->options;
    }

    /**
     * Gets an option by name
     * @param  string $name Name of the required option
     * @return mixed        The value of that option
     */
    public function getOption($name){
        if(!isset($this->options[$name])){
            throw new \UnexpectedValueException(get_class()." has no option ".$name);
        }
        return $this->options[$name];
    }

    /**
     * Gets the status array
     * @return array The status
     */ 
    public function getStatus(){
        return $this->status;
    }

    /**
     * Gets just the stage part of the status array
     * @return array The stage
     */
    public function getStage(){
        return $this->stage;
    }

    /**
     * Sets the progress to totally complete.
     * 
     * This sets the status to totally complete, that is,
     * it writes a final status message without a stage and
     * sets the complete flag to true and error to false,
     * publising the progress file and outputting the final
     * status to the browser.
     * @param  string $msg (optional) The final message
     * @return object      this
     */
    public function totallyComplete($msg = null){
        $msg = ($msg == null) ? 'Process Complete' : $msg;
        $status = array(
            'message'       => $msg,
            'error'         => false,
            'remaining'     => 0,
            'complete'      => true,
        ); 
        $this->status = array_merge($this->status, $status);
        $status = $this->getStatusArray();
        if(isset($status['stage'])) unset($status['stage']);
        echo json_encode($status);
        if(file_exists($this->options['filename']))
            unlink($this->options['filename']);
        return $this;
    }

    /**
     * Increments the stageNum and sets the next stage options
     * @param  array  $stage (optional) The stage options to use for the new stage
     * @return object        this
     */
    public function nextStage($stage = null){
        $this->resetStage();
        if($stage !== null){
            $this->stage->update($stage);
        }
        $this->status['remaining']--;
        $this->stage->stageNum++;
        $this->stage->startTime = $this->stage->curTime = microtime(true);
        return $this->publishStatus();
    }

    /**
     * Resets the stage to default values
     * @return object this
     */
    public function resetStage(){
        $this->stage->reset();
        return $this;
    }

    /**
     * Updates the stage with the options given
     * @param  array  $stage Array of stage options
     * @return object        this
     */
    public function updateStage($stage){
        $this->stage->update($stage);
        $this->stage->curTime = microtime(true);
        return $this->publishStatus();
    }

    /**
     * Increments the 'completeItems' counter for the current stage
     * @param  integer $n             Number to increment by, defaults to 1
     * @param  boolean $publishStatus Whether to publish the status file or not. Useful for 
     *                                when the process iterates fast, so you might only want
     *                                to update the status every x iterations to stop the
     *                                progress file from constantly being accessed and changed
     * @return object                 this
     */
    public function incrementStageItems($n = 1, $publishStatus = False){
        $this->stage->increment($n, $publishStatus);
        return $this;
    }

    /**
     * Magic call method, currently only used for magic setters and getters.
     * @param  string $method The method that was called
     * @param  array  $args   Array of arguments given
     * @return mixed          Either returns value for magic gets, or this for other stuff.
     */
    public function __call($method, $args){
        if(substr($method, 0, 3) == 'set') {
            if(substr($method, 3, 6) == 'Status') {
                $key = strtolower(substr($method, 9,1)) . substr($method, 10);
                $this->status[$key] = $args[0];
                return $this;
            } else if(substr($method, 3, 5) == 'Stage') {
                $key = strtolower(substr($method, 8,1)) . substr($method, 9);
                $this->stage->{$key} = $args[0];
                return $this;
            } else if(array_key_exists(strtolower(substr($method, 3,1)) . substr($method, 4), $this->options)){
                $this->options[strtolower(substr($method, 3,1)) . substr($method, 4)] = $args[0];
                return $this;
            } else {
                $var = strtolower(substr($method, 3,1)) . substr($method, 4);
                trigger_error("Property $var doesn't exist and cannot be set with a magic method.", E_USER_ERROR);
            }
        } else if (substr($method, 0, 3) == 'get') {
            if(substr($method, 3, 6) == 'Status') {
                $key = strtolower(substr($method, 9,1)) . substr($method, 10);
                return $this->status[$key];
            } else if(substr($method, 3, 5) == 'Stage') {
                $key = strtolower(substr($method, 8,1)) . substr($method, 9);
                return $this->stage->{$key};
            } else if(array_key_exists(strtolower(substr($method, 3,1)) . substr($method, 4), $this->options)){
                return $this->options[strtolower(substr($method, 3,1)) . substr($method, 4)];
            } else {
                $var = strtolower(substr($method, 3,1)) . substr($method, 4);
                trigger_error("Property $var doesn't exist and cannot be gotten with a magic method.", E_USER_ERROR);
            }
        } else {
            throw new \BadMethodCallException("Method: $method does not exists in ".get_class());
        }
    }
}


/**
 * 
 */
class Stage {
    private $status = array();

    private $default = array(
        'name'          => null,
        'message'       => null,
        'stageNum'      => 0,
        'totalItems'    => 1,
        'completeItems' => 0,
        'pcComplete'    => 0.0,
        'rate'          => null,
        'startTime'     => null,
        'curTime'       => null,
        'timeRemaining' => null,
    );

    private $pu = null;

    function __set($var, $val){
        if(array_key_exists($var, $this->status)){
            $this->status[$var] = $val;
        } else {
            trigger_error("Property $var doesn't exist and cannot be set.", E_USER_ERROR);
        }
        return $this;
    }

    function &__get($var){
        if(array_key_exists($var, $this->status)){
            return $this->status[$var];
        } else {
            trigger_error("Property $var doesn't exist and cannot be set.", E_USER_ERROR);
        }
    }

    function __construct($pu, $status = null){
        $this->pu = $pu;
        $this->status = $this->default;
        $this->reset(False);
        if($status !== null){
            $this->status = array_merge($this->default, $status);
        }
        $this->startTime = $this->curTime = microtime(true);
        return $this;
    }

    public function update($status = array()){
        $this->status = array_merge($this->status, $status);
        return $this;
    }

    public function toArray(){
        return $this->status;
    }

    public function increment($n = 1, $publishStatus = False){
        $this->completeItems = min(
            $this->completeItems + $n,
            $this->totalItems
        );

        $this->curTime = microtime(true);

        if($this->pu->getAutocalc()){
            if($this->totalItems > 0 && $this->totalItems !== null)
                $this->pcComplete = ($this->completeItems/$this->totalItems);
            else
                $this->pcComplete = 0;

            $this->rate = $this->completeItems / ($this->curTime - $this->startTime);

            if($this->getStageRate() > 0)
                $this->timeRemaining = (($this->totalItems- $this->completeItems) / $this->rate);
            else
                $this->timeRemaining = -1;
        }
        if($publishStatus){
            $this->pu->publishStatus();
        }
        return $this;
    }

    /**
     * Resets the stage to default values
     * @return object this
     */
    public function reset($incStageNum = False){
        if(!$incStageNum && isset($this->stageNum)){
            $sn = $this->stageNum;
        }
        $this->status = $this->default;
        if(!$incStageNum && isset($sn)){
            $this->stageNum = $sn;
        }
        return $this;
    }

    /**
     * Magic call method, currently only used for magic setters and getters.
     * @param  string $method The method that was called
     * @param  array  $args   Array of arguments given
     * @return mixed          Either returns value for magic gets, or this for other stuff.
     */
    public function __call($method, $args){
        if(substr($method, 0, 3) == 'set') {
            if(substr($method, 3, 6) == 'Status') {
                $key = strtolower(substr($method, 9,1)) . substr($method, 10);
                $this->status[$key] = $args[0];
                return $this;
            }
        } else if (substr($method, 0, 3) == 'get') {
            if(substr($method, 3, 6) == 'Status') {
                $key = strtolower(substr($method, 9,1)) . substr($method, 10);
                return $this->status[$key];
            }
        } else {
            throw new \BadMethodCallException("Method: $method does not exists in ".get_class());
        }
    }
}