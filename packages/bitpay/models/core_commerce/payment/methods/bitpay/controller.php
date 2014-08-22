<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
Loader::library('payment/controller', 'core_commerce');
class CoreCommerceBitpayPaymentMethodController extends CoreCommercePaymentController {

	public function method_form() {
		$pkg = Package::getByHandle('bitpay');
		$this->set('PAYMENT_METHOD_BITPAY_API_KEY', $pkg->config('PAYMENT_METHOD_BITPAY_API_KEY'));
		$this->set('PAYMENT_METHOD_BITPAY_TEST_MODE', $pkg->config('PAYMENT_METHOD_BITPAY_TEST_MODE'));
		$this->set('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED', $pkg->config('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED'));
		$this->set('PAYMENT_METHOD_BITPAY_CURRENCY_CODE', 
			(strlen($pkg->config('PAYMENT_METHOD_BITPAY_CURRENCY_CODE'))?$pkg->config('PAYMENT_METHOD_BITPAY_CURRENCY_CODE'):'USD')
			);

		$bitpay_transaction_speeds = array(
			'low'	=>t('Low'),
			'medium'=>t('Medium'),
			'high'	=>t('High')
		);
		$this->set('bitpay_transaction_speeds',$bitpay_transaction_speeds);	

		$bitpay_currency_codes = array(
			'BTC'=>t('Bitcoin'),
			'USD'=>t('US Dollar'),
			'EUR'=>t('Eurozone Euro'),
			'GBP'=>t('Pound Sterling'),
			'JPY'=>t('Japanese Yen'),
			'CAD'=>t('Canadian Dollar'),
			'AUD'=>t('Australian Dollar'),
			'CNY'=>t('Chinese Yuan'),
			'CHF'=>t('Swiss Franc'),
			'SEK'=>t('Swedish Krona'),
			'NZD'=>t('New Zealand Dollar'),
			'KRW'=>t('South Korean Won')

			// TODO: add the remaining currencies

		);
		asort($bitpay_currency_codes);
		$this->set('bitpay_currency_codes',$bitpay_currency_codes);	
		
	}
	
	public function validate() {
		$e = parent::validate();
		$ve = Loader::helper('validation/strings');
		
		if ($this->post('PAYMENT_METHOD_BITPAY_API_KEY') == '') {
			$e->add(t('You must specify your BitPay API Key (for authenticated payment notices)'));
		}

		return $e;
	}
	
	public function action_notify_complete() {
		$success = false;
		Log::addEntry("Payment Notification Received\n\n".$_SERVER['HTTP_REFERER']);
        Loader::model('order/model', 'core_commerce');
		$pkg = Package::getByHandle('bitpay');

		$response = bpVerifyNotification($pkg->config('PAYMENT_METHOD_BITPAY_API_KEY'));

		if (is_string($response))
        {
        	Log::addEntry("bitpay interface error: $response");      
        } 
		else
        {
        	$eh = Loader::helper('encryption');
            $order_id = $eh->decrypt($response['posData']);
            $o = CoreCommerceOrder::getByID($orderID);
        	$order_total = number_format($o->getOrderTotal(),2,'.','');

        	if ($o) {
	            switch($response['status'])
	            {
					case 'confirmed':
					case 'complete':                    
	                    $o->setStatus(CoreCommerceOrder::STATUS_AUTHORIZED);				
						parent::finishOrder($o, 'BitPay');
						break;
					case 'invalid':
						Log::addEntry('Invalid payment debug info for order# '.$o->getOrderID().'\n'.var_export($response,true) . var_export($o,true));
				}
			} else {
				Log::addEntry('Received order notification with unknown order: '.$orderID);
			}
        }

	}
	
	public function form() {
		Loader::library('bp_lib', 'bitpay');
		$pkg = Package::getByHandle('bitpay');

		// Get Return URL
		$ch = Loader::helper('checkout/step', 'core_commerce');
		$ns = $ch->getNextCheckoutStep();
		$ps = $ch->getCheckoutStep();
		$redirectURL = $ns->getURL();

		// Get Invoice #
		$o = CoreCommerceCurrentOrder::get();
	    $o->setStatus(CoreCommerceOrder::STATUS_INCOMPLETE);
		$this->set('item_name', SITE);
		$invoice_number = $o->getInvoiceNumber();

		// Get Order ID
		$eh = Loader::helper('encryption');
		$order_id = $eh->encrypt($o->getOrderID());

		$options = array(
			'apiKey'            => $pkg->config('PAYMENT_METHOD_BITPAY_API_KEY'),
            'notificationURL'   => $this->action('notify_complete'),
            'redirectURL'       => $redirectURL,
            'currency'          => $pkg->config('PAYMENT_METHOD_BITPAY_CURRENCY_CODE'),
            'physical'          => 'true',
            'fullNotifications' => 'true',
            'transactionSpeed'  => $pkg->config('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED'),
            'testMode'			=> ($pkg->config('PAYMENT_METHOD_PAYPAL_STANDARD_TEST_MODE') == 'test')
        );

        $response = bpCreateInvoice($invoice_number, $o->getOrderTotal(), $order_id, $options);
		
		if(array_key_exists('error', $response))
        {
            $bitpay_error = t("Error: Problem communicating with payment provider.\nPlease try again later.");
            $this->set('bitpay_error', $bitpay_error);
        }
        else
        {
            $this->set('action', $response["url"]);
        }
		
	}
		
	public function save() {
		$pkg = Package::getByHandle('bitpay');
		$pkg->saveConfig('PAYMENT_METHOD_BITPAY_API_KEY', $this->post('PAYMENT_METHOD_BITPAY_API_KEY'));
		$pkg->saveConfig('PAYMENT_METHOD_BITPAY_TEST_MODE', $this->post('PAYMENT_METHOD_BITPAY_TEST_MODE'));
		$pkg->saveConfig('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED', $this->post('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED'));
	}
	

   private function validateIPN() {
      $pkg = Package::getByHandle('bitpay');
      $req = 'cmd=' . urlencode('_notify-validate');

      foreach ($_POST as $key => $value) {
         $value = urlencode(stripslashes($value));
         $req .= "&$key=$value";
      }

      $sandbox = '';
		if ($pkg->config('PAYMENT_METHOD_BITPAY_TEST_MODE') == 'test') { 
         $sandbox = ".test";
      }

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://www'.$sandbox.'.paypal.com/cgi-bin/webscr');
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: www'.$sandbox.'.paypal.com'));
      $res = curl_exec($ch);
      curl_close($ch);
 
 
	  // assign posted variables to local variables
      $item_name = $_POST['item_name'];
      $item_number = $_POST['item_number'];
      $payment_status = $_POST['payment_status'];
      $payment_amount = $_POST['mc_gross'];
      $payment_currency = $_POST['mc_currency'];
      $txn_id = $_POST['txn_id'];
      $receiver_email = $_POST['receiver_email'];
      $payer_email = $_POST['payer_email'];

      if (strcmp ($res, "VERIFIED") == 0) {
      // check the payment_status is Completed
      // check that txn_id has not been previously processed
      // check that receiver_email is your Primary PayPal email
      // check that payment_amount/payment_currency are correct
      // process payment
         return true;
      }
      else if (strcmp ($res, "INVALID") == 0) {
         // log for manual investigation
         Log::addEntry(t("Paypal had an IPN issue : ").var_export($res));
         return false;
      }
	}
	
}
