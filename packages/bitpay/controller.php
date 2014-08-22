<?php       
defined('C5_EXECUTE') or die(_("Access Denied."));

class BitpayPackage extends Package {
	
	protected $pkgHandle = 'bitpay';
	protected $appVersionRequired = '5.4.1';
	protected $pkgVersion = '1.0.0';
	
	public function getPackageName() {
		return t("eCommerce - BitPay"); 
	}	
	
	public function getPackageDescription() {
		return t("BitPay Payment Add-on for the eCommerce package.");
	}

	public function install() {
		$pkg = parent::install();
		
		Loader::model('payment/method', 'core_commerce');
		CoreCommercePaymentMethod::add('bitpay', t('BitPay'), 0, null, $pkg);
	}

}