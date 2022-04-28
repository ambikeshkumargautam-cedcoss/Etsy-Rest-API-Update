<?php
/*
 * login_with_etsy.php
 *
 * @(#) $Id: login_with_etsy.php,v 1.1 2014/03/17 09:45:08 mlemos Exp $
 *
 */

	/*
	 *  Get the http.php file from http://www.phpclasses.org/httpclient
	 */
	ini_set( 'memory_limit', -1 );
	session_start();
	require 'http.php';
	require 'oauth_client.php';

if ( ( isset( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) ) || isset( $_SESSION['OAUTH_ACCESS_TOKEN'] ) ) {

	$client               = new oauth_client_class();
	$client->debug        = false;
	$client->debug_http   = true;
	$client->server       = 'Etsy';
	$client->redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] .
	dirname( strtok( $_SERVER['REQUEST_URI'], '?' ) ) . '/login_with_etsy.php';

	$client->client_id     = 'ghvcvauxf2taqidkdx2sw4g4';
	$application_line      = __LINE__;
	$client->client_secret = '27u2kvhfmo';

	$client->Initialize();

	/*
	$success = $client->CallAPI(
					'https://openapi.etsy.com/v2//taxonomy/seller/get/',
					'GET', array(), array('FailOnAccessError'=>true), $category);*/
	// $success = $client->CallAPI(
	// 'https://openapi.etsy.com/v2/users/283513310',
	// 'GET', array(), array('FailOnAccessError'=>true), $user);

	// print_r($user);



	// $success = $client->CallAPI(
	// 'https://openapi.etsy.com/v2/users/283513310/shops',
	// 'GET', array("user_id" => "283513310"), array('FailOnAccessError'=>true), $shop);

	// print_r($shop);

	// $success = $client->CallAPI(
	// 'https://openapi.etsy.com/v2/shops/ShubhamStoreIndia',
	// 'GET', array(), array('FailOnAccessError'=>true), $shop1);

	// print_r($shop1);

	/*
	$success = $client->CallAPI(
					"https://openapi.etsy.com/v2/shops/22774500/sections",
					"POST", array( 'title' => "Women Clothing" ), array('FailOnAccessError'=>true), $sections);

	print_r($sections);*/

	/*
	$array = array(
		"quantity" => 9,
		"title" => "T-shirt Round Neck",
		"description" => "T-Shirt Round Neck

	Baby Clothing

	6 months - 12 months

	Green Color

	100% Cotton ",
		"price" => 15,
		"shipping_template_id" => 87732611554,
		"shop_section_id" => 28442344,
		"state" => "draft",
		"processing_min" => "1",
		"processing_max" => "3",
		"taxonomy_id" => "2137",
		"who_made" => "i_did",
		"is_supply" => "1",
		"when_made" => "2010_2019",
		"recipient" => "baby_boys"
	);

	$success = $client->CallAPI(
					"https://openapi.etsy.com/v2/listings",
					"POST", $array, array('FailOnAccessError'=>true), $listings);

	print_r($listings);*/
	$image_path = '/opt/lampp/htdocs/web/wordpress/wp-content/uploads/2020/03/vnech-tee-green-1.jpg';
	$array      = array(
		'listing_id' => 792312279,
		'image'      => array( '@' . $image_path . ';type=image/jpeg' ),
		'rank'       => 1,
	);

	// $arr1 = array(
	// 'FailOnAccessError'=>true,
	// "Files"=>array(
	// "Type" => "FileName",
	// "FileName" => "image.jpeg",
	// "ContentType" => "image/jpeg"
	// )
	// );

	$success = $client->CallAPI(
		'https://openapi.etsy.com/v2/listings/792312279/images',
		'POST',
		$array,
		array( 'FailOnAccessError' => true ),
		$listingImage
	);

	print_r( $listingImage );

	// $success = $client->CallAPI(
	// "https://openapi.etsy.com/v2/listings/792312279/inventory",
	// "GET", array( "listing_id" => 792312279 ), array('FailOnAccessError'=>true), $listingData);

	// $listingData = json_decode(json_encode($listingData),true);

	// $listingData = $listingData['results']['products'];
	// $listingData[0]['offerings'][0]['quantity'] = 100;
	// $listingData[0]['offerings'][0]['price'] = 150;
	// $listingData[0]['sku'] = "testing-11";

	// print_r($listingData);

	// $success = $client->CallAPI(
	// "https://openapi.etsy.com/v2/listings/792312279/inventory",
	// "PUT", array("products" => json_encode($listingData)), array('FailOnAccessError'=>true), $listing);

	// print_r($listing);

} else {
	// require('/opt/lampp/htdocs/httpclient-2016-05-02/http.php');

	$client               = new oauth_client_class();
	$client->debug        = false;
	$client->debug_http   = true;
	$client->server       = 'Etsy';
	$client->redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] .
	dirname( strtok( $_SERVER['REQUEST_URI'], '?' ) ) . '/login_with_etsy.php';

	$client->client_id     = 'ghvcvauxf2taqidkdx2sw4g4';
	$application_line      = __LINE__;
	$client->client_secret = '27u2kvhfmo';
	// $client->scope = 'listings_r';

	if ( strlen( $client->client_id ) == 0
		|| strlen( $client->client_secret ) == 0 ) {
		die(
			'Please go to Etsy Developers page https://www.etsy.com/developers/register , ' .
			'create an application, and in the line ' . $application_line .
			' set the client_id to key string and client_secret with shared secret. ' .
			'The Callback URL must be ' . $client->redirect_uri
		);
	}

	if ( ( $success = $client->Initialize() ) ) {
		if ( ( $success = $client->Process() ) ) {
			if ( strlen( $client->access_token ) ) {
				$success = $client->CallAPI(
					'https://openapi.etsy.com/v2/users/__SELF__',
					'GET',
					array(),
					array( 'FailOnAccessError' => true ),
					$user
				);
			}
		}
		$success = $client->Finalize( $success );
	}
	if ( $client->exit ) {
		exit;
	}
	if ( $success ) {
		?>
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head>
				<title>Etsy OAuth client results</title>
			</head>
			<body>
			<?php
			echo '<h1>', HtmlSpecialChars( $user->results[0]->login_name ),
			' you have logged in successfully with Etsy!</h1>';
			echo '<pre>', HtmlSpecialChars( print_r( $user, 1 ) ), '</pre>';
			?>
			</body>
			</html>
			<?php
	} else {
		?>
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head>
				<title>OAuth client error</title>
			</head>
			<body>
				<h1>OAuth client error</h1>
				<pre>Error: <?php echo HtmlSpecialChars( $client->error ); ?></pre>
			</body>
			</html>
			<?php
	}
}
