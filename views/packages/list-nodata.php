<?php
	$show_notice01  = false; //DUP_UI::GetViewStateValue('dup-notice01-chk');
?>

<div id='dup-list-alert-nodata'>
	<i class="fa fa-archive"></i> 
		<?php _e("No Packages Found.", 'wpduplicator'); ?><br/>
		<?php _e("Click the 'Create New' button to build a package.", 'wpduplicator'); ?> <br/><br/>

	<i>
		<?php
			printf("%s <a href='admin.php?page=duplicator-help'>%s</a> %s",
				__("Please visit the", 'wpduplicator'), 
				__("help page", 'wpduplicator'),
				__("for additional support", 'wpduplicator'));
		?>
	</i>
	
	<!-- NOTICE01: 0.5.0 and 0.5.6:  Removed in 0.5.8 -->
	<?php if( $show_notice01)  :	?>
		<div id="dup-notice-01" class='dup-notice-msg'>
			<i class="fa fa-exclamation-triangle fa-lg"></i>
			<?php 
				_e("Older packages prior to 0.5.0 are no longer supported in this version.", 'wpduplicator'); 

				printf("  %s <a href='admin.php?page=duplicator-help'>%s</a> %s",
					__("To get an older package please visit the", 'wpduplicator'), 
					__("help page", 'wpduplicator'),
					__("and look for the Change Log link for additional instructions.", 'wpduplicator'));
			?><br/>
			<label for="dup-notice01-chk">
				<input type="checkbox" class="dup-notice-chk" id="dup-notice01-chk" name="dup-notice01-chk" onclick="Duplicator.UI.SaveViewStateByPost('dup-notice01-chk', 1); jQuery('#dup-notice-01').hide()" /> 
				<?php _e("Hide this message", 'wpduplicator'); ?>
			</label>
		</div><br/><br/>
	<?php else : ?>			
		<div style="height:75px">&nbsp;</div>
	<?php endif; ?>
</div>



