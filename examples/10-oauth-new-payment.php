<?php
/*
 * Example 10 -  Using OAuth access token to prepare a new payment.
 */

try
{
	/*
	 * Initialize the Mollie API library with your OAuth access token.
	 */
	require "initialize_with_oauth.php";

	/*
	 * Generate a unique order id for this example. It is important to include this unique attribute
	 * in the redirectUrl (below) so a proper return page can be shown to the customer.
	 */
	$order_id = time();

	/*
	 * Determine the url parts to these example files.
	 */
	$protocol = isset($_SERVER['HTTPS']) && strcasecmp('off', $_SERVER['HTTPS']) !== 0 ? "https" : "http";
	$hostname = $_SERVER['HTTP_HOST'] ? : "my.app";
	$path     = dirname(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);


	/*
	 * Since unlike an API key the OAuth access token does NOT belong to a profile, we need to retrieve a profile
	 * so we can specify the profileId-parameter when creating a payment below.
	 */
	$profiles = $mollie->profiles->all();
	$profile  = reset($profiles);

	/*
	 * Payment parameters:
	 *   amount        Amount in EUROs. This example creates a € 10,- payment.
	 *   description   Description of the payment.
	 *   redirectUrl   Redirect location. The customer will be redirected there after the payment.
	 *   webhookUrl    Webhook location, used to report when the payment changes state.
	 *   metadata      Custom metadata that is stored with the payment.
	 */
	$payment = $mollie->payments->create(array(
		"amount"       => 10.00,
		"description"  => "My first API payment",
		"redirectUrl"  => "{$protocol}://{$hostname}{$path}/03-return-page.php?order_id={$order_id}",
		"webhookUrl"   => "{$protocol}://{$hostname}{$path}/02-webhook-verification.php",
		"metadata"     => array(
			"order_id" => $order_id,
		),
		"profileId" => $profile->id // This is specifically necessary for payment resources via OAuth access.
	));

	/*
	 * In this example we store the order with its payment status in a database.
	 */
	database_write($order_id, $payment->status);

	/*
	 * Send the customer off to complete the payment.
	 */
	if (PHP_SAPI === "cli")
	{
		echo "Redirect to: " . $payment->getPaymentUrl() . PHP_EOL;
		return;
	}

	header("Location: " . $payment->getPaymentUrl());
}
catch (Mollie_API_Exception $e)
{
	echo "API call failed: " . htmlspecialchars($e->getMessage());
}


/*
 * NOTE: This example uses a text file as a database. Please use a real database like MySQL in production code.
 */
function database_write ($order_id, $status)
{
	$order_id = intval($order_id);
	$database = dirname(__FILE__) . "/orders/order-{$order_id}.txt";

	file_put_contents($database, $status);
}
