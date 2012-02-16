/*
 * date:2011/09/20
 * Social Medias Connect Plug-in
 */
(function($){
	if(!$||$ !== jQuery)$=jQuery;
	$(document).ready(function(){
		$('#smc_shorturl_service').change(function(){
			if($('option:selected',this).val()=='custom'){
				$('#smc_shorturl').fadeIn();
			}else{
				$('#smc_shorturl').fadeOut();
			}
		});
		
		$('#delete_bind').submit(function(){
			var ps=$('#repassword');
			if(ps.val() == '' || ps.val() == '输入密码'){
				ps.addClass('smc_').val('输入密码').fadeIn();
				return false;
			}
		});
		$('#repassword').focus(function(){
			if($(this).val()=='输入密码')$(this).val('');
		});
		
		$('#smc_float_style label').click(function(){
			$(this).prev('input').trigger('click');
		});
		$('#smc_is_use_float input').change(function(){
			var v=$(this).val();
			if(v=='1'){
				$('#smc_list_style').fadeOut('400',function(){
					$('#smc_float_style').fadeIn('400');
				});
				if($('#smc_list_style input:checked').val()!='2' || $('#smc_is_use_float input:checked').val()!='0')$('#smc_icon_size_set').fadeOut('400');
			}else{
				$('#smc_float_style').fadeOut('400',function(){
					$('#smc_list_style').fadeIn('400');
					if($('#smc_list_style input:checked').val()=='2' && $('#smc_is_use_float input:checked').val()=='0')$('#smc_icon_size_set').fadeIn('400');
				});
			}
		});
		$('#smc_list_style input').change(function(){
			if($(this).val()=='2' && $('#smc_is_use_float input:checked').val()=='0')$('#smc_icon_size_set').fadeIn();
			else $('#smc_icon_size_set').fadeOut();
		});
		
	});
})(jQuery);