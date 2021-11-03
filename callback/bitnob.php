<?php
use WHMCS\Database\Capsule;
/**
 * WHMCS Open Node Payment Callback File
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$data = file_get_contents('php://input');
$obj = json_decode($data);
logTransaction($gatewayParams['name'], $data, 'Log');
if ($obj->event == 'checkout.received.paid') {
    try {
        $bitnobid = $obj->data->id;
        $token = Capsule::table('bitnob_payments')->where('bitnobid', $bitnobid)->first();
        // print_r($token); exit();
        if (empty($token)) {
            throw new Exception('Order has Bitnob ID associated');
        }
        $url = "https://api.bitnob.co/api/v1/transactions/".$obj->data->transactions[0]->id;
        if ($gatewayParams['testMode'] == 'on') {
            $url = "https://sandbox.bitnob.co/api/v1/transactions/".$obj->data->transactions[0]->id;
        }
        $apikey = $gatewayParams['testMode'] == 'on' ? $gatewayParams['testapikey'] : $gatewayParams['apikey'];
        $resp = bitnob_sendDataCallback($url,$apikey);
        
        $objdata = json_decode($resp);
        if($objdata->status != true){
            throw new Exception('Order has Bitnob ID associated');
        }
        $invoiceId = $token->invoiceid;
        // print_r($objdata->data->transactions); exit();
        // foreach($objdata->data->transactions as $trans){
            $invid = preg_replace('/[^0-9]/', '', $objdata->data->description);
            if($invid == $invoiceId){
                $transactionId = $objdata->data->id;
                $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
                checkCbTransID($transactionId);
                logTransaction($gatewayParams['name'], $resp, 'Success');
                addInvoicePayment(
                    $invoiceId,
                    $transactionId,
                    $obj->data->amount,
                    $paymentFee,
                    $gatewayModuleName
                );
            }
       // }
        echo "ok";
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
}

function bitnob_sendDataCallback($url,$apikey)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        // CURLOPT_POSTFIELDS => $data,
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
