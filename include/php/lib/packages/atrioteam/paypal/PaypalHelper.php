<?php
/**
 * 
 * @package AtrioShop
 * @author Emanuele Fornasier, Jacopo Viscuso, Mauricio Cabral
 * 
 */
namespace AtrioTeam\Paypal;

use Exception;

class PaypalHelper
{

    protected $API_UserName;
    protected $API_Password;
    protected $API_Signature;
    protected $API_Endpoint;
    protected $PAYPAL_URL;


    /**
     * @var testMode numero per negative testing
     * 1 = CallShortcutExpressCheckout (redirect in uscita)
     * 2 = GetShippingDetails (recupero info dopo ritorno)
     * 3 = ConfirmPayment (conferma pagamento dopo ritorno)
     */
    protected $testMode;


    /**
     * @param $testmMode simulazione di errore (v. def variabili)
     */
    public function __construct($API_UserName, $API_Password, $API_Signature, $API_Endpoint, $PAYPAL_URL, $testMode = 0) {
        $this->API_UserName = $API_UserName;
        $this->API_Password = $API_Password;
        $this->API_Signature = $API_Signature;
        $this->API_Endpoint = $API_Endpoint;
        $this->PAYPAL_URL = $PAYPAL_URL;

        $this->USE_PROXY = false;
        $this->version = "2.3";
        $this->PROXY_HOST = '127.0.0.1';
        $this->PROXY_PORT = '808';

        // BN Code  is only applicable for partners
        $this->sBNCode = "PP-ECWizard";

        $this->testMode = $testMode;

    }




    /* An express checkout transaction starts with a token, that
       identifies to PayPal your transaction
       In this example, when the script sees a token, the script
       knows that the buyer has already authorized payment through
       paypal.  If no token was found, the action is to send the buyer
       to PayPal to first authorize payment
       */

    /*   
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose:  Prepares the parameters for the SetExpressCheckout API Call.
    ' Inputs:  
    '       paymentAmount:      Total value of the shopping cart
    '       currencyCodeType:   Currency code value the PayPal API
    '       paymentType:        paymentType has to be one of the following values: Sale or Order or Authorization
    '       returnURL:          the page where buyers return to after they are done with the payment review on PayPal
    '       cancelURL:          the page where buyers return to when they cancel the payment review on PayPal
    '--------------------------------------------------------------------------------------------------------------------------------------------   
    */
    function CallShortcutExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $arrayExtraOptions = array()) 
    {
        //------------------------------------------------------------------------------------------------------------------------------------
        // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
        
        $nvpstr = "&Amt=". $paymentAmount;
        $nvpstr .= "&PAYMENTACTION=" . $paymentType;
        $nvpstr .= "&ReturnUrl=" . $returnURL;
        $nvpstr .= "&CANCELURL=" . $cancelURL;
        $nvpstr .= "&CURRENCYCODE=" . $currencyCodeType;
        $nvpstr .= "&TEMPLATE='TemplateB'";
        
        // TEST DI ERRORE !!!!!
        if ($this->testMode == 1) {
            $nvpstr .= "&MAXAMT=104.00";
        } 

        
        if (count($arrayExtraOptions)) {
            foreach($arrayExtraOptions as $k => $v) {
                $nvpstr .= "&".$k."=".$v;
            }
        }
        
        $_SESSION["currencyCodeType"] = $currencyCodeType;    
        $_SESSION["PaymentType"] = $paymentType;

        //'--------------------------------------------------------------------------------------------------------------- 
        //' Make the API call to PayPal
        //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
        //' If an error occured, show the resulting errors
        //'---------------------------------------------------------------------------------------------------------------
        $resArray=$this->hash_call("SetExpressCheckout", $nvpstr);
        if(!empty($resArray["ACK"]) && strtoupper($resArray["ACK"]) == "SUCCESS")
        {
            $token = urldecode($resArray["TOKEN"]);
            $_SESSION["TOKEN"] = $token;
        } else {
            // Modificato da manu 2/2/2012 per aggiungere informazioni aggiuntive che
            // ci servono nella pagina
            $resArray['API_NAME'] = 'SetExpressCheckout';
        }
           
        return $resArray;
    }

    /*   
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose:  Prepares the parameters for the SetExpressCheckout API Call.
    ' Inputs:  
    '       paymentAmount:      Total value of the shopping cart
    '       currencyCodeType:   Currency code value the PayPal API
    '       paymentType:        paymentType has to be one of the following values: Sale or Order or Authorization
    '       returnURL:          the page where buyers return to after they are done with the payment review on PayPal
    '       cancelURL:          the page where buyers return to when they cancel the payment review on PayPal
    '       shipToName:         the Ship to name entered on the merchant's site
    '       shipToStreet:       the Ship to Street entered on the merchant's site
    '       shipToCity:         the Ship to City entered on the merchant's site
    '       shipToState:        the Ship to State entered on the merchant's site
    '       shipToCountryCode:  the Code for Ship to Country entered on the merchant's site
    '       shipToZip:          the Ship to ZipCode entered on the merchant's site
    '       shipToStreet2:      the Ship to Street2 entered on the merchant's site
    '       phoneNum:           the phoneNum  entered on the merchant's site
    '--------------------------------------------------------------------------------------------------------------------------------------------   
    */
    function CallMarkExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, 
                                      $cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState,
                                      $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum
                                    ) 
    {
        //------------------------------------------------------------------------------------------------------------------------------------
        // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
        
        $nvpstr = "&Amt=". $paymentAmount;
        $nvpstr .= "&PAYMENTACTION=" . $paymentType;
        $nvpstr .= "&ReturnUrl=" . $returnURL;
        $nvpstr .= "&CANCELURL=" . $cancelURL;
        $nvpstr .= "&CURRENCYCODE=" . $currencyCodeType;
        $nvpstr .= "&ADDROVERRIDE=1";
        $nvpstr .= "&SHIPTONAME=" . $shipToName;
        $nvpstr .= "&SHIPTOSTREET=" . $shipToStreet;
        $nvpstr .= "&SHIPTOSTREET2=" . $shipToStreet2;
        $nvpstr .= "&SHIPTOCITY=" . $shipToCity;
        $nvpstr .= "&SHIPTOSTATE=" . $shipToState;
        $nvpstr .= "&SHIPTOCOUNTRYCODE=" . $shipToCountryCode;
        $nvpstr .= "&SHIPTOZIP=" . $shipToZip;
        $nvpstr .= "&PHONENUM=" . $phoneNum;
        
        $_SESSION["currencyCodeType"] = $currencyCodeType;    
        $_SESSION["PaymentType"] = $paymentType;

        //'--------------------------------------------------------------------------------------------------------------- 
        //' Make the API call to PayPal
        //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
        //' If an error occured, show the resulting errors
        //'---------------------------------------------------------------------------------------------------------------
        $resArray=$this->hash_call("SetExpressCheckout", $nvpstr);
        $ack = strtoupper($resArray["ACK"]);
        if($ack == "SUCCESS")
        {
            $token = urldecode($resArray["TOKEN"]);
            $_SESSION["TOKEN"]=$token;
        }
           
        return $resArray;
    }
    
    /*
    '-------------------------------------------------------------------------------------------
    ' Purpose:  Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:  
    '       None
    ' Returns: 
    '       The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '-------------------------------------------------------------------------------------------
    */
    function GetShippingDetails( $token )
    {
        //'--------------------------------------------------------------
        //' At this point, the buyer has completed authorizing the payment
        //' at PayPal.  The function will call PayPal to obtain the details
        //' of the authorization, incuding any shipping information of the
        //' buyer.  Remember, the authorization is not a completed transaction
        //' at this state - the buyer still needs an additional step to finalize
        //' the transaction
        //'--------------------------------------------------------------
       
        //'---------------------------------------------------------------------------
        //' Build a second API request to PayPal, using the token as the
        //'  ID to get the details on the payment authorization
        //'---------------------------------------------------------------------------
        $nvpstr = "&TOKEN=" . $token;
        
        // TEST DI ERRORE !!!!!
        if ($this->testMode == 2) {
            $nvpstr = "&TOKEN=81100";
        }
        

        //'---------------------------------------------------------------------------
        //' Make the API call and store the results in an array.  
        //' If the call was a success, show the authorization details, and provide
        //'     an action to complete the payment.  
        //' If failed, show the error
        //'---------------------------------------------------------------------------
        $resArray = $this->hash_call("GetExpressCheckoutDetails", $nvpstr);
        $ack = strtoupper($resArray["ACK"]);
        if($ack == "SUCCESS")
        {   
            $_SESSION["payer_id"] = $resArray["PAYERID"];
        } else {
            // Modificato da manu 2/2/2012 per aggiungere informazioni aggiuntive che
            // ci servono nella pagina
            $resArray['API_NAME'] = 'GetExpressCheckoutDetails';
        }
        return $resArray;
    }
    
    /*
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose:  Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:  
    '       sBNCode:    The BN code used by PayPal to track the transactions from a given shopping cart.
    ' Returns: 
    '       The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '--------------------------------------------------------------------------------------------------------------------------------------------   
    */
    function ConfirmPayment( $FinalPaymentAmt, $arrayExtraOptions = [] )
    {
        /* Gather the information to make the final call to
           finalize the PayPal payment.  The variable nvpstr
           holds the name value pairs
           */
        

        //Format the other parameters that were stored in the session from the previous calls   
        $token              = urlencode($_SESSION["TOKEN"]);
        $paymentType        = urlencode($_SESSION["PaymentType"]);
        $currencyCodeType   = urlencode($_SESSION["currencyCodeType"]);
        $payerID            = urlencode($_SESSION["payer_id"]);

        $serverName         = urlencode($_SERVER["SERVER_NAME"]);

        $nvpstr  = '&TOKEN=' . $token;
        $nvpstr .= '&PAYERID=' . $payerID . '&PAYMENTACTION=' . $paymentType . '&AMT=' . $FinalPaymentAmt;
        
        // TEST ERRORE!!
        if ($this->testMode == 3) {
            $nvpstr .= '&PAYERID=' . $payerID . '&PAYMENTACTION=' . $paymentType . '&AMT=104.22';
        }
        
        $nvpstr .= '&CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName; 

        // opzioni extra
        if (count($arrayExtraOptions)) {
            foreach($arrayExtraOptions as $k => $v) {
                $nvpstr .= "&".$k."=".$v;
            }
        }

         /* Make the call to PayPal to finalize payment
            If an error occured, show the resulting errors
            */
        $resArray = $this->hash_call("DoExpressCheckoutPayment",$nvpstr);

        /* Display the API response back to the browser.
           If the response from PayPal was a success, display the response parameters'
           If the response was an error, display the errors received using APIError.php.
           */
        $ack = strtoupper($resArray["ACK"]);
        
        // Modificato da manu 2/2/2012 per aggiungere informazioni aggiuntive che
        // ci servono nella pagina
        if($ack !== "SUCCESS")
        {   
            $resArray['API_NAME'] = 'DoExpressCheckoutPayment';
        }
        
        return $resArray;
    }

    /**
      '-------------------------------------------------------------------------------------------------------------------------------------------
      * $this->hash_call: Function to perform the API call to PayPal using API signature
      * @methodName is name of API  method.
      * @nvpStr is nvp string.
      * returns an associtive array containing the response from the server.
      '-------------------------------------------------------------------------------------------------------------------------------------------
    */
    function hash_call($methodName,$nvpStr)
    {

        //setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);    // modificato Jacopo 19/11/2014 da FALSE
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // modificato Jacopo 19/11/2014 da FALSE

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_SSLVERSION,1);         // inserito Jacopo 19/11/2014

        
        //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
       //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
        if($this->USE_PROXY)
            curl_setopt ($ch, CURLOPT_PROXY, $this->PROXY_HOST. ":" . $this->PROXY_PORT); 

        //NVPRequest for submitting to server
        $nvpreq = "METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($this->version) . "&PWD=" . urlencode($this->API_Password) . "&USER=" . urlencode($this->API_UserName) . "&SIGNATURE=" . urlencode($this->API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($this->sBNCode);

        //setting the nvpreq as POST FIELD to curl
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        //getting response from server
        $response = curl_exec($ch);

        //convrting NVPResponse to an Associative Array
        $nvpResArray = $this->deformatNVP($response);
        $nvpReqArray = $this->deformatNVP($nvpreq);
        $_SESSION["nvpReqArray"] = $nvpReqArray;

        if (curl_errno($ch)) 
        {
            // moving to display page to display curl errors
              $_SESSION["curl_error_no"] = curl_errno($ch) ;
              $_SESSION["curl_error_msg"] = curl_error($ch);

              //Execute the Error handling module to display errors. 
        } 
        else 
        {
             //closing the curl
            curl_close($ch);
        }

        return $nvpResArray;
    }

    /*'----------------------------------------------------------------------------------
     Purpose: Redirects to PayPal.com site.
     Inputs:  NVP string.
     Returns: 
    ----------------------------------------------------------------------------------
    */
    function RedirectToPayPal ( $token )
    {
       
        // Redirect to paypal.com here
        $payPalURL = $this->PAYPAL_URL . $token;
        header("Location: ".$payPalURL);
    }

    
    /*'----------------------------------------------------------------------------------
     * This function will take NVPString and convert it to an Associative Array and it will decode the response.
      * It is usefull to search for a particular key and displaying arrays.
      * @nvpstr is NVPString.
      * @nvpArray is Associative Array.
       ----------------------------------------------------------------------------------
      */
    function deformatNVP($nvpstr)
    {
        $intial=0;
        $nvpArray = array();

        while(strlen($nvpstr))
        {
            //postion of Key
            $keypos= strpos($nvpstr,'=');
            //position of value
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval=substr($nvpstr,$intial,$keypos);
            $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] =urldecode( $valval);
            $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
         }
        return $nvpArray;
    }
    
    
    /*'----------------------------------------------------------------------------------
    * Aggiunta da Manu per segnalazione errori API 
    *  ----------------------------------------------------------------------------------
    */
    function ppApi_error($resArray, $redirect) {
        
        $arMsgAdmin = array (
            "SetExpressCheckout"            =>      "Errore nell&rsquo;invio richiesta autorizzazione" 
            ,"GetExpressCheckoutDetails"    =>      "Errore dopo la richiesta PAGAMENTO" 
            ,"DoExpressCheckoutPayment"     =>      "Errore nella conferma di PAGAMENTO" 
        );
        
        //Display a user friendly Error on the page using any of the following error information returned by PayPal
        $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
            
        // Elenco errori
        $msg_errore = $resArray["API_NAME"]." API call failed. <br />";
        $msg_errore .= "Detailed Error Message: " . $ErrorLongMsg.'<br />';
        $msg_errore .= "Short Error Message: " . $ErrorShortMsg.'<br />';
        $msg_errore .= "Error Code: " . $ErrorCode.'<br />';
        $msg_errore .= "Error Severity Code: " . $ErrorSeverityCode.'<br />';

        // AVVISO SEMPRE ADMIN
        return '[PAYPAL]<br />'.$arMsgAdmin[$resArray["API_NAME"]].'<br /><br />Errore: '.$msg_errore;
        
    }




}

