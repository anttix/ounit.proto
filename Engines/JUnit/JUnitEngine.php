<?php

class JUnitEngine {
    public function JUnitEngine(& $parent) {
        $this->parent = &$parent;
    }

    public function getMarks($questionID) {
        $limit = "ulimit -t 15";
        $qdir = $this->getPathFromQuestionID($questionID);

        $cmd = JAVA.' -cp "'.JUNIT_JAR.':'.$qdir.':'.E_JUNIT.'" '.
               'CountTests AnswerTest 2>&1';
        exec($limit . ';' . $cmd, $output, $returncode);
        $marks = $output[0];

        if(is_numeric($marks))
            return $marks;
        else
            die("Unable to determine number of marks for $questionID");
    }

    public function getPathFromQuestionID($questionID) {
        $dir = getcwd();
        return $dir.'/questions/'.$questionID;
    }

    public function get_session_variable($var) {
        return $this->parent->get_session_variable($var);
    }

    public function set_session_variable($var, $val) {
        return $this->parent->set_session_variable($var, $val);
    }

    public function do_start(& $ret, $questionID) {
        $desc = $this->load_desc($questionID);
        $marks = $this->getMarks($questionID);
        $this->set_session_variable("marks", $marks);
        $code = $this->get_session_variable("code");
        if(is_null($code)) $code = $this->load_code($questionID);
        $ret->XHTML = $this->question_xhtml($desc, $code);
        $ret->resources = array($this->load_testfile($questionID));
        $this->set_session_variable("ncompile", 3);
    }

    public function do_process(& $ret, & $response,
                               $questionSession, $questionID) {
        $marks      = $this->get_session_variable("marks");
        $score      = $this->get_session_variable("score");
        $output     = $this->get_session_variable("output");
        $code       = $this->get_session_variable("code");
        $ncompile   = $this->get_session_variable("ncompile");
        $codechanged = false;

        // $ret->progressInfo = '1'; // Tries left?

        $desc = $this->load_desc($questionID);
	if(isset($response["code"])) {
            if($code !== $response["code"])
                $codechanged = true;

            $code = $response["code"];
            $this->set_session_variable("code", $code);
        } else {
            $code = $this->load_code($questionID);

            if($code != null)
                $codechanged = true;
        }

	/* $response["event"] is moodle specific indicator.
           7 means submit 2 means save w/o submit */
        if(isset($response["grade"]) ||
           (isset($response["event"]) && $response["event"] == 7)) {
            $grade = true;
        } else {
            $grade = false;
        }
        /* Only compile if instructed, code changed or our
           poor fool tried to grade w/o ever trying to compile */
	if(isset($response["compile"]) || $codechanged == true ||
           ($score == null && $grade == true)) {

            $co = $this->compile($code, $questionSession);
            if($co[0] == 0) {
                $to = $this->test($questionSession);
                $failed = $to[2];
            } else {
                $to = array();
                $failed = $marks;
            }
            $this->cleanup($questionSession);
            $score = $marks - $failed;

            /* Subtract the number of times we allow to compile,
               Grade automatically if out of retries */
            if(isset($response["compile"]))
                $ncompile = $ncompile - 1;

            if($ncompile < 1) $grade = true;

            $output = $this->output_xhtml($co, $to);
            $this->set_session_variable("score", $score);
            $this->set_session_variable("output", $output);
            $this->set_session_variable("ncompile", $ncompile);

            /* Grade automatically on maxium score */
            if($score >= $marks) $grade = true;
        } 

        if($grade == true) {
            $ret->XHTML = $this->results_xhtml($desc, $code, $output);
	    $res = new Results();
            $res->scores[] = new Score(NULL, $score);
	    $ret->results = $res;
        } else {
            $ret->XHTML = $this->question_xhtml($desc, $code, $output);
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
    function question_xhtml($descr, $codetpl, $results = '') {
        $tries = $this->get_session_variable("ncompile");
        $html = file_get_contents(dirname(__FILE__) . '/question.thtml');
        $html = str_replace('%%DESCRIPTION%%', $descr, $html);
        $html = str_replace('%%CODE_TEMPLATE%%',
                            htmlspecialchars($codetpl), $html);

	/* Utter a warning message when displaying results */
	if($results) {
            if($tries > 0)
                $html .= "<div>NB! You can retry this test $tries times</div>";

            $html .= '<div style="background-color: red;">';
            $html .= 'The score is <strong>not</strong> saved. ' .
                     'You MUST click on "Grade" to make your answer final.';
            $html .= '</div>';
	}

        $html = str_replace('%%RESULTS%%', $results, $html);

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
    function output_xhtml($co, $to) {
        $marks      = $this->get_session_variable("marks");

        $html = '';
        $score = 0;

        if($co[0] != 0) {
            $html .= '<div style="color: red;font-size:150%;">';
            $html .= 'Compile Error</div>';
        }
        $html .= '<pre class="compileroutput">';
        $html .= htmlspecialchars($co[1]);
        $html .= '</pre>';

        if(!empty($to)) {
            $score = $marks - $to[2];
            if($score < $marks or $to[0] != 0) {
                $html .= '<div style="color: red;font-size:150%;">';
                $html .= 'Some tests FAILED</div>';
            }
            $html .= '<pre class="unittestoutput">';
            $html .= htmlspecialchars($to[1]);
            $html .= '</pre>';
        }
        $html .= "Score: " . $score . " / $marks";

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
    function load_code($questionID) {
        return file_get_contents('questions/'.$questionID.'/Answer.java');
    }

    /**
     * Internal function for loading unit test class file.
     *
     * @param String questionID
     * @return String
     */
    function load_testfile($questionID) {
        $r = new Resource();
        $r->content = file_get_contents('questions/'.$questionID.'/AnswerTest.class');
        $r->encoding = "binary";
        $r->filename = "/AnswerTest.class";
        $r->mimeType = "application/x-java-class";

        return $r;
    }

    /**
     * Internal function for compiling submitted Java code
     *
     * @param String code
     * @param String sessionID
     * @return String[]
     */
    function compile($code, $sessionID) {
        $limit="ulimit -t 60";
        $dir = getcwd();
        $tmpdir = 'tmp/' . $sessionID;
        if(!is_dir($tmpdir)) mkdir($tmpdir);
	chdir($tmpdir);

        file_put_contents('Answer.java', $code);
        $output = array();

        $cmd = JAVAC.' Answer.java 2>&1';
        exec($limit . ';' . $cmd, $output, $returncode);

        $ret = array($returncode, "");
        foreach($output as $o) {
            $ret[1] .= $o . "\n";
        }

        chdir($dir);

        /* Very strange hack indeed. We need to filter those characters
         * because othervise they'll create SOAP XML errors. Go figure!
         */
        $ret = str_replace("\x1a","",$ret);
        $ret = str_replace("\x00","",$ret);

        return $ret;
    }

    /**
     * Internal function for testing submitted Java code
     *
     * @param String code
     * @param String sessionID
     * @return String[]
     */
    function test($sessionID) {
        $limit = "ulimit -t 15";
        $dir = getcwd();
        $questionID = $this->get_session_variable("questionID");
        $marks      = $this->get_session_variable("marks");
        $qdir       = $this->getPathFromQuestionID($questionID);
        $tmpdir = 'tmp/' . $sessionID;

	chdir($tmpdir);

        $output = array();

        $cmd = JAVA.' -cp "'.JUNIT_JAR.':'.$qdir.':." '.
	       '-Djava.security.manager -Djava.security.policy='.POLICY.
               ' org.junit.runner.JUnitCore AnswerTest 2>&1';
        exec($limit . ';' . $cmd, $output, $returncode);

        $ret = array($returncode, "", $marks);
        foreach($output as $o) {
            // Filter out stack trace lines for clarity
            if(!preg_match("/^[ 	]*at/", $o))
                $ret[1] .= $o . "\n";
        }

        if($returncode == 0) {
	    $ret[2] = 0;
        } else {
            // Count failures
            if(preg_match("/Failures: (\d+)\s*$/", $ret[1], $ref) > 0) {
  	        $ret[2] = $ref[1];
            } else { // No failures line, probably we got killed
                $ret[2] = $marks;
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
}
?>
