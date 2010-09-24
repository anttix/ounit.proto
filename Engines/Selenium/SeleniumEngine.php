<?php

class SeleniumEngine {
    var $parent;
    var $lp;

    public function SeleniumEngine(& $parent) {
        $this->parent = &$parent;
    }

    public function getMarks($questionID) {
        return 3;
    }

    public function get_session_variable($var) {
        return $this->parent->get_session_variable($var);
    }

    public function set_session_variable($var, $val) {
        return $this->parent->set_session_variable($var, $val);
    }

    public function do_start(& $ret, $questionID) {
        $desc = $this->load_desc($questionID);
//        $code = $this->get_session_variable("code");
//        if(is_null($code)) $code = $this->load_code($questionID);
//        $ret->XHTML = $this->question_xhtml($desc, $code);
        $ret->XHTML = $this->question_xhtml($desc, $questionID);
        $ret->resources = array($this->load_zipfile($questionID));
        $this->set_session_variable("ncompile", 3);
    }

    public function do_process(& $ret, & $response,
                               $questionSession, $questionID) {
        $score      = $this->get_session_variable("score");
        $output     = $this->get_session_variable("output");
        $code       = $this->get_session_variable("code");
        $codechanged = false;

        // $ret->progressInfo = '1'; // Tries left?


        $desc = $this->load_desc($questionID);

        /* FIXME: It's debateable if we should remember the code when
                  user didn't explicitly save it.
         */
/*
	if(isset($response["code"]) && trim($response["code"]) != "") {
            if($code !== $response["code"])
                $codechanged = true;
            $code = $response["code"];
            $this->set_session_variable("code", $code);
        }
*/

	/* $response["event"] is moodle specific indicator.
           7 means submit 2 means save w/o submit */
        if(isset($response["grade"]) ||
           (isset($response["event"]) && $response["event"] == 7)) {
            $grade = true;
        } else {
            $grade = false;
        }

        $nc = $this->get_session_variable("ncompile");

        /* Only compile if instructed, code changed or our
           poor fool tried to grade w/o ever trying to compile */
	if(isset($response["test"]) /* || $codechanged == true */ ||
           ($score == null && $grade == true)) {

            //error_log("Mida, mida? $questionSession");
            //error_log("test = " . $response["test"]);

            // FIXME: Code change processing. Where it should go?
	    if(isset($response["code"]) && trim($response["code"]) != "" &&
                $code !== $response["code"]) {

                $code = $response["code"];
                $this->set_session_variable("code", $code);

                /* Subtract the number of times we allow to compile */
                $this->set_session_variable("ncompile", $nc-1);

                $to = $this->test($code, $questionSession, $questionID);
                $failed = $to[2];
                $this->cleanup($questionSession);
                $score = 3 - $failed;

                $output = $this->output_xhtml($to);
                $this->set_session_variable("score", $score);
                $this->set_session_variable("output", $output);
            }
        } 

        if($grade == true) {
            $ret->XHTML = "<!-- Session: $questionSession -->";
            $ret->XHTML .= $this->results_xhtml($desc, $code, $output);
	    $res = new Results();
            $res->scores[] = new Score(NULL, $score);
	    $ret->results = $res;
        } else {
            $ret->XHTML = $this->question_xhtml($desc, $questionID, $output);
        }
    }

    /**
     * Internal function for constructing student entry HTML
     *
     * @param String descr
     * @param String codetpl
     * @param String results
     * @return String
     */
    function question_xhtml($descr, $questionID, $results = '') {
        $html = file_get_contents(dirname(__FILE__) . '/question.thtml');
        $html = str_replace('%%DESCRIPTION%%', $descr, $html);
        $html = str_replace('%%RESULTS%%', $results, $html);
        $html = str_replace('%%QF%%', $questionID . ".zip", $html);

	/* Utter a warning message when displaying results */
	if($results) {
            $html .= '<div style="background-color: red;">';
            $html .= 'The score is <strong>not</strong> saved. ' .
                     'You MUST click on "Grade" to make your answer final.';
            $html .= '</div>';
	}

        return $html;
    }

    /**
     * Internal function for constructing final results HTML
     *
     * @param String descr
     * @param String codetpl
     * @param String results
     * @return String
     */
    function results_xhtml($descr, $code, $results = '') {
        $html = file_get_contents(dirname(__FILE__) . '/results.thtml');
        $html = str_replace('%%DESCRIPTION%%', $descr, $html);
        $html = str_replace('%%CODE%%',
                            htmlspecialchars($code), $html);
        $html = str_replace('%%RESULTS%%', $results, $html);

        return $html;
    }
    /**
     * Internal function for constructing compile results
     *
     * @param String[] co
     * @param String[] to
     * @return String
     */
    function output_xhtml($to) {
        $html = '';
        $nc = $this->get_session_variable("ncompile");
        $score = 3 - $to[2];

        if($score < 3 or $to[0] != 0) {
            $html .= '<div style="color: red;font-size:150%;">';
            $html .= 'Some tests FAILED</div>';
        }

        $html .= "Score: " . $score . " / 3";
	$html .= "<div>NB! You can retry this test $nc times</div>";

        $html .= '<pre class="unittestoutput">';
        $html .= $to[1];
        $html .= '</pre>';

        return $html;
    }
    /**
     * Internal function for loading question description
     *
     * @param String questionID
     * @return String
     */
    function load_desc($questionID) {
        return file_get_contents('questions/'.$questionID.'/description.xhtml');
    }
    /**
     * Internal function for loading code template
     *
     * @param String questionID
     * @return String
     */
    function load_zipfile($questionID) {
        $r = new Resource();
        $r->content = file_get_contents('questions/'.$questionID.'/files.zip');
        $r->encoding = "binary";
        $r->filename = $questionID . ".zip";
        $r->mimeType = "application/zip";

        return $r;
    }

    /**
     * Internal function for testing submitted code
     *
     * @param String code
     * @param String sessionID
     * @return String[]
     */
    function test($code, $sessionID, $questionID) {

        $limit="ulimit -t 70";
        $dir = getcwd();
        $tmpdir = 'tmp/' . $sessionID;
        $qdir = $dir.'/questions/'.$questionID;
        if(!is_dir($tmpdir)) mkdir($tmpdir);
	chdir($tmpdir);
        system("chmod g+w .");

	// Uncompress files
        $output = array();
        $cmd = "unzip $qdir/files.zip";
        exec($cmd, $output, $returncode);

	// Put student answer in place
        $fn = trim(file_get_contents("$qdir/outfile"));
        file_put_contents($fn, $code);

        // Lock engine
	$lf = "$dir/tmp/selenium.lock." . (rand()%5);
	// $lf = "$dir/tmp/selenium.lock";
	$this->lock_engine($lf);

        // Set URL
        $url = ANSURL . "/" . $sessionID . "/";

/*
        putenv("OUNIT_URL=" . ANSURL . "/" . $sessionID . "/");
	// Execute tests
        $output = array();
        $cmd = JAVA.' -cp "'.JUNIT_JAR.':'.SELENIUM_JAR.':'.$qdir.':." '.
               ' org.junit.runner.JUnitCore AnswerTest 2>&1';
        exec($limit . ';' . $cmd, $output, $returncode);

	putenv("OUNIT_URL");
*/

	$output = array();
	$cmd = SSRV . " \"$url\" answertest.html out.html";
        exec($limit . ';' . $cmd, $output, $returncode);

        $this->unlock_engine();

        $ret = array($returncode, "", 3);

        // Filter out crap (we only need stuff between body tags)
        $ret[1] = file_get_contents("out.html");
        $ret[1] = ereg_replace("^.*<body>|</body>.*$", "", $ret[1]);

/*
        foreach($output as $o) {
            // Filter out stack trace lines for clarity
            if(!preg_match("/^[ 	]*at/", $o))
                $ret[1] .= $o . "\n";
        }
*/

        if($returncode == 0) {
	    $ret[2] = 0;
        } else {
            // Count failures
            if(preg_match("/numTestFailures:<\/td>\n<td>(\d+)<\/td>/",
               $ret[1], $ref) > 0) {
  	        $ret[2] = $ref[1];
            } else { // No failures line, probably we got killed
                $ret[2] = 3;
            }
        }

        chdir($dir);
        return $ret;
    }

    /**
     * Internal function for cleaning up the mess created by compiling
     *
     * @param String code
     * @param String sessionID
     * @return String[]
     */
    function cleanup($sessionID) {
        $tmpdir = 'tmp/' . $sessionID;
	exec('rm -rf "'.$tmpdir.'"');
    }

    function lock_engine($lockfile) {
        $this->lp = fopen($lockfile, "w+");

        if (!flock($this->lp, LOCK_EX)) { // do an exclusive lock
	    error_log("Failed to lock engine");
        }

    }
    function unlock_engine() {
        flock($this->lp, LOCK_UN); // release the lock
        fclose($this->lp);
    }
}
?>
