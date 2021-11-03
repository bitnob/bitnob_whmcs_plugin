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
        'DisplayName' => 'Bitnob',
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
    // print_r($params); exit();   
    $langPayNow = $params['langpaynow'];
    if ($_POST['bitnob'] == 'bitnob') {
        $oldinvoice = Capsule::table('bitnob_payments')->where('invoiceid', $params['invoiceid'])->first();
        if ($oldinvoice) {
            // Capsule::table('bitnob_payments')->where('invoiceid', $params['invoiceid'])->delete();
            // header('location: https://checkout.bitnob.co/app/' . $oldinvoice->bitnobid . '/');
            // exit();
        }
        $systemUrl = $params['systemurl'];
        $returnUrl = $params['returnurl'];
        $langPayNow = $params['langpaynow'];
        $moduleName = $params['paymentmethod'];
        $env = $params['testMode'] == 'on' ? 'sandbox' : 'production'; // 
        $apikey = $params['testMode'] == 'on' ? $params['testapikey'] : $params['apikey'];
        $urlconv = "https://api.bitnob.co/api/v1/wallets/convert-currency/";
        if ($env == 'sandbox') {
            $urlconv = "https://sandbox.bitnob.co/api/v1/wallets/convert-currency/";
        }
        $currencyconv = bitnob_sendData($urlconv, json_encode(["conversion" => strtoupper($params['currency']) . "_BTC", "amount" => $params['amount']]), $apikey);
        $satoshi = json_decode($currencyconv, true);
        if ($satoshi['status'] == 1) {
            $url = "https://api.bitnob.co/api/v1/checkout/";
            if ($env == 'sandbox') {
                $url = "https://sandbox.bitnob.co/api/v1/checkout/";
            }
            $amount = $satoshi['data'];
            $postval = array(
                // 'invoiceid'         => $params['invoiceid'].'-'.rand(100,999),
                'callbackUrl'      => rtrim($systemUrl,'/') . '/modules/gateways/callback/' . $moduleName . '.php',
                'successUrl'       => $returnUrl,
                'description'      => $params['description'],
                'satoshis' => round(($amount) * (pow(10, 8)), 6)
            );
            // echo json_encode($postval);
            // print_r($postval);
            $resp = bitnob_sendData($url, json_encode($postval), $apikey);
            $array = json_decode($resp, true);
            // print_r($array); exit();
            if ($array['status'] == 1) {
                Capsule::table('bitnob_payments')->insert([
                    'invoiceid' => $params['invoiceid'],
                    'bitnobid' => $array['data']['id'],
                    'apiresponse' => $resp
                ]);
                header('location: https://checkout.bitnob.co/app/' . $array['data']['id'] . '/');
                exit();
            } else {
                $error = $array['message'];
            }
        } else {
            $error = implode(" ", $satoshi['message']);
        }
    }
    $htmlOutput = '<form method="post" action="">';
    $htmlOutput .= '<input type="hidden" name="bitnob" value="bitnob" />';
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form> <br><h2 style="color:red;">' . $error . '</h2>';
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
