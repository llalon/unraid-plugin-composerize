<?php

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/composerize/include/Definitions.php");
require_once("$docroot/plugins/composerize/include/Util.php");
require_once("$docroot/plugins/dynamix.docker.manager/include/Helpers.php"); // From dynamic docker plugin

# GET - docker run => composerize
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["name"])) {
    //log("INFO: GET received.");

    $name = htmlspecialchars($_GET['name']);

    ob_start();
    $response = getComposerize($name);
    ob_end_clean();

    header('Content-type: application/json');
    http_response_code($response['status']);
    echo json_encode($response['body']);
}

# POST - Submit with specified compose data
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && isset($_POST['compose'])) {
    //log("INFO: POST received.");

    $name = htmlspecialchars($_POST['name']);
    $compose = htmlspecialchars($_POST['compose']);

    ob_start();
    $response = postCompose($name, $compose);
    ob_end_clean();

    header('Content-type: application/json');
    http_response_code($response['status']);
    echo json_encode($response['body']);
}

# POST - Submits a composerize - changes fs
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    //log("INFO: POST received.");

    $name = htmlspecialchars($_POST['name']);

    ob_start();
    $response = postComposerize($name);
    ob_end_clean();

    header('Content-type: application/json');
    http_response_code($response['status']);
    echo json_encode($response['body']);
}

else {
    header('Content-type: application/json');
    http_response_code(404);
    echo json_encode([]);
}

function getComposerize($name): array
{
    $result = composerizeTemplateByName($name);

    // Save to fs
    if (!isset($result["compose"])) {
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

function postCompose($name, $compose): array
{
    $compose_yaml_file = installCompose($name, $compose);

    if (strlen($compose_yaml_file) > 1) {
        return [
            'body' => [
                'name' => $name,
                'compose' => $name,
                'file' => $compose_yaml_file
            ],
            'status' => 200
        ];
    }

    return [
        'body' => [
            'name' => $name,
            'compose' => $name,
            'error_message' => "Failed to save to filesystem"
        ],
        'status' => 500
    ];
}

function postComposerize($name): array
{

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
    if (!isset($result["compose"])) {
        return [
            'body' => [
                'error_message' => "Failed to generate compose"
            ],
            'status' => 500
        ];
    }

    $compose_yaml_file = installCompose($result['name'], $result['compose']);

    if (!isset($compose_yaml_file)) {
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

function composerizeTemplateByName($name): array
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

function composerizeTemplateXML($templateXML): array
{
    $xmlVars = xmlToVar($templateXML);
    ob_clean();
    $name = $xmlVars['Name'];
    $cmd = xmlToCommand($templateXML, false)[0];
    ob_clean();

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

    $compose = composerizeCommand($cmd);
    ob_clean();

    if (!$compose) {
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

function getComposeFilePaths($name): array
{
    $compose_project_directory = COMPOSE_DIRECTORY . $name . "/";
    $compose_yaml_file = $compose_project_directory . "docker-compose.yml";
    $compose_name_file = $compose_project_directory . "name";

    return [
        'compose_project_directory' => $compose_project_directory,
        'compose_yaml_file' => $compose_yaml_file,
        'compose_name_file' => $compose_name_file
    ];
}

function installCompose($name, $compose): string
{
    $paths = getComposeFilePaths($name);

    $compose_project_directory = $paths['compose_project_directory'];
    $compose_yaml_file = $paths['compose_yaml_file'];
    $compose_name_file = $paths['compose_name_file'];

    mkdir($compose_project_directory, 0755, true);

    $nameResult = file_put_contents($compose_name_file, $name);
    $composeResult = file_put_contents($compose_yaml_file, $compose);

    // Unable to save
    if (!$nameResult || !$composeResult){
        return "";
    }

    return $compose_yaml_file;
}

function composerizeCommand($cmd): string
{
    // TODO: Validate yaml output
    //log("DEBUG: Composerizing command: " . $cmd);

    $cmd = str_replace("/usr/local/emhttp/plugins/dynamix.docker.manager/scripts/docker create", 'docker run', $cmd);
    $systemCmd = COMPOSE_BINARY . " " . $cmd;

    $output = array();
    exec($systemCmd, $output, $return);

    if ($return != 0) {
        // error occurred
        //log("DEBUG: Failed to execute cmd");
        return "";
    } else {
        // success
        //log("DEBUG: Commanded completed successfully.");
        return implode("\n", $output);
    }
}

?>
