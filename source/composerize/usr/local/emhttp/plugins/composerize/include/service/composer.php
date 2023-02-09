<?php

/*
 * Functions related to interacting with compose manager and saving compose templates to the file system.
 */

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/definitions.php");
require_once("$docroot/plugins/composerize/include/util.php");

/**
 * Installs a compose to disk. Writes to the default compose plugin directories.
 *
 * @param array $compose contains name and yaml elements
 * @return bool true if template was successfully installed
 */
function installCompose(array $compose): bool
{
    $name = $compose['name'];
    $yaml = $compose['compose'];

    if (empty($name) || empty($yaml)) {
        return false;
    }

    $composeProjectDirectory = COMPOSE_DIRECTORY . $name . "/";
    $composeYamlFilePath = $composeProjectDirectory . "docker-compose.yml";
    $composeNameFilePath = $composeProjectDirectory . "name";

    createDirectoryIfNotAvailable($composeProjectDirectory);

    $saveFileResultName = saveFileContentToDisk($composeNameFilePath, $name);
    $saveFileResultYaml = saveFileContentToDisk($composeYamlFilePath, $yaml);

    return ($saveFileResultName && $saveFileResultYaml);
}