<?php

require __DIR__ . '/../src/autoload.php';

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
            $project = $this->rips->addProject(array(
                'name' => $path['filename'],
                'source' => $file
            ));
            echo "Upload complete\n";
        } else {
            echo "Project already existing\n";
        }

        while (1) {
            $status = $this->rips->getProjectStatus($project['projectId']);

            echo "\rStatus: " . $status['percent'];

            if (($status['phase'] == 0) && ($status['percent'] == 100)) {
                echo "\n";
                break;
            }

            sleep(5);
        }

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
