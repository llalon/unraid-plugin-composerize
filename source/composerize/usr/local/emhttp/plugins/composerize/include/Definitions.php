<?php
const DOCKER_TEMPLATE_DIRECTORY = '/boot/config/plugins/dockerMan/templates-user/';
const COMPOSE_DIRECTORY = '/boot/config/plugins/compose.manager/projects/';
const COMPOSE_BINARY = '/usr/local/emhttp/plugins/composerize/bin/composerize';

$templatesList = array();

function getDockerTemplateList(): array
{
    global $templatesList;

    if (!$templatesList) {
        $szGlob = DOCKER_TEMPLATE_DIRECTORY . "*.xml";
        $files = glob($szGlob);

        $templatesList = array();

        foreach ($files as $file) {
            $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
            $name = preg_replace('/^.*?(?=my-)/', '', $name);
            $name = preg_replace('/my-/i', '', $name);
            $templatesList[$name] = $file;
        }
    }

    return $templatesList;
}

?>