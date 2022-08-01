<?php

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/Definitions.php");
require_once("$docroot/plugins/composerize/include/Util.php");
require_once("$docroot/plugins/dynamix.docker.manager/include/Helpers.php"); // From dynamic docker plugin

#    ██████╗ ██████╗ ██████╗ ███████╗
#   ██╔════╝██╔═══██╗██╔══██╗██╔════╝
#   ██║     ██║   ██║██║  ██║█████╗
#   ██║     ██║   ██║██║  ██║██╔══╝
#   ╚██████╗╚██████╔╝██████╔╝███████╗
#    ╚═════╝ ╚═════╝ ╚═════╝ ╚══════╝

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['template_name'])) {
    debugToConsole("INFO: POST received.");

    $name = htmlspecialchars($_POST['template_name']);

    if (!checkDependencies()) {
        debugToConsole("ERROR: Missing dependencies.");
        http_response_code(400);
    }

    $result = composerizeTemplateByName($name);

    if ($result){
        http_response_code(200);
        debugToConsole("INFO: Success!");
    } else {
        http_response_code(400);
        debugToConsole("INFO: Failure!");
    }
}

#   ███████╗██╗   ██╗███╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗███████╗
#   ██╔════╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║██╔════╝
#   █████╗  ██║   ██║██╔██╗ ██║██║        ██║   ██║██║   ██║██╔██╗ ██║███████╗
#   ██╔══╝  ██║   ██║██║╚██╗██║██║        ██║   ██║██║   ██║██║╚██╗██║╚════██║
#   ██║     ╚██████╔╝██║ ╚████║╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║███████║
#   ╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝

function composerizeTemplateByName($name): bool
{
    debugToConsole("INFO: Generating compose file for: " . $name . ".");

    $templateFile = getDockerTemplateList()[$name];
    if (empty($templateFile)) {
        debugToConsole("ERROR: Failed to get template file by name.");
        return false;
    }

    $templateXML = file_get_contents($templateFile);
    if (empty($templateXML)) {
        debugToConsole("ERROR: Failed to load template file.");
        return false;
    }

    return composerizeTemplateXML($templateXML);
}

function composerizeTemplateXML($templateXML): bool
{
    $xmlVars = xmlToVar($templateXML);
    $name = $xmlVars['Name'];
    $cmd = xmlToCommand($templateXML, false)[0];

    if ($name === null || trim($name) === '') {
        debugToConsole("ERROR: Unable to parse name.");
        return false;
    }

    if ($cmd === null || trim($cmd) === '') {
        debugToConsole("ERROR: Unable get docker command.");
        return false;
    }

    $compose_project_directory = COMPOSE_DIRECTORY . $name . "/";
    $compose_yaml_file = $compose_project_directory . "docker-compose.yml";
    $compose_name_file = $compose_project_directory . "name";

    mkdir($compose_project_directory, 0755, true);

    $compose = composerizeCommand($cmd);

    if (!$compose){
        debugToConsole("ERROR: Unable get docker compose.");
        return false;
    }

    file_put_contents($compose_name_file, $name);
    file_put_contents($compose_yaml_file, $compose);

    return true;
}

function composerizeCommand($cmd): string
{
    // TODO: Replace with native binary
    // TODO: Validate yaml output

    debugToConsole("DEBUG: Composerizing command: " . $cmd);

    $cmd = str_replace("/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker create", 'docker run', $cmd);
    $systemCmd = "docker run --rm wondercode/composerize-plus:1.1.0 " . $cmd;

    $output = array();
    exec($systemCmd, $output, $return);

    if ($return != 0)
    {
        // error occurred
        debugToConsole("DEBUG: Failed to execute cmd");
        return "";
    }
    else
    {
        // success
        debugToConsole("DEBUG: Commanded completed successfully.");
        return implode("\n", $output);
    }
}

?>




