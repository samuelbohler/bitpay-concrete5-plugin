<?php  $form = Loader::helper('form'); ?>

<fieldset>
<legend><?php echo t('BitPay Information')?></legend>
<div class="clearfix">
	<?php echo $form->label('PAYMENT_METHOD_BITPAY_API_KEY', t('API Key'))?>
	<div class="input">
		<?php echo $form->text('PAYMENT_METHOD_BITPAY_API_KEY', $PAYMENT_METHOD_BITPAY_API_KEY, array('class' => 'input-xlarge'))?>
		<span class="help-inline"><?php echo t('Required')?></span>
	</div>
</div>

<div class="clearfix">
	<label><?php echo t('Test Mode')?></label>
	<div class="input">
		<ul class="inputs-list">
			<li><label><?php echo $form->radio('PAYMENT_METHOD_BITPAY_TEST_MODE', 'test', $PAYMENT_METHOD_BITPAY_TEST_MODE != 'live')?> <span><?php echo t('Test Mode')?></span></label></li>
			<li><label><?php echo $form->radio('PAYMENT_METHOD_BITPAY_TEST_MODE', 'live', $PAYMENT_METHOD_BITPAY_TEST_MODE == 'live')?> <span><?php echo t('Live')?></span></label></li>
		</ul>
	</div>
</div>

<div class="clearfix">
	<?php echo $form->label('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED', t('Transaction Speed'))?>
	<div class="input">
		<?php echo $form->select('PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED', $bitpay_transaction_speeds, $PAYMENT_METHOD_BITPAY_TRANSACTION_SPEED);?>
	</div>
</div>

<div class="clearfix">
	<?php echo $form->label('PAYMENT_METHOD_BITPAY_CURRENCY_CODE', t('Store Currency'))?>
	<div class="input">
		<?php echo $form->select('PAYMENT_METHOD_BITPAY_CURRENCY_CODE', $bitpay_currency_codes, $PAYMENT_METHOD_BITPAY_CURRENCY_CODE);?>
	</div>
</div>

</fieldset>
