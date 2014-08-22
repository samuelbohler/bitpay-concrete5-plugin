<?php  $form = Loader::helper('form'); ?>
<style>
	#submit_next {display: none;}
</style>
<script type="text/javascript">
$(document).on("ready", function(){
	$('#ccm-core-commerce-checkout-form-payment-method > form').on('submit', function(e){
		e.preventDefault();
		console.log("Made it here");
		document.location.href = $('#ccm-core-commerce-checkout-form-payment-method > form').attr('action');
	});
	$('#submit_next').show();
});
</script>

<?php  if(isset($bitpay_error)) { ?>

	<span><?php echo $bitpay_error; ?></span>

<?php  }else{ 
			echo t("Click 'Next' to proceed to bitpay.com to finish your order.");
  	   }
?>