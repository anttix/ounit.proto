<?php
define('JAVAC',     'javac');
define('JAVA',      'java');
define('JUNIT_JAR', '/usr/share/java/junit4.jar');
// define('JUNIT_JAR',    dirname(__FILE__) . '/../junit4.jar');
define('ROOT',      'http://localhost/ounit');
define('ANSURL',    ROOT . '/tmp');
define('E_JUNIT',   dirname(__FILE__) . '/Engines/JUnit');
define('POLICY',    E_JUNIT . '/junit.policy');
define('JHELPERS',  dirname(__FILE__) . '/helpers');
define('SSRV',      dirname(__FILE__) . '/run-selenium');

$delegateURL = "http://localhost:8080/ounit-server/OunitService";
?>
