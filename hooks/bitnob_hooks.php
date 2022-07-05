<?php
add_hook('ClientAreaPage', 1, function($vars) {
    global $smarty;
    foreach ($vars['gateways'] as $key => $value) {
        // print_r($value);
        if($value['sysname'] == 'bitnob'){
            $vars['gateways']['bitnob']['name'] = $vars['gateways']['bitnob']['name'].'<br><img style="width: 209px" src="modules/gateways/bitnob/logo.png">';
        }        
    }
    // print_r($vars['gateways']);exit();
    return $vars;
});


function addBinobScript($vars) {

    return <<<HTML
    <script src="https://www.js.bitnob.co/v1/inline.js"></script>
    
    HTML;
    
}
    
add_hook("ClientAreaHeadOutput", 1, "addBinobScript");
