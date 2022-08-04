<?php

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/Definitions.php");

function debugToConsole($data)
{
    $output = clean($data);
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

function clean($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function checkDependencies(): bool
{
    // Check if compose.manager plugin is installed.
    if (!file_exists(COMPOSE_DIRECTORY) && !is_dir(COMPOSE_DIRECTORY)) {
        return false;
    }

    // Check if docker.manager plugin is installed.
    if (!file_exists(DOCKER_TEMPLATE_DIRECTORY) && !is_dir(DOCKER_TEMPLATE_DIRECTORY)) {
        return false;
    }

    return true;
}

?>