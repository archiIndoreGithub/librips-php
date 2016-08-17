#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use RIPS\API\Client;
use RIPS\API\Exceptions\NotAuthorizedError;

class RIPSExample
{
    private $rips;

    public function __construct($name, $password)
    {
        $curl = array(
            // Only required for CURL on Windows.
            CURLOPT_CAINFO => 'ca.crt'
        );

        $this->rips = new Client(false, $curl);
        $this->rips->login(array(
            'name' => $name,
            'password' => $password
        ));
    }

    /**
     * It is easy to extend methods of the API in case special functionality is
     * required. In this simple example we want to test if there is already a
     * project with the same name as the current project. To do this we simply
     * iterate over all projects of the current user and check the name.
     */
    public function getProjectByName($name)
    {
        $projects = $this->rips->getProjects();

        foreach ($projects as $project) {
            if ($project['name'] === $name) {
                return $project;
            }
        }

        return false;
    }

    public function scan($file)
    {
        $path = pathinfo($file);

        $project = $this->getProjectByName($path['filename']);
        if ($project === false) {
            echo "Uploading project\n";

            /**
             * Upload a source code archive to the RIPS cloud and add it to the
             * queue. By sending "path" instead of "source" it would be possible
             * to use this example script for the self-hosted version of RIPS.
             */
            $project = $this->rips->addProject(array(
                'name' => $path['filename'],
                'source' => $file
            ));

            echo "Upload complete\n";
        } else {
            echo "Project already existing\n";
        }

        /**
         * Block the execution until the scan is finished. This may take
         * several minutes.
         */
        $this->rips->blockUntilFinished($project['projectId']);

        /**
         * If this point is reached it means that the scan is finished and we
         * can start to process interesting information, e.g., issues. Some
         * columns contain numerical identifiers and have to be dereferenced to
         * be readable for humans.
         */
        $issues = $this->rips->getProjectIssues($project['projectId']);

        foreach ($issues as &$issue) {
            $issue['type'] = $this->rips->getIssueType($issue['typeId']);
            $issue['file'] = $this->rips->getProjectFilename($project['projectId'], $issue['fileId']);
        }

        return $issues;
    }
}

if ($argc < 4) {
    print "php " . $argv[0] . " [user] [pass] [file]\n";
    exit(1);
}

try {
    $example = new RIPSExample($argv[1], $argv[2]);
} catch (NotAuthorizedError $e) {
    echo "Invalid login\n";
    exit(1);
} catch (Exception $e) {
    echo "Could not connect\n";
    exit(1);
}

print_r($example->scan($argv[3]));
