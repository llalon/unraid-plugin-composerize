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
    //log("INFO: POST received.");

    $name = htmlspecialchars($_POST['template_name']);

    if (!checkDependencies()) {
        //log("ERROR: Missing dependencies.");
        http_response_code(400);
    }
    
    ob_start();
    $result = composerizeTemplateByName($name);
    ob_end_clean();

    if ($result["compose"] != null){
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    header('Content-type: application/json');
    echo json_encode($result);
}

#   ███████╗██╗   ██╗███╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗███████╗
#   ██╔════╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║██╔════╝
#   █████╗  ██║   ██║██╔██╗ ██║██║        ██║   ██║██║   ██║██╔██╗ ██║███████╗
#   ██╔══╝  ██║   ██║██║╚██╗██║██║        ██║   ██║██║   ██║██║╚██╗██║╚════██║
#   ██║     ╚██████╔╝██║ ╚████║╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║███████║
#   ╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝

function composerizeTemplateByName($name)
{
    //log("INFO: Generating compose file for: " . $name . ".");

    $templateFile = getDockerTemplateList()[$name];
    if (empty($templateFile)) {
        //log("ERROR: Failed to get template file by name.");
        return [
            'compose' => null,
            'file' => null,
            'name' => null
        ];
    }

    $templateXML = file_get_contents($templateFile);
    if (empty($templateXML)) {
        //log("ERROR: Failed to load template file.");
        return [
            'compose' => null,
            'file' => null,
            'name' => null
        ];
    }

    return composerizeTemplateXML($templateXML);
}

function composerizeTemplateXML($templateXML)
{
    $xmlVars = xmlToVar($templateXML);
    $name = $xmlVars['Name'];
    $cmd = xmlToCommand($templateXML, false)[0];   

    if ($name === null || trim($name) === '') {
        //log("ERROR: Unable to parse name.");
        return [
            'compose' => null,
            'file' => null,
            'name' => null
        ];
    }

    if ($cmd === null || trim($cmd) === '') {
        //log("ERROR: Unable get docker command.");
        return [
            'compose' => null,
            'file' => null,
            'name' => $name
        ];
    }

    $compose_project_directory = COMPOSE_DIRECTORY . $name . "/";
    $compose_yaml_file = $compose_project_directory . "docker-compose.yml";
    $compose_name_file = $compose_project_directory . "name";

    mkdir($compose_project_directory, 0755, true);

    $compose = composerizeCommand($cmd);

    if (!$compose){
        //log("ERROR: Unable get docker compose.");
        return [
            'compose' => null,
            'file' => null,
            'name' => $name
        ];
    }

    file_put_contents($compose_name_file, $name);
    file_put_contents($compose_yaml_file, $compose);

    return [
        'compose' => $compose,
        'file' => $compose_yaml_file,
        'name' => $name
    ];
}

function composerizeCommand($cmd)
{
    // TODO: Validate yaml output
    //log("DEBUG: Composerizing command: " . $cmd);

    $cmd = str_replace("/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker create", 'docker run', $cmd);
    $systemCmd = COMPOSE_BINARY . " " . $cmd;

    $output = array();
    exec($systemCmd, $output, $return);

    if ($return != 0)
    {
        // error occurred
        //log("DEBUG: Failed to execute cmd");
        return "";
    }
    else
    {
        // success
        //log("DEBUG: Commanded completed successfully.");
        return implode("\n", $output);
    }
}

?>




