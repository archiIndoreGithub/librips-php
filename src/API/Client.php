<?php

namespace RIPS\API;

class Client
{
    private $server;
    private $cookies;
    private $options;
    private $login_data;
    private $temp_jar;

    /**
     * Create a new object of the RIPS API client.
     *
     * @param $server string Custom API server address
     * @param $options array Custom curl options
     * @param $jar string Custom cookie jar
     */
    public function __construct($server = null, $options = array(), $jar = null)
    {
        if ($server) {
            $this->server = $server;
        } else {
            $this->server = 'https://api-1.ripstech.com';
        }
        if ($jar) {
            $this->cookies = $jar;
            $this->temp_jar = false;
        } else {
            $this->cookies = tempnam(sys_get_temp_dir(), 'cookies');
            $this->temp_jar = true;
        }
        $this->options = $options;
    }

    /**
     * Destroy object of the RIPS API client and remove temp. cookie jar.
     */
    public function __destruct()
    {
        if ($this->temp_jar) {
            unlink($this->cookies);
        }
    }

    /**
     * Wrapper for HTTP requests via curl.
     */
    private function send($method, $url, $body = null, $raw = false)
    {
        if ($method !== 'POST' && is_array($body) && !empty($body)) {
            $url .= '?' . http_build_query($body);
        }

        $handle = curl_init($url);

        $defaultOptions = array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => array('Expect:'),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_COOKIEFILE => $this->cookies,
            CURLOPT_COOKIEJAR => $this->cookies
        );
        curl_setopt_array($handle, $this->options + $defaultOptions);

        if ($method === 'POST' && is_array($body)) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($handle);

        if (!$raw) {
            $response = json_decode($response, true);
        }

        $error = curl_error($handle);
        $info = curl_getinfo($handle);

        curl_close($handle);

        if ($error) {
            throw new \Exception($error);
        }

        switch ($info['http_code']) {
            case 200:
                return $response;
            case 400:
                throw new Exceptions\BadRequestError(
                    isset($response['message']) ? $response['message'] : ''
                );
            case 401:
                throw new Exceptions\NotAuthorizedError(
                    isset($response['message']) ? $response['message'] : ''
                );
            case 404:
                throw new Exceptions\NotFoundError(
                    isset($response['message']) ? $response['message'] : ''
                );
            case 500:
                throw new Exceptions\ServerError(
                    isset($response['message']) ? $response['message'] : ''
                );
        }
    }

    /**
     * Get projects of the current user.
     *
     * @param $data array Optional parameters to filter results
     * @return Associative array of projects
     */
    public function getProjects($data = array())
    {
        $url = $this->server . '/projects/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get projects of the current user grouped by status.
     *
     * @return Associative array of projects grouped by status
     */
    public function getProjectsByStatus()
    {
        $url = $this->server . '/projects/by/status/';
        return $this->send('GET', $url);
    }

    /**
     * Delete projects of the current user.
     *
     * @param $data array Optional parameters to filter results
     */
    public function deleteProjects($data = array())
    {
        $url = $this->server . '/projects/';
        return $this->send('DELETE', $url, $data);
    }

    /**
     * Create a new project.
     *
     * @param $data array Project parameters
     */
    public function addProject($data)
    {
        if (isset($data['source'])) {
            $data['source'] = new \CURLFile($data['source']);
        }

        $url = $this->server . '/project/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Get a project via project id.
     *
     * @param $pid int Project id
     * @return Associative array of project
     */
    public function getProject($pid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Update a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional project parameters
     */
    public function updateProject($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Delete a project via project id.
     *
     * @param $pid int Project id
     */
    public function deleteProject($pid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/';
        return $this->send('DELETE', $url);
    }

    /**
     * Get the status of a project via project id.
     *
     * @param $pid int Project id
     * @return Associative array of project status
     */
    public function getProjectStatus($pid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/status/';
        return $this->send('GET', $url);
    }

    /**
     * Get the trend of a project via project id.
     *
     * @param $pid int Project id
     * @return Associative array of project trend
     */
    public function getProjectTrend($pid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/trend/';
        return $this->send('GET', $url);
    }

    /**
     * Get filenames of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project filenames
     */
    public function getProjectFilenames($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/filenames/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get a filename of a project via project id and filename id.
     *
     * @param $pid int Project id
     * @param $fid int Filename id
     * @return Associative array of project filename
     */
    public function getProjectFilename($pid, $fid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/filename/' . intval($fid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get functions of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project functions
     */
    public function getProjectFunctions($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/functions/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get a function of a project via project id and function id.
     *
     * @param $pid int Project id
     * @param $fid int Function id
     * @return Associative array of project function
     */
    public function getProjectFunction($pid, $fid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/function/' . intval($fid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get issue comments of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issue comments
     */
    public function getProjectIssuesComments($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/comments/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get an issue comment of a project via project id and issue comment id.
     *
     * @param $pid int Project id
     * @param $iid int Issue comment id
     * @return Associative array of project issue comment
     */
    public function getProjectIssuesComment($pid, $iid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/comment/' . intval($iid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get issue lines of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issue lines
     */
    public function getProjectIssuesLines($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/lines/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get an issue line of a project via project id and issue line id.
     *
     * @param $pid int Project id
     * @param $iid int Issue line id
     * @return Associative array of project issue line
     */
    public function getProjectIssuesLine($pid, $iid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/line/' . intval($iid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get issue strings of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issue strings
     */
    public function getProjectIssuesStrings($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/strings/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get an issue string of a project via project id and issue string id.
     *
     * @param $pid int Project id
     * @param $iid int Issue string id
     * @return Associative array of project issue string
     */
    public function getProjectIssuesString($pid, $iid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/string/' . intval($iid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get issues of a project via project id grouped by issue type id.
     *
     * @param $pid int Project id
     * @return Associative array of project issues grouped by issue type id
     */
    public function getProjectIssuesByTypes($pid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/by/types/';
        return $this->send('GET', $url);
    }

    /**
     * Get issues of a project via project id and issue type id.
     *
     * @param $pid int Project id
     * @param $iid int Issue type id
     * @return Associative array of project issues
     */
    public function getProjectIssuesByType($pid, $iid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/by/type/' . intval($iid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get issues of a project via project id grouped by filename id.
     *
     * @param $pid int Project id
     * @return Associative array of project issues grouped by filename id
     */
    public function getProjectIssuesByFilenames($pid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/by/filenames/';
        return $this->send('GET', $url);
    }

    /**
     * Get issues of a project via project id and filename id.
     *
     * @param $pid int Project id
     * @param $iid int Filename id
     * @return Associative array of project issues
     */
    public function getProjectIssuesByFilename($pid, $fid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/by/filename/' . intval($fid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get issues of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issues
     */
    public function getProjectIssues($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Delete issues of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     */
    public function deleteProjectIssues($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issues/';
        return $this->send('DELETE', $url, $data);
    }

    /**
     * Get an issue of a project via project id and issue id.
     *
     * @param $pid int Project id
     * @param $iid int Issue id
     * @return Associative array of project issue
     */
    public function getProjectIssue($pid, $iid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issue/' . intval($iid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Update an issue of a project via project id and issue id.
     *
     * @param $pid int Project id
     * @param $iid int Issue id
     * @param $data array Optional issue parameters
     */
    public function updateProjectIssue($pid, $iid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issue/' . intval($iid) . '/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Delete an issue of a project via project id and issue id.
     *
     * @param $pid int Project id
     * @param $iid int Issue id
     */
    public function deleteProjectIssue($pid, $iid)
    {
        $url = $this->server . '/project/' . intval($pid) . '/issue/' . intval($iid) . '/';
        return $this->send('DELETE', $url);
    }

    /**
     * Get issue comments of a project via project id and issue id.
     *
     * @param $pid int Project id
     * @param $iid int Issue id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issue comments
     */
    public function getProjectIssueComments($pid, $iid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issue/' . intval($iid) . '/comments/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get issue lines of a project via project id and issue id.
     *
     * @param $pid int Project id
     * @param $iid int Issue id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issue lines
     */
    public function getProjectIssueLines($pid, $iid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issue/' . intval($iid) . '/lines/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get issue strings of a project via project id and issue id.
     *
     * @param $pid int Project id
     * @param $iid int Issue id
     * @param $data array Optional parameters to filter results
     * @return Associative array of project issue strings
     */
    public function getProjectIssueStrings($pid, $iid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/issue/' . intval($iid) . '/strings/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Get PDF report of a project via project id.
     *
     * @param $pid int Project id
     * @param $data array Optional parameters to filter results
     * @return Raw PDF file
     */
    public function getProjectReport($pid, $data = array())
    {
        $url = $this->server . '/project/' . intval($pid) . '/report/';
        return $this->send('GET', $url, $data, true);
    }

    /**
     * Get issue types.
     *
     * @return Associative array of issue types
     */
    public function getIssueTypes()
    {
        $url = $this->server . '/issues/types/';
        return $this->send('GET', $url);
    }

    /**
     * Get issue type via issue type id.
     *
     * @param $tid int Issue type id
     * @return Associative array of issue type
     */
    public function getIssueType($tid)
    {
        $url = $this->server . '/issues/type/' . intval($tid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Get users.
     *
     * @param $data array Optional parameters to filter results
     * @return Associative array of users
     */
    public function getUsers($data = array())
    {
        $url = $this->server . '/users/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Create a new user.
     *
     * @param $data array User parameters
     */
    public function addUser($data)
    {
        $url = $this->server . '/user/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Get a user via user id.
     *
     * @param $uid int User id
     * @return Associative array of user
     */
    public function getUser($uid)
    {
        $url = $this->server . '/user/' . intval($uid) . '/';
        return $this->send('GET', $url);
    }

    /**
     * Update a user via user id.
     *
     * @param $uid int User id
     * @param $data array Optional user parameters
     */
    public function updateUser($uid, $data = array())
    {
        $url = $this->server . '/user/' . intval($uid) . '/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Delete a user via user id.
     *
     * @param $uid int User id
     */
    public function deleteUser($uid)
    {
        $url = $this->server . '/user/' . intval($uid) . '/';
        return $this->send('DELETE', $url);
    }

    /**
     * Get API version.
     *
     * @return Associative array of version
     */
    public function getVersion()
    {
        $url = $this->server . '/version/';
        return $this->send('GET', $url);
    }

    /**
     * Get the user status.
     *
     * @return Associative array of user status
     */
    public function getStatus()
    {
        $url = $this->server . '/status/';
        return $this->send('GET', $url);
    }

    /**
     * Get stats.
     *
     * @return Associative array of stats
     */
    public function getStats()
    {
        $url = $this->server . '/stats/';
        return $this->send('GET', $url);
    }

    /**
     * Get error logs.
     *
     * @param $data array Optional parameters to filter results
     * @return Associative array of error logs
     */
    public function getErrorLogs($data = array())
    {
        $url = $this->server . '/logs/errors/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Delete error logs.
     *
     * @param $data array Optional parameters to filter results
     */
    public function deleteErrorLogs($data = array())
    {
        $url = $this->server . '/logs/errors/';
        return $this->send('DELETE', $url, $data);
    }

    /**
     * Get info logs.
     *
     * @param $data array Optional parameters to filter results
     * @return Associative array of info logs
     */
    public function getInfoLogs($data = array())
    {
        $url = $this->server . '/logs/infos/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Delete info logs.
     *
     * @param $data array Optional parameters to filter results
     */
    public function deleteInfoLogs($data = array())
    {
        $url = $this->server . '/logs/infos/';
        return $this->send('DELETE', $url, $data);
    }

    /**
     * Get scan logs.
     *
     * @param $data array Optional parameters to filter results
     * @return Associative array of info scans
     */
    public function getScanLogs($data = array())
    {
        $url = $this->server . '/logs/scans/';
        return $this->send('GET', $url, $data);
    }

    /**
     * Delete scan logs.
     *
     * @param $data array Optional parameters to filter results
     */
    public function deleteScanLogs($data = array())
    {
        $url = $this->server . '/logs/scans/';
        return $this->send('DELETE', $url, $data);
    }

    /**
     * Send e-mail to user.
     *
     * @param $uid int User id
     * @param $type string Template type
     * @param $data array Optional parameters for e-mails
     */
    public function sendEmail($uid, $type, $data = array())
    {
        $url = $this->server . '/user/' . intval($uid) . '/email/' . $type . '/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Login and save authentication token in cookie.
     *
     * @param $data array Authentication data
     */
    public function login($data)
    {
        $this->login_data = $data;
        $url = $this->server . '/login/';
        return $this->send('POST', $url, $data);
    }

    /**
     * Login with stored data, but only if the old session is expired.
     */
    public function relogin()
    {
        if (!$this->login_data) {
            throw new \Exception('No login data available');
        }
        try {
            $this->getStatus();
        } catch (Exceptions\NotAuthorizedError $e) {
            $this->login($this->login_data);
        }
    }

    /**
     * Logout and delete authentication token in cookie.
     */
    public function logout()
    {
        $this->login_data = null;
        $url = $this->server . '/logout/';
        return $this->send('POST', $url);
    }

    /**
     * Block execution until the scan of a project is finished.
     *
     * @param $pid int Project id
     * @param $wait_time int Optional time out in seconds
     * @param $sleep_time int Sleep time between status fetches
     */
    public function blockUntilFinished($pid, $wait_time = null, $sleep_time = 5)
    {
        for ($iteration = 0;; $iteration++) {
            $status = $this->getProjectStatus($pid);

            if (($status['phase'] == 0) && ($status['percent'] == 100)) {
                break;
            } else if (!is_null($wait_time) && $iteration > ($wait_time / $sleep_time)) {
                throw new \Exception('Scan did not finish in time');
            }

            sleep($sleep_time);
        }
    }
}

/**
 * @mainpage RIPS API Client Documentation
 *
 * This PHP library provides an object-oriented interface for the RIPS API. It is a simple
 * wrapper around libcurl and takes care of everything important that is required to
 * use the API, e.g., correct management of cookies.
 */
