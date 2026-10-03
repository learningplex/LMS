<?php

// 1. Nginx preserves the original clean URL in this header.
// Fall back to REQUEST_URI for direct access.
$requesturi = $_SERVER['HTTP_X_CUSTOMCLEANURL_URI'] ?? $_SERVER['REQUEST_URI'];

// 2. If HTTP_X_CUSTOMCLEANURL_URI was populated from Nginx $uri (path only without query string),
// re-attach the original query string so parameters like ?id=... are never lost.
if (!str_contains($requesturi, '?') && !empty($_SERVER['QUERY_STRING'])) {
    $requesturi .= '?' . $_SERVER['QUERY_STRING'];
}

// 3. Define NO_OUTPUT_BUFFERING early for scripts that require streaming output.
// This MUST happen before config.php is required.
$requestpathonly = parse_url($requesturi, PHP_URL_PATH) ?: $requesturi;
if (str_contains($requestpathonly, '/course/delete.php') || str_contains($requestpathonly, '/backup/')) {
    if (!defined('NO_OUTPUT_BUFFERING')) {
        define('NO_OUTPUT_BUFFERING', true);
    }
}

// Prevent route.php from loading itself recursively.
if ($requestpathonly === '/local/customcleanurl/route.php') {
    error_log('CUSTOMCLEANURL DEBUG: route.php called itself - stopping recursion');
    http_response_code(500);
    exit('CustomCleanURL route recursion detected');
}

require_once(__DIR__ . '/../../../config.php');

// Resolve the clean URL.
$responsedata = \local_customcleanurl\local\helper::check_requesturl($requesturi);

// Debug logging.
error_log('CUSTOMCLEANURL DEBUG requesturi=' . $requesturi);
error_log('CUSTOMCLEANURL DEBUG method=' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
error_log('CUSTOMCLEANURL DEBUG urltype=' . ($responsedata['urltype'] ?? ''));
error_log('CUSTOMCLEANURL DEBUG moodleurl=' . ($responsedata['moodleurl'] ?? ''));
error_log('CUSTOMCLEANURL DEBUG filepath=' . ($responsedata['filepath'] ?? ''));

if (!empty($responsedata['status'])) {

    $urltype = $responsedata['urltype'] ?? '';
    $moodleurl = $responsedata['moodleurl'] ?? '';
    $filepath = $responsedata['filepath'] ?? '';
    $param = $responsedata['param'] ?? [];

    /*
     * Custom Clean URL route test.
     */
    if ($urltype === 'customcleanurl_routetest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => true]);
        exit;
    }

    /*
     * Some Moodle module URLs need to be redirected
     * to their original Moodle URL.
     */
    $redirecturltype = [
        'mod_page_view',
        'mod_resource_view',
        'mod_folder_view',
        'mod_url_view',
        'mod_url_index',
        'mod_imscp_view',
        'mod_bigbluebuttonbn_view',
    ];

    if (!empty($moodleurl) && in_array($urltype, $redirecturltype, true)) {
        redirect($moodleurl);
        exit;
    }

    /*
     * Handle 404 through the plugin's 404 handler.
     */
    if ($urltype === '404') {
        header('HTTP/1.0 404 Not Found');
        http_response_code(404);

        $_SERVER['REDIRECT_STATUS'] = '404';

        if (!empty($filepath) && is_file($filepath)) {
            chdir(dirname($filepath));
            require($filepath);
        }

        exit;
    }

    /*
     * For clean course URLs, tell Moodle what the internal
     * Moodle URL is.
     */
    if ($urltype === 'customcleanurl_courseurl' && !empty($moodleurl)) {
        $PAGE->set_url($moodleurl);
    }

    /*
     * Pass resolved parameters to Moodle.
     */
    foreach ($param as $key => $value) {
        $_GET[$key] = $value;
    }

    /*
     * Execute the resolved Moodle PHP file.
     */
    if (!empty($filepath) && is_file($filepath)) {

        // Extra safety check against self-inclusion.
        $currentroute = realpath(__FILE__);
        $targetfile = realpath($filepath);

        if ($targetfile !== false && $currentroute === $targetfile) {
            error_log(
                'CUSTOMCLEANURL DEBUG: prevented self-inclusion of route.php'
            );

            http_response_code(500);
            exit('CustomCleanURL route recursion detected');
        }

        global $FULLME, $ME, $SCRIPT, $FULLSCRIPT;

        if (!empty($moodleurl)) {
            $resolvedurl = new moodle_url($moodleurl);
            $resolvedpath = $resolvedurl->get_path(false);

            $FULLME = $resolvedurl->raw_out(false);
            $ME = $resolvedurl->out_as_local_url(false);
            $SCRIPT = $resolvedpath;
            $FULLSCRIPT = (new moodle_url($resolvedpath))->raw_out(false);

            $_SERVER['SCRIPT_NAME'] = $resolvedpath;
            $_SERVER['PHP_SELF'] = $resolvedpath;
        }

        chdir(dirname($filepath));
        require($filepath);
        exit;
    }
}

if (!empty($responsedata['message'])) {
    echo $responsedata['message'];
} else {
    http_response_code(404);
    echo 'Page not found';
}

exit;
