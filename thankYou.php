<?php
	namespace Safaricom\Mpesa;
	include_once("vendor/autoload.php");
	use Symfony\Component\Dotenv\Dotenv;
	require_once 'core/init.php';
	$name = sanitize($_POST['full_name']);
	$email = sanitize($_POST['email']);
	$street=sanitize($_POST['street']);
	$street2=sanitize($_POST['street2']);
	$city=sanitize($_POST['city']);
	$state=sanitize($_POST['state']);
	$zip_code=sanitize($_POST['zip_code']);
	$country=sanitize($_POST['country']);
	$phone = sanitize($_POST["phone"]);
	$amount = sanitize($_POST["amount"]);

class Mpesa{
	// $name = sanitize($_POST['full_name']);
	// $email = sanitize($_POST['email']);
	// $street=sanitize($_POST['street']);
	// $street2=sanitize($_POST['street2']);
	// $city=sanitize($_POST['city']);
	// $state=sanitize($_POST['state']);
	// $zip_code=sanitize($_POST['zip_code']);
	// $country=sanitize($_POST['country']);

	// $phone = sanitize($_POST["phone"]);
	// $amount = sanitize($_POST["amount"]);
	if($cart_id!=''){
		$cartQ=$db->query("SELECT * from cart where id='{$cart_id}'");
		$result=mysqli_fetch_assoc($cartQ);
		$items=json_decode($result['items'],true);
		$i=1;
		$sub_total=0;
		$item_count=0;
	}
	foreach($items as $item){
		$item_id=$item['id'];


//adjust inventory

		$product_id=$item['id'];
		$productQ=$db->query("SELECT * FROM products where id='{$product_id}'");
		$product=mysqli_fetch_assoc($productQ);
		$dif=$product['qty'] - $item['quantity'];
		$db->query("UPDATE products SET qty='{$dif}' where id='{$product_id}'");

		$i++;
		$item_count+=$item['quantity'];
		$sub_total+=$item['quantity'] * $product['price'];

	}
	$tax=TAXRATE * $sub_total;
	$tax=number_format($tax,2);
	$grand_total = $tax+$sub_total;


	//update cart
	$db->query("UPDATE cart SET paid=1 where id='{$cart_id}'");
	$db->query("INSERT into transactions (cart_id,full_name,	email,	street,	street2,	city,	state,	zip_code,	country,	sub_total,	tax,	grand_total) VALUES( '$cart_id', '$name', '$email', '$street','$street2', '$city','$state', '$zip_code', '$country','$sub_total','$tax','$grand_total' )");
	$domain=false;
	setcookie(CART_COOKIE, '', 1,"/",$domain,false);

	include 'includes/head.php';
	include 'includes/navigation.php';
	include 'includes/headerpartial.php';


// <!-- STK push -->

/**
     * Use this function to initiate an STKPush Simulation
     * @param $BusinessShortCode | The organization shortcode used to receive the transaction.
     * @param $LipaNaMpesaPasskey | The password for encrypting the request. This is generated by base64 encoding BusinessShortcode, Passkey and Timestamp.
     * @param $TransactionType | The transaction type to be used for this request. Only CustomerPayBillOnline is supported.
     * @param $Amount | The amount to be transacted.
     * @param $PartyA | The MSISDN sending the funds.
     * @param $PartyB | The organization shortcode receiving the funds
     * @param $PhoneNumber | The MSISDN sending the funds.
     * @param $CallBackURL | The url to where responses from M-Pesa will be sent to.
     * @param $AccountReference | Used with M-Pesa PayBills.
     * @param $TransactionDesc | A description of the transaction.
     * @param $Remark | Remarks
     * @return mixed|string
     */

    // public function STKPushSimulation($BusinessShortCode, $LipaNaMpesaPasskey, $TransactionType, $Amount, $PartyA, $PartyB, $PhoneNumber, $CallBackURL, $AccountReference, $TransactionDesc, $Remark){

        try {
            $environment = env("MPESA_ENV");
        } catch (\Throwable $th) {
            // $environment = self::env("MPESA_ENV");
						$environment = env("MPESA_ENV");
        }

        if( $environment =="live"){
            $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $token = self::generateLiveToken();
						// $token = generateLiveToken();
        }elseif ($environment=="sandbox"){
            $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $token = self::generateSandBoxToken();
						// $token = generateSandBoxToken();
        }else{
            return json_encode(["Message"=>"invalid application status"]);
        }

        $timestamp='20'.date(    "ymdhis");
        $password=base64_encode($BusinessShortCode.$LipaNaMpesaPasskey.$timestamp);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$token));

        $curl_post_data = array(
            'BusinessShortCode' => "174379",
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => TransactionType.CustomerPayBillOnline,
            'Amount' => $amount,
            'PartyA' => $PartyA,
            'PartyB' => "174379",
            'PhoneNumber' => $Phone,
            'CallBackURL' => "http://mycallbackurl.com/checkout.php",
            'AccountReference' => "001ABC",
            'TransactionDesc' => "Goods Payment"
        );

				// LNMExpress lnmExpress = new LNMExpress(
				// 					 "174379",
				// 					 "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919",  //https://developer.safaricom.co.ke/test_credentials
				// 					 TransactionType.CustomerPayBillOnline,
				// 					 "100",
				// 					 "254708374149",
				// 					 "174379",
				// 					 phoneNumber,
				// 					 "http://mycallbackurl.com/checkout.php",
				// 					 "001ABC",
				// 					 "Goods Payment"
				// 	 );


        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $curl_response=curl_exec($curl);
        return $curl_response;
    }

		?>


<h1 class="text-center text-success"> Thank you </h1>
<p class="text-center text-success">Your payment request for <?=money($grand_total);?> has been submitted successfully. We're looking forward to seeing you again!</p>

<?php
	include 'includes/footer.php';

 ?>