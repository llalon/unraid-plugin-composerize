<?php

/*
 * Install a docker compose stack. Supports POST only with:
 *
 * - name (string): The name of the docker compose stack.
 * - compose (string): The Docker Compose YAML configuration to install.
 * - force (boolean): Will not warn if an existing stack will be overwritten if true. (Optional)
 *
 * Example Response:
 * {
 *    "status": true,
 *    "force": true
 * }
 */

$docroot = $docroot ?: $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
$pluginRoot = '/plugins/composerize';

include $docroot . $pluginRoot . '/include/composerize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die();
}

$name = $_POST['name'];
$compose = $_POST['compose'];
$force = $_POST['force'] == 'true';

if (empty($name) || empty($compose) || !isValidYaml($compose)) {
    http_response_code(400);
    die();
}

$status = installCompose($name, $compose, $force);
http_response_code(200);

header('Content-Type: application/json');
die(json_encode(array(
    'status' => $status,
    'force' => $force
)));