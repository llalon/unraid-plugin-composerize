<?php

/*
 * For checking system deps and alerting user if not avail
 */

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/definitions.php");
require_once("$docroot/plugins/composerize/include/util.php");

$result = checkDependencies();
die(json_encode(array("healthy" => $result)));

