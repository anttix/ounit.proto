<?php
/**
*
* Soap server entry point.
*
* This file is licensed under the GPL License.
*
* A copy of the license is in a file license.txt.
* If you can't find it, you can obtain the license from
* http://www.gnu.org/copyleft/gpl.html
*
* @author Antti Andreimann (anttix@users.sourceforge.net)
* @package opaqueunit
*/

require_once("config.php");

$myurl = ROOT . "/engine.php";

ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '0');

require_once('OpaqueDatatypes.php');
require_once('OpaqueServer.php');

$classmap = array('Resource' => 'Resource',
                  'StartReturn' => 'StartReturn',
                  'CustomResult' => 'CustomResult',
                  'Score' => 'Score',
                  'Results' => 'Results',
                  'ProcessReturn' => 'ProcessReturn');

$soap = new SoapServer(ROOT.'/opaque.wsdl.php',
                       array('uri' => $myurl,
                             'classmap' => $classmap,
                             'encoding' => 'UTF-8'));
$soap->setClass('OpaqueServer');
$soap->handle();
?>
