<?php

/*
 * This endpoint is for functions related to generating compose strings from templates.
 */

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/service/composerizer.php");

$name = empty($_GET["name"]) ? false : trim(htmlspecialchars($_GET['name']));

if ($_SERVER["REQUEST_METHOD"] != "GET" || $name === false) {
    http_response_code(400);
    die();
}

$compose = composerize($name);

if (empty($compose)) {
    http_response_code(500);
    die();
}

http_response_code(200);
die(json_encode($compose));