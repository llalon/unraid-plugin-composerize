<?php

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/definitions.php");

/**
 * @return bool true if all dependencies are met
 */
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

function createDirectoryIfNotAvailable($directory): void
{
    ob_start();
    mkdir($directory, 0755, true);
    ob_end_clean();
}

function saveFileContentToDisk($path, $content): bool
{
    ob_start();
    $putResult = file_put_contents($path, $content);
    ob_end_clean();

    return ($putResult == true);
}

/**
 * @return array of all available templates
 */
function getDockerTemplateList(): array
{
    $szGlob = DOCKER_TEMPLATE_DIRECTORY . "*.xml";
    $files = glob($szGlob);

    $templatesList = array();

    foreach ($files as $file) {
        $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
        $name = preg_replace('/^.*?(?=my-)/', '', $name);
        $name = preg_replace('/my-/i', '', $name);
        $templatesList[$name] = $file;
    }

    return $templatesList;
}