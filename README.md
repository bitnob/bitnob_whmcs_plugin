#  Bitnob - Accept Bitcoin Payments (On-chain & Lightning) 

##  Bitnob WHMCS Module
Download [HERE](https://marketplace.whmcs.com/product/6136-bitnob-accept-bitcoin-payments-on-chain-lightning)


This payment gateway module supports the following functions and features:
- Checkout for store payments
- On-chain bitcoin payments
- Lightning bitcoin payments
## How to get API Keys
- Register at https://app.bitnob.co
- Navigate to Settings > Accounts > API Keys & Webhooks
## Update Callback
Change callback to `https://{your-domain}/modules/gateways/callback/bitnob.php`

Enable The module, add product to chart and test the payment

## WHMCS Admin Instruction 

1) Goto "Payment Gateways" in your whmcs admin panel.
2) Click on "Manage Existing Gateways" tab.
3) You can find the "Bitnob" module.
4) Add API Key or API Test Key and save it.
5) Congratulations, We are done with Bitnob setup. 


## Local Installation Guide
To Set-up the BitNob please follow the instrections.
1. Extract the zip file you have downloaded.
2. In the root of this bitnob folder you can see the bitnob.php file
3. Move this file inside your "/modules/gateways" dir.
4. Now inside "bitnob" folder there is "callback" folder which contains another "bitnob.php", move this file into your "/clientarea/modules/gateways/callback" dir.
5. There is also a file inside "hooks" folder, Move this file onto your whmcs's folder "/includes/hooks".
6. Create a folder into your "/modules/gateways" named "bitnob" and place logo.png file in to it. 
