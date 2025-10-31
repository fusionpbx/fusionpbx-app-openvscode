<?php

require_once dirname(__DIR__, 2) . '/resources/require.php';

if (permission_exists('openvscode_view')) {
	//pass to openvscode
	header('Content-Length: 0');
	exit;
}

http_response_code(401);
exit;
