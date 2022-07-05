<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 * @return array
 */
function bitnob_MetaData()
{
    return array(
        'DisplayName' => 'Bitnob Test',
        'APIVersion' => '1.0',
    );
}

/**
 * Define gateway configuration options.
 * @return array
 */
function bitnob_config()
{
    if (!Capsule::schema()->hasTable('bitnob_payments')) {
        Capsule::schema()->create(
            'bitnob_payments',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->string('invoiceid');
                $table->string('bitnobid');
                $table->string('apiresponse');
                $table->timestamps();
            }
        );
    }
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Bitnob',
        ),
        // a text field type allows for single line text input
        'apikey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your API Key',
        ),
        'testapikey' => array(
            'FriendlyName' => 'Test API Key',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'sk.8fcdc.a23474b7d2612534df',
            'Description' => 'Enter your Test API Key',
        ),
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ),
    );
}

/**
 * Payment link.
 * @return string
 */
function bitnob_link($params)
{ 
    //echo "<pre>";
    //print_r($params); exit();   
    $langPayNow = $params['langpaynow'];
    $moduleName = $params['paymentmethod'];
    $systemUrl = $params['systemurl'];
    $apikey = $params['testMode'] == 'on' ? $params['testapikey'] : $params['apikey'];
    $env = $params['testMode'] == 'on' ? 'sandbox' : 'production'; // 
    $callbackUrl =  rtrim($systemUrl, '/') . '/modules/gateways/callback/' . $moduleName . '.php';
    $returnUrl = $params['returnurl'];
    $description = 'Bitcoin Payment for Order No. (' . $params['invoiceid'] . ') . Powered by Bitnob';

    $htmlOutput = '<form method="post" action="" onsubmit="getFormData(event)" >';
    $htmlOutput .= '<input type="hidden" name="bitnob" value="bitnob" />';
    $htmlOutput .= '<input type="hidden" placeholder="Enter Email" value="'.$params['clientdetails']['email'].'" name="email" id="bitnob-email" required="">';
    $htmlOutput .= '<input type="submit" style="display:none" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form><div class="text-center mt-5"><img src="'.$params['systemurl'].'/modules/gateways/bitnob/logo.png" width="250" /></div> <br><h2 style="color:red;">' . $error . '</h2>';
    $htmlOutput .= '<script>
                    console.log("i am included");
                    function pay() {
                        var data = {
                            publicKey: "'.$apikey.'",
                            amount: '.(int)$params['amount'].',
                            customerEmail: "'.$params['clientdetails']['email'].'",
                            notificationEmail: "'.$params['clientdetails']['email'].'",
                            description: "'.$description.'",
                            currency: "'.$params['currency'].'",
                            reference: "'.$params['invoiceid'].'-'.rand(10,99999).'",
                            callbackUrl: "'.$callbackUrl.'",
                            successUrl: "'.$returnUrl.'",
                        };
                        window.initializePayment(data, "'.$env.'");
                    }

                    function getFormData(e) {
                        e.preventDefault();
                        pay();
                    }
                </script>';
    return $htmlOutput;
}

function bitnob_sendData($url, $data, $apikey)
{
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json'",
            "authorization: Bearer " . $apikey,
            "content-type: application/json",
        ),
    ));
    $response = curl_exec($curl);
 
    $err = curl_error($curl);
 
    curl_close($curl);
    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return  $response;
    }
}
