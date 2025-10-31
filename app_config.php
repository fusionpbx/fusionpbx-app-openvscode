<?php

//application details
$apps[$x]['name']                 = "OpenVSCode Editor";
$apps[$x]['uuid']                 = "d984f335-f38f-4a93-a045-6fd00145a7de";
$apps[$x]['category']             = "Apps";
$apps[$x]['subcategory']          = "";
$apps[$x]['version']              = "1.0";
$apps[$x]['license']              = "MIT";
$apps[$x]['url']                  = "https://github.com/gitpod-io/openvscode-server";
$apps[$x]['description']['en-us'] = "Browser-based VS Code (OpenVSCode Server).";

//permission details
$y                                           = 0;
$apps[$x]['permissions'][$y]['name']         = "openvscode_view";
$apps[$x]['permissions'][$y]['menu']['uuid'] = "8bddcd8f-4440-4179-9e9e-3a1791708b50";
$apps[$x]['permissions'][$y]['groups'][]     = "superadmin";
