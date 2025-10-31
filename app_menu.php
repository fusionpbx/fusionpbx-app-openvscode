<?php

$apps[$x]['menu'][0]['title']['en-us'] = "OpenVSCode Editor";
$apps[$x]['menu'][0]['uuid']           = "8bddcd8f-4440-4179-9e9e-3a1791708b50";
$apps[$x]['menu'][0]['parent_uuid']    = "594d99c5-6128-9c88-ca35-4b33392cec0f"; // Advanced Menu
$apps[$x]['menu'][0]['category']       = "external"; // Open in new tab
$apps[$x]['menu'][0]['path']           = "/app/openvscode/"; // Trigger Nginx proxy
$apps[$x]['menu'][0]['groups'][]       = "superadmin";
