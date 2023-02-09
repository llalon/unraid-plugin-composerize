<?php

/*
 * Functions related to generating docker compose yamls from unraid xml templates
 */

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/definitions.php");
require_once("$docroot/plugins/composerize/include/util.php");

// From dynamic docker plugin
require_once("$docroot/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("$docroot/plugins/dynamix.docker.manager/include/Helpers.php");

$DockerClient = new DockerClient();
$DockerUpdate = new DockerUpdate();
$DockerTemplates = new DockerTemplates();

$custom = DockerUtil::custom();
$subnet = DockerUtil::network($custom);
$cpus = DockerUtil::cpus();

/**
 * Composerize an unraid docker xml template
 *
 * @param $name string xml template name
 * @return ?array docker compose
 */
function composerize(string $name): ?array
{
    return composerizeTemplateByName($name);
}

/**
 * Convert template XML to docker run
 *
 * @param string $templateXML template xml data
 * @return string|null docker run command or null
 */
function getTemplateCommandFromXml(string $templateXML): ?string
{
    ob_start();
    $cmd = xmlToCommand($templateXML, false)[0];
    ob_clean();

    return $cmd;
}

/**
 * Convert template XML to container name
 *
 * @param string $templateXML template xml data
 * @return string|null container name
 */
function getTemplateNameFromXml(string $templateXML): ?string
{
    ob_start();
    $xmlVars = xmlToVar($templateXML);
    ob_clean();
    $name = $xmlVars['Name'];
    ob_clean();

    return $name;
}

/**
 * Get the file path of the unraid xml template
 *
 * @param string $name unraid xml template name
 * @return string|null file path of its xml content
 */
function getTemplateFilePath(string $name): ?string
{
    $templates = getDockerTemplateList();

    $file = $templates[$name];

    if (empty($file)) {
        return null;
    }

    return $file;
}

/**
 * Gets the xml content for a given unraid template
 *
 * @param String $name unraid xml template name
 * @return string|null actual xml file content
 */
function getTemplateXmlContents(string $name): ?string
{
    $templateFile = getTemplateFilePath($name);

    if (empty($templateFile)) {
        return null;
    }

    $templateXML = file_get_contents($templateFile);

    if (empty($templateXML)) {
        return null;
    }

    return $templateXML;
}

/**
 * @param string $name unraid xml template name
 * @return array|null
 */
function composerizeTemplateByName(string $name): ?array
{
    $templateXML = getTemplateXmlContents($name);

    if (empty($templateXML)) {
        return null;
    }

    return composerizeTemplateXML($templateXML);
}

/**
 * Converts a docker run command to a yaml compose.
 * ToDo replace with native php
 *
 * @param $cmd string docker run command
 * @return ?string yaml compose file
 */
function composerizeCommand(string $cmd): ?string
{
    $cmd = str_replace("/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker create", 'docker run', $cmd);
    $systemCmd = COMPOSE_BINARY . " " . $cmd;

    $output = array();
    exec($systemCmd, $output, $return);

    if ($return != 0) {
        return null;
    }

    return implode("\n", $output);
}

/**
 * Convert an unraid xml docker template to docker compose + a name.
 *
 * @param string $templateXML unraid xml template
 * @return ?array containing the two strings: 'compose' and 'name'
 */
function composerizeTemplateXML(string $templateXML): ?array
{
    $name = getTemplateNameFromXml($templateXML);
    $cmd = getTemplateCommandFromXml($templateXML);

    if (empty($name) || empty($cmd)) {
        return null;
    }

    $compose = composerizeCommand($cmd);
    ob_clean();

    if (empty($compose)) {
        return null;
    }

    ob_end_clean();

    return array(
        'compose' => $compose,
        'name' => $name
    );
}