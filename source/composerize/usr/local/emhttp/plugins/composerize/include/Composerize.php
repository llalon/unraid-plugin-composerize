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

# POST - Submits a composerize - changes fs
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['template_name'])) {
    //log("INFO: POST received.");
    
    $name = htmlspecialchars($_POST['template_name']);

    ob_start();
    $response = postComposerize($name);
    ob_end_clean();

    header('Content-type: application/json');
    http_response_code($response['status']);
    echo json_encode($response['body']);
}

# GET - docker run => composerize
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["template_name"])) {
    //log("INFO: GET received.");

    $name = htmlspecialchars($_GET['template_name']);

    ob_start();
    $response = getComposerize($name);
    ob_end_clean();

    header('Content-type: application/json');
    http_response_code($response['status']);
    echo json_encode($response['body']);
}

#   ███████╗██╗   ██╗███╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗███████╗
#   ██╔════╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║██╔════╝
#   █████╗  ██║   ██║██╔██╗ ██║██║        ██║   ██║██║   ██║██╔██╗ ██║███████╗
#   ██╔══╝  ██║   ██║██║╚██╗██║██║        ██║   ██║██║   ██║██║╚██╗██║╚════██║
#   ██║     ╚██████╔╝██║ ╚████║╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║███████║
#   ╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝

function getComposerize($name){
    $result = composerizeTemplateByName($name);

    // Save to fs
    if (!isset($result["compose"])){
        return [
            'body' => [
                'error_message' => "Failed to generate compose"
            ],
            'status' => 500
        ];
    }

    return [
        'body' => [
            'name' => $result['name'],
            'compose' => $result['compose'],
        ],
        'status' => 200
    ];
}

function postComposerize($name){

    if (!checkDependencies()) {
        //log("ERROR: Missing dependencies.");
        return [
            'body' => [
                'error_message' => "Missing dependencies"
            ],
            'status' => 500
        ];
    }

    $result = composerizeTemplateByName($name);

    // Save to fs
    if (!isset($result["compose"])){
        return [
            'body' => [
                'error_message' => "Failed to generate compose"
            ],
            'status' => 500
        ];
    }

    $compose_yaml_file = installCompose($result['name'], $result['compose']);

    if (!isset($compose_yaml_file)){
        return [
            'body' => [
                'name' => $result['name'],
                'compose' => $result['compose'],
                'error_message' => "Failed to save to filesystem"
            ],
            'status' => 500
        ];
    }

    return [
        'body' => [
            'name' => $result['name'],
            'compose' => $result['compose'],
            'file' => $compose_yaml_file
        ],
        'status' => 200
    ];
}

function composerizeTemplateByName($name)
{
    //log("INFO: Generating compose file for: " . $name . ".");

    $templateFile = getDockerTemplateList()[$name];
    if (empty($templateFile)) {
        //log("ERROR: Failed to get template file by name.");
        return [
            'compose' => null,
            'name' => null
        ];
    }

    $templateXML = file_get_contents($templateFile);
    if (empty($templateXML)) {
        //log("ERROR: Failed to load template file.");
        return [
            'compose' => null,
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
    $paths = getComposeFilePaths($name); 

    if ($name === null || trim($name) === '') {
        //log("ERROR: Unable to parse name.");
        return [
            'compose' => null,
            'name' => null
        ];
    }

    if ($cmd === null || trim($cmd) === '') {
        //log("ERROR: Unable get docker command.");
        return [
            'compose' => null,
            'name' => $name
        ];
    }

    // here
    $compose = composerizeCommand($cmd);
    // here

    if (!$compose){
        //log("ERROR: Unable get docker compose.");
        return [
            'compose' => null,
            'name' => $name
        ];
    }

    return [
        'compose' => $compose,
        'name' => $name
    ];
}

function getComposeFilePaths($name){
    $compose_project_directory = COMPOSE_DIRECTORY . $name . "/";
    $compose_yaml_file = $compose_project_directory . "docker-compose.yml";
    $compose_name_file = $compose_project_directory . "name";

    return [
        'compose_project_directory' => $compose_project_directory,
        'compose_yaml_file' => $compose_yaml_file,
        'compose_name_file' => $compose_name_file
    ];
}

function installCompose($name, $compose){
    $paths = getComposeFilePaths($name);

    $compose_project_directory = $paths['compose_project_directory'];
    $compose_yaml_file = $paths['compose_yaml_file'];
    $compose_name_file = $paths['compose_name_file'];

    mkdir($compose_project_directory, 0755, true);

    file_put_contents($compose_name_file, $name);
    file_put_contents($compose_yaml_file, $compose);

    return $compose_yaml_file;
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
