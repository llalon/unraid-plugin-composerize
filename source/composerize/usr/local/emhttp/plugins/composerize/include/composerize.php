<?php

const DOCKER_TEMPLATE_DIRECTORY = '/boot/config/plugins/dockerMan/templates-user/';
const COMPOSE_DIRECTORY = '/boot/config/plugins/compose.manager/projects/';

$docroot = $docroot ?: $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

// Initialize docker connection from dynamix docker plugin
require_once("$docroot/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("$docroot/plugins/dynamix.docker.manager/include/Helpers.php");

// ToDo allow for user customization
$dockerClient = new DockerClient();
$dockerUpdate = new DockerUpdate();
$dockerTemplates = new DockerTemplates();
$custom = DockerUtil::custom();
$subnet = DockerUtil::network($custom);
$cpus = DockerUtil::cpus();

function isValidYaml($yamlString): bool
{
    // ToDo - add better validation for yaml
    return !empty($yamlString);
}

function writeContentToFile($file, $content): bool
{
    try {
        $f = fopen($file, "w");
        fwrite($f, $content);
        fclose($f);

        return true;
    } catch (Exception $exception) {
        var_dump($exception);
        return false;
    }
}

/**
 * Install a compose stack to the disk.
 *
 * @param string $name docker stack name
 * @param string $compose compose yaml string
 * @param bool $force whether to force overwrite
 * @return string status "success", "failure" or "exists"
 */
function installCompose(string $name, string $compose, bool $force): string
{
    try {
        $composeProjectDirectory = COMPOSE_DIRECTORY . $name . "/";
        $composeYamlFilePath = $composeProjectDirectory . "docker-compose.yml";
        $composeNameFilePath = $composeProjectDirectory . "name";

        $nameFileExists = file_exists($composeNameFilePath);
        $yamlFileExists = file_exists($composeYamlFilePath);

        if (!$force && ($nameFileExists || $yamlFileExists)) {
            return "exists";
        }

        mkdir($composeProjectDirectory, 0755, true);

        $saveFileResultName = writeContentToFile($composeNameFilePath, $name);
        $saveFileResultYaml = writeContentToFile($composeYamlFilePath, $compose);

        if ($saveFileResultName && $saveFileResultYaml) {
            return "success";
        }

        return "failure";
    } catch (Exception $exception) {
        return "failure";
    }
}

/**
 * Get a list of available templates as key value pairs.
 * file =>
 *
 * @return array docker templates and run commands
 */
function getDockerTemplateList(): array
{
    $dockerTemplates = array();

    $files = glob(DOCKER_TEMPLATE_DIRECTORY . "*.xml");

    foreach ($files as $file) {
        $info = xmlToCommand($file, false);

        $command = str_replace("/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker create", 'docker run', $info[0]);
        $name = $info[1];

        $dockerTemplates[$name] = $command;
    }

    return $dockerTemplates;
}
