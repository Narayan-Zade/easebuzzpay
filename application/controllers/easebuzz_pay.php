<?php
    // include file
    
class easebuzz_pay extends CI_Controller
{
    
    public function __construct()
     {
         parent::__construct();
            $this->load->library('Easebuzz');
            $this->load->library('Payment');
     }

    /*
    * Create object for call easepay payment gate API and Pass required data into constructor.
    * Get API response.
    *  
    * param string $_GET['apiname'] - holds the API name.
    * param  string $MERCHANT_KEY - holds the merchant key.
    * param  string $SALT - holds the merchant salt key.
    * param  string $ENV - holds the env(enviroment).
    * param  string $_POST - holds the form data.
    *
    * ##Return values
    *   
    * - return array ApiResponse['status']== 1 successful.
    * - return array ApiResponse['status']== 0 error.
    *
    * @param string $_GET['apiname'] - holds the API name.
    * @param  string $MERCHANT_KEY - holds the merchant key.
    * @param  string $SALT - holds the merchant salt key.
    * @param  string $ENV - holds the env(enviroment).
    * @param  string $_POST - holds the form data.
    *
    * @return array ApiResponse['status']== 1 successful. 
    * @return array ApiResponse['status']== 0 error. 
    *
    */
    function index()
    {
        if(!empty($_POST) && (sizeof($_POST) > 0)){
       // print_r($_POST);exit;
        /*
        * There are three approch to call easebuzz API.
        *
        * 1. using hidden api_name which is $_POST from form.
        * 2. using pass api_name into URL.
        * 3. using extract html file name then based on file name call API.
        *
        */

        // first way
        $apiname = trim(htmlentities($_POST['api_name'], ENT_QUOTES));

        // second way
        //$apiname = trim(htmlentities($_GET['api_name'], ENT_QUOTES));

        // third way
        // $url_link = $_SERVER['HTTP_REFERER'];
        // $apiname = explode('.', ( end( explode( '/',$url_link) ) ) )[0];
        // $apiname = trim(htmlentities($apiname, ENT_QUOTES));


        /*
        * Based on API call change the Merchant key and salt key for testing(initiate payment).
        */
        $MERCHANT_KEY = "A92FULVHOP";
        $SALT = "GRE7GR7J14";
        $ENV = "test";    // setup test enviroment (testpay.easebuzz.in). 
        //$ENV = "prod";   // setup production enviroment (pay.easebuzz.in).
        
        $PaymentObj = new Payment();

        if($apiname === "initiate_payment"){ 

            /*  Very Important Notes
            * 
            * Post Data should be below format.
            *
                Array ( [txnid] => T3SAT0B5OL [amount] => 100.0 [firstname] => jitendra [email] => test@gmail.com [phone] => 1231231235 [productinfo] => Laptop [surl] => http://localhost:3000/response.php [furl] => http://localhost:3000/response.php [udf1] => aaaa [udf2] => aa [udf3] => aaaa [udf4] => aaaa [udf5] => aaaa [address1] => aaaa [address2] => aaaa [city] => aaaa [state] => aaaa [country] => aaaa [zipcode] => 123123 ) 
            */
            $PaymentObj->initiate_payment($_POST, $MERCHANT_KEY, $SALT, $ENV);

        }
    }
   
    } 

    public function success(){
        $SALT = "GRE7GR7J14";
       $easebuzzObj = new Easebuzz($MERCHANT_KEY = null, $SALT, $ENV = null);
         $result = $easebuzzObj->easebuzzResponse( $_POST );
        echo "Your payment Successfully done ";
             print_r($result);



    }
    public function Failed(){
        echo "wrong something  since payment unsuccess";
    }
    /*
    *  Show All API Response except initiate Payment API
    */
    function easebuzzAPIResponse($data){
        print_r($data);
    }

}
?>