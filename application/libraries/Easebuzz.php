<?php
class easebuzz_payment
{
    /*
    * initiate_payment method initiate payment and call dispay the payment page.
    */
    function initiate_payment($params, $merchant_key, $salt, $env){
        $result = $this->_payment($params, $merchant_key, $salt, $env);
        $this->_paymentResponse((object)$result); 
    }


    /*
    * _payment method use for initiate payment.
    * @return array $pay_result - holds the response with status and data.
    * @return integer status = 1 successful.
    * @return integer status = 0 error.
    *
    */
    function _payment($params, $merchant_key, $salt, $env){

        $postedArray = '';
        $URL = '';

        // argument validation
        $argument_validation = $this->_checkArgumentValidation($params, $merchant_key, $salt, $env);
        if(is_array($argument_validation) && $argument_validation['status'] === 0){
            return $argument_validation;
        }

        // push merchant key into $params array.
        $params['key'] =  $merchant_key;

        // remove white space, htmlentities(converts characters to HTML entities), prepared $postedArray.
        $postedArray = $this->_removeSpaceAndPreparePostArray($params);

        // empty validation
        $empty_validation = $this->_emptyValidation($postedArray, $salt);
        if(is_array($empty_validation) && $empty_validation['status'] === 0){
            return $empty_validation;
        }

        // check amount should be float or not 
        if(preg_match("/^([\d]+)\.([\d]?[\d])$/", $postedArray['amount'])){
            $postedArray['amount'] = (float)$postedArray['amount'];
        }

        // type validation
        $type_validation = $this->_typeValidation($postedArray, $salt, $env);
        if($type_validation !== true){
            return $type_validation;
        }

        // again amount convert into string
        $diff_amount_string = abs( strlen($params['amount']) - strlen("".$postedArray['amount']."") );
        $diff_amount_string = ($diff_amount_string === 2) ? 1 : 2;
        $postedArray['amount'] = sprintf("%.".$diff_amount_string."f", $postedArray['amount']);

        // email validation
        $email_validation = $this->_email_validation($postedArray['email']);
        if($email_validation !== true)
            return $email_validation;

        // get URL based on enviroment like ($env = 'test' or $env = 'prod')
        $URL = $this->_getURL($env);

        // process to start pay
        $pay_result = $this->_pay($postedArray, $salt, $URL);
        
        return $pay_result;        
    }


    /*
    *  _checkArgumentValidation method Check number of Arguments Validation. Means how many arguments submitted 
    *  from form and verify with API documentation.
    * @return interger 1 number of  arguments match. 
    * @return array status = 0 number of arguments mismatch.
    *  
    */
    function _checkArgumentValidation($params, $merchant_key, $salt, $env){
        $args = func_get_args();
        $argsc = count($args);
        if($argsc !== 4){
            return array(
                'status' => 0,
                'data' => 'Invalid number of arguments.'
            );
        }
        return 1;
    }
 

    /*  
    *  _removeSpaceAndPreparePostArray method Remove white space, converts characters to HTML entities 
    *   and prepared the posted array.
    * @return array $temp_array - holds the all posted value after removing space.
    *
    */
    function _removeSpaceAndPreparePostArray($params){
        $temp_array = array(
            'key' => trim( htmlentities($params['key'], ENT_QUOTES) ),
            'txnid' => trim( htmlentities($params['txnid'], ENT_QUOTES) ),
            'amount' => trim( htmlentities($params['amount'], ENT_QUOTES) ),
            'firstname' => trim( htmlentities($params['firstname'], ENT_QUOTES) ),
            'email' => trim( htmlentities($params['email'], ENT_QUOTES) ),
            'phone' => trim( htmlentities($params['phone'], ENT_QUOTES) ),
            'productinfo' =>trim( htmlentities($params['productinfo'], ENT_QUOTES) ),
            'surl' => trim( htmlentities($params['surl'], ENT_QUOTES) ),
            'furl' => trim( htmlentities($params['furl'], ENT_QUOTES) )
            
        );
        return $temp_array;
    }


    /*
    * _emptyValidation method check empty validation for Mandatory Parameters.
    
    * @return boolean true - all $params Mandatory parameters is not empty.
    * @return array with status and data - $params parameters or $salt are empty.
    * 
    */
    function _emptyValidation($params, $salt){
        $empty_value = false;
        if(empty($params['key'])) 
            $empty_value = 'Merchant Key';

        if(empty($params['txnid'])) 
            $empty_value = 'Transaction ID';

        if(empty($params['amount'])) 
            $empty_value = 'Amount';
            
        if(empty($params['firstname'])) 
            $empty_value = 'First Name';

        if(empty($params['email'])) 
            $empty_value ='Email';

        if(empty($params['phone'])) 
            $empty_value = 'Phone';

        if(empty($params['productinfo'])) 
            $empty_value ='Product Infomation';
            
        if(empty($params['surl'])) 
            $empty_value ='Success URL';
        
        if(empty($params['furl'])) 
            $empty_value ='Failure URL';

        if(empty($salt))
            $empty_value = 'Merchant Salt Key';

        if($empty_value !== false){
            return array(
                'status' => 0,
                'data' => 'Mandatory Parameter '.$empty_value.' can not empty'
            );
        }
        return true;
    }


    /*
    * _typeValidation method check type validation for field.
    *
    * @return boolean true - all params parameters type are correct.
    * @return array with status and data - params parameters type mismatch.
    * 
    */
    function _typeValidation($params, $salt, $env){
        $type_value = false;
        if(!is_string($params['key']))
            $type_value = "Merchant Key should be string";

        if(!is_float($params['amount']))
            $type_value = "The amount should float up to two or one decimal.";

        if(!is_string($params['productinfo']))
            $type_value =  "Product Information should be string";

        if(!is_string($params['firstname']))
            $type_value =  "First Name should be string";
        
        if(!is_string($params['phone']))
            $type_value = "Phone Number should be number";

        if(!is_string($params['email']))
            $type_value = "Email should be string";

        if(!is_string($params['surl']))
            $type_value = "Success URL should be string";
        
        if(!is_string($params['furl']))
            $type_value = "Failure URL should be string";

        if($type_value !== false){
            return array(
                'status' => 0,
                'data' => $type_value
            );
        }
        return true;
    }


    /*
    * _email_validation method check email format validation
    * 
    * @return boolean true - email format is correct.
    * @return array with status and data - email format is incorrect.
    * 
    */
    function _email_validation($email){
        $email_regx = "/^([\w\.-]+)@([\w-]+)\.([\w]{2,8})(\.[\w]{2,8})?$/";
        if(!preg_match($email_regx, $email)){
            return array(
                'status' => 0,
                'data' => 'Email invalid, Please enter valid email.'
            );
        }
        return true;
    }


    /*
    * _getURL method set based on enviroment ($env = 'test' or $env = 'prod') and 
    * generate url link.
    *  
    * @return string $url_link - holds the full URL.
    *
    */
    function _getURL($env){
        $url_link = '';
        switch($env){
            case 'test' :
                $url_link = "https://testpay.easebuzz.in/";
                break;
            case 'prod' :
                $url_link = 'https://pay.easebuzz.in/';
                break;
            default :
                $url_link = "https://testpay.easebuzz.in/";
        }
        return $url_link;
    }


    /*
    * _pay method initiate payment will be start from here.
    *
    * @return array with status and data - holds the details
    * @return integer status = 0 means error.
    * @return integer status = 1 means success and go the url link.  
    *   
    */
    function _pay($params_array, $salt_key, $url){
        $hash_key = '';

        // generate hash key and push into params array.
        $hash_key = $this->_getHashKey($params_array, $salt_key);
        $params_array['hash'] = $hash_key;

        // call curl_call() for initiate pay link
        $curl_result = $this->_curlCall( $url.'payment/initiateLink', http_build_query($params_array) );
        
        $accesskey = ($curl_result->status === 1) ? $curl_result->data : null;

        if( empty($accesskey) ){
           return $curl_result;
        }else{
            $curl_result->data = $url.'pay/'.$accesskey;
            return $curl_result;
        }
    }


    /*
    * _getHashKey method generate Hash key based on the API call (initiatePayment API).
    * @return string $hash - holds the generated hash key.  
    *
    */
    function _getHashKey($posted, $salt_key){
        $hash_sequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";

        // make an array or split into array base on pipe sign.
        $hash_sequence_array = explode( '|', $hash_sequence );
        $hash = null;

        // prepare a string based on hash sequence from the $params array.
        foreach($hash_sequence_array as $value ) {
            $hash .= isset($posted[$value]) ? $posted[$value] : '';
            $hash .= '|';
        }

        $hash .= $salt_key;

        // generate hash key using hash function(predefine) and return
        return strtolower( hash('sha512', $hash) );
    }


    /*
    *  _curlCall method call CURL for initialized payment link.
    
    * @return array with curl_status and data - holds the details.
    * @return integer curl_status = 0 means error.
    * @return integer curl_status = 1 means success and go the url link.
    *
    * ##Method call
    * - curl_init() - Initializes a new session and return a cURL.
    * - curl_setopt_array() - Set multiple options for a cURL transfer.
    * - curl_exec() - Perform a cURL session.
    * - curl_errno() -  Return the last error number.
    * - curl_error() - Return a string containing the last error for the current session.
    *
    * ##Used value
    * - curl_status => 0 : means failure.
    * - curl_status => 1 : means Success.
    *
    */
    function _curlCall($url, $params_array){
        // Initializes a new session and return a cURL.
        $cURL = curl_init();

        // Set multiple options for a cURL transfer.
        curl_setopt_array( 
            $cURL, 
            array ( 
                CURLOPT_URL => $url, 
                CURLOPT_POSTFIELDS => $params_array, 
                CURLOPT_POST => true, 
                CURLOPT_RETURNTRANSFER => true, 
                CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36', 
                CURLOPT_SSL_VERIFYHOST => 0, 
                CURLOPT_SSL_VERIFYPEER => 0 
            ) 
        );

        // Perform a cURL session
        $result = curl_exec($cURL);

        // check there is any error or not in curl execution.
        if( curl_errno($cURL) ){
            $cURL_error = curl_error($cURL);
            if( empty($cURL_error) )
                $cURL_error = 'Server Error';
            
            return array(
                'curl_status' => 0, 
                'error' => $cURL_error
            );
        }

        $result = trim($result);
        $result_response = json_decode($result);

        return $result_response;
    }
    
    
    /*
    * _paymentResponse method show response after API call.
    *
    * @return string URL $result->status = 1 - means go to easebuzz page.
    * @return string URL $result->status = 0 - means error
    *
    */
    function _paymentResponse($result){

        if ($result->status === 1){
            // first way
            header( 'Location:' . $result->data );

            // second way
            // echo "
            //    <script type='text/javascript'>
            //           window.location ='".$result->data."'
            //    </script>
            // ";

            exit(); 
        }else{
            //echo '<h3>'.$result['data'].'</h3>';
            print_r(json_encode($result));
        }
    }


    /*
    * response method verify API response is acceptable or not and returns the response object.
   
    * @return array with status and data - holds the details.
    * @return integer status = 0 means error.
    * @return integer status = 1 means success. 
    *
    */
    function response($response_params, $salt_key){

        // check return response params is array or not
        if(!is_array($response_params) || count($response_params) === 0 ){
            return array(
                'status' => 0,
                'data' => 'Response params is empty.'
            );
        }

        // remove white space, htmlentities, prepared $easebuzzPaymentResponse.
        $easebuzzPaymentResponse = $this->_removeSpaceAndPrepareAPIResponseArray($response_params);

        // empty validation 
        $empty_validation = $this->_emptyValidation($easebuzzPaymentResponse, $salt_key);
        if(is_array($empty_validation) && $empty_validation['status'] === 0){
            return $empty_validation;
        }

        // empty validation for response params status
        if( empty($easebuzzPaymentResponse['status']) ){
            return array(
                'status' => 0,
                'data' => 'Response status is empty.'
            );
        }

        // check response the correct or not
        $response_result = $this->_getResponse($easebuzzPaymentResponse, $salt_key);

        return $response_result;
    }


    /*
    *  _removeSpaceAndPrepareAPIResponseArray method Remove white space, converts characters to HTML entities 
    *   and prepared the posted array.
   
    * @return array $temp_array - holds the all posted value after removing space.
    *
    */
    function _removeSpaceAndPrepareAPIResponseArray($response_array){
        $temp_array = array();
        foreach( $response_array as $key => $value ){
            $temp_array[$key] = trim( htmlentities($value, ENT_QUOTES) );
        }
        return $temp_array;
    }


    /*
    * _getResponse check response is correct or not.
    *
    
    * @return array with status and data - holds the details.
    * @return integer status = 0 means error.
    * @return integer status = 1 means success.
    *
    */
    function _getResponse($response_array, $s_key){
       
        // reverse hash key for validation means response is correct or not.
        $reverse_hash_key = $this->_getReverseHashKey($response_array, $s_key);

        if($reverse_hash_key === $response_array['hash']){
            switch ($response_array['status']) {
                case 'success' :
                    return array(
                        'status' => 1,
                        'url' => $response_array['surl'],
                        'data' => $response_array
                    );
                    break;
                case 'failure' :
                    return array(
                        'status' => 1,
                        'url' => $response_array['furl'],
                        'data' => $response_array
                    );
                    break;
                default :
                    return array(
                        'status' => 1,
                        'data' => $response_array
                    );
            }
        }else{
            return array(
                'status' => 0,
                'data' => 'Hash key Mismatch'
            );
        }
    }


    /*
    * _getReverseHashKey to generate Reverse hash key for validation
    *
    * reverse hash format (hash sequence) :
    *  $reverse_hash = salt|status|udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
    * 
    * status in $reverse_hash means => it will the response status which is getting from the response. 
    * @params string $reverse_hash_sequence - holds the format of reverse hash key (sequence).
    * @params array $response_array - holds the response array.
    * @params string $s_key - holds the merchant salt key.
    *
    * @return string  $reverse_hash - holds the generated reverse hash key.
    *
    */
    function _getReverseHashKey($response_array, $s_key){
        $reverse_hash_sequence = "udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key";

        // make an array or split into array base on pipe sign.
        $reverse_hash = "";
        $reverse_hash_sequence_array = explode( '|', $reverse_hash_sequence );
        $reverse_hash .= $s_key. '|' . $response_array['status'];

        // prepare a string based on reverse hash sequence from the $response_array array.
        foreach($reverse_hash_sequence_array as $value ) {
            $reverse_hash .= '|';
            $reverse_hash .= isset($response_array[$value]) ? $response_array[$value] : '';
        }

        // generate reverse hash key using hash function(predefine) and return
        return strtolower( hash('sha512', $reverse_hash) );        
    }
}
?>


