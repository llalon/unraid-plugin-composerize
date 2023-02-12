<?php

/*
 * This endpoint is for functions related saving compose files to the compose manager.
 * POST body requires `name` and optionally `compose`. If `compose` is not provided the default will be used.
 *
 */

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/service/composer.php");
require_once("$docroot/plugins/composerize/include/service/composerizer.php");

$name = empty($_POST["name"]) ? false : trim(htmlspecialchars($_POST['name']));
$compose = empty($_POST["compose"]) ? false : trim(htmlspecialchars($_POST['compose']));

if ($_SERVER["REQUEST_METHOD"] != "POST" || $name === false) {
    http_response_code(400);
    die();
}

$result = installCompose(array(
    "name" => $name,
    "compose" => (empty($compose)) ? composerize($name) : $compose
));

http_response_code($result ? 200 : 500);
die(json_encode(array(
    "success" => ($result == true)
)));