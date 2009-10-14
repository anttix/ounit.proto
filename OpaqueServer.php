<?php

require_once("config.php");
require_once("Engines.php");

class OpaqueServer {
   var $engines;

    public function OpaqueServer() {
        $this->engines["i200"] = new JUnitEngine($this);
        $this->engines["i202"] = new SeleniumEngine($this);
    }

    public function findEngine($questionID) {
        $arr = split("\.", $questionID);
        return $this->engines[$arr[0]];
    }
    
    /**
     * Called to obtain engine name, memory usage and active session count.
     * Must return an XML like this:
     * <engineinfo>
     *   <name>My Question Engine</name> <!-- Required -->
     *   <usedmemory>123 bytes or 45 KB or 67 MB</usedmemory> <!-- Optional -->
     *   <activesessions>9</activesessions> <!-- Optional -->
     * </engineinfo>
     *
     * @param void
     * @return String
     */
    public function getEngineInfo() {
        $name = "OpaqueUnit question engine";

	$xml  = '<engineinfo>';
	$xml .= '  <name>'.$name.'</name>'."\n";

        if(function_exists('memory_get_peak_usage')) {
            $xml .= '  <usedmemory>';
            $xml .= memory_get_peak_usage() . ' bytes';
            $xml .= '</usedmemory>'."\n";
        }

	// $xml .= '<activesessions></activesessions>';
	$xml .= '</engineinfo>';

        return $xml;
    }

    /**
     * Called to obtain question metadata and scoring information.
     * Must return an XML like this:
     * <questionmetadata>
     *   <scoring> 
     *     <marks>3</marks> <!-- Maximum score for this question -->
     *   </scoring> 
     *   <plainmode>yes</plainmode> <!-- plain mode supported? -->
     * </questionmetadata> 
     *
     * @param String $questionID
     * @param String $questionVersion
     * @param String $questionBaseURL
     * @return String
     */
    public function getQuestionMetadata($questionID, $questionVersion,
                                        $questionBaseURL) {

        $marks = $this->findEngine($questionID)->getMarks($questionID);
        $plainmode = 'yes';

        $xml  = '<questionmetadata>'."\n";
        $xml .= '  <scoring>'."\n";
        $xml .= '    <marks>'.$marks.'</marks>'."\n";
        $xml .= '  </scoring>'."\n";
        $xml .= '  <plainmode>'.$plainmode.'</plainmode>'."\n";
        $xml .= '</questionmetadata>';

        return $xml;
    }
    
    /**
      * Intialises a new question & returns the unique session identifier.
      *
      * @param String questionID
      * @param String questionVersion
      * @param String questionBaseURL
      * @param String[] initialParamNames
      * @param String[] initialParamValues
      * @param String[] cachedResources
      * @return StartReturn
      */
    public function start($questionID, $questionVersion, $questionBaseURL,
                          $initialParamNames, $initialParamValues,
                          $cachedResources) {

        $initialParams = array_combine($initialParamNames, $initialParamValues);
        $userId = $initialParams['userid'];
        $seed = $initialParams['randomseed']; 
        $passKey = $initialParams['passKey']; 

        $ret = new StartReturn();
        $ret->questionSession = $this->init_session();
        $ret->progressInfo = '0'; // Tries left?
        $ret->head = '';

	// We must use VLS provided seed to ensure repeatability.
        srand($this->trim_seed($seed));

        $this->set_session_variable("questionID", $questionID);

        $this->findEngine($questionID)->do_start($ret, $questionID);

        return $ret;
    }
    
    /**
     * Called when the question has been completed in VLS.
     *
     * @param String questionSession 
     * @return void
     */
    public function stop($questionSession) {
        $this->destroy_session($questionSession);
    }
    
    /**
     * Called to evaluate student answers.
     *
     * @param String questionSession 
     * @param String[] responseNames
     * @param String[] responseValues
     * @return Results
     */
    public function process($questionSession, $responseNames, $responseValues) {
        $response = array_combine($responseNames, $responseValues);

        /* foreach($response as $k => $v) {
            error_log("$k => $v");
        } */

        if(empty($questionSession)) {
            return;
        }

        $this->init_session($questionSession);
        
        $questionID = $this->get_session_variable("questionID");

        $ret = new ProcessReturn();

        $this->findEngine($questionID)->do_process($ret, $response,
                                                   $questionSession, $questionID);

        // $ret->progressInfo = '1'; // Tries left?
	// $ret->questionEnd = true;
	// $res->actionSummary = ;
        // $res->questionLine = ;
	// $res->answerLine = ;
        // $res->attempts = ;

        return $ret;
    }

    /**
     * Internal function for initializing question sessions.
     * uses PHPs built-in session support.
     *
     * @param String id
     * @return String
     */
    function init_session($id = Null) {
        ini_set('session.use_cookies', 0);
        if(!is_null($id)) {
            session_id($id);
        }
        session_name('OpaqueServer');
        session_start();

        return session_id();
    }

    /**
     * Internal function for destroying question sessions.
     *
     * @param String id
     * @return void
     */
    function destroy_session($id) {
        if(empty($id)) {
            return;
        }

        $this->init_session($id);
        $_SESSION = array();
        session_destroy();
    }

    /**
     * Internal function for reading session variables.
     *
     * @param String name
     * @return Mixed
     */
    function get_session_variable($name) {
        if(isset($_SESSION[$name]))
            return $_SESSION[$name];
        else
            return null;
    }

    /**
     * Internal function for writing session variables.
     *
     * @param String name
     * @param Mixed value
     * @return void
     */
    function set_session_variable($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * Internal function for trimming VLS provided random seeds
     * so they will fit into an integer.
     *
     * @param String seed
     * @return int
     */
    function trim_seed($seed) {
        $m = $seed / PHP_INT_MAX;
        $s = ((int)$m) * PHP_INT_MAX;

        return (int)($seed - $s);
    }
}
?>
