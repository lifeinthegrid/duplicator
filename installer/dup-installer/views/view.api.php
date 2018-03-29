<?php defined("ABSPATH") or die(""); ?>
<!-- START OF VIEW API -->
<style>
	div#content {width:70%}
	div#api-area {margin:auto; line-height:21px }
	div#api-area table {width:100%}
	div#api-area table td:first-child{width:40%; padding-right:15px}
	div#api-area table td{vertical-align:top; text-align:left}
	iframe#api-results {margin:auto; width:97%; height:90%; border:1px solid silver; min-height: 500px}
	div.api-details {font-size:11px}
	form.api-form {display:none; padding-left:20px}
	form.api-form input[type=text] {width:100%; font-size:12px; padding:3px}
	input#api-results-txt {width:97% !important; background: #efefef;}
	div.api-area a.operation {font-weight: bold; font-size:14px}
	div.api-area pre {font-size:11px; line-height: 13px; padding: 2px; border:1px solid silver; background: #efefef; border-radius: 3px}
</style>
<script>
	function RequestAPI(template, test) {
		var url = window.location.href;
		url = url.replace("/api/", "");
		url = url + template;
		if (test == 0) {
			$('#api-results-txt').val(url);
			$('#api-results').attr('src', url);
		} else {
			window.open(url, 'api-window');
		}
	}
</script>

<div id="api-area">
	<div class="hdr-main">
		API ROUTES:
		<!--div style="float:right; font-size:12px">
			<input type="checkbox" name="api-debug" id="api-debug">
			<label for="api-debug">Debug Routes</label>
		</div-->
	</div> 
	<div class="api-area">
	<table>
		<tr>
			<td>
				<b>OPERATIONS:</b>
				<?php foreach($API_Server->controllers as $class) : ?>
					<div style="padding: 5px 0 5px 0">
						<?php 
							$id = uniqid();
							$name = str_replace('/api/', '', $class->operation); 
						?>
						<a href="javascript:void(0)" onclick="$('#frm-<?php echo $id ?>').toggle()" class="operation">&#xbb;<?php echo $name; ?></a><br/>

						<form id="frm-<?php echo $id ?>" class="api-form">
							<input id="txt-<?php echo $id ?>" type="text" value="<?php echo $class->template; ?>" /> <br/>
							<a href="javascript:void(0)" onclick="RequestAPI($('#txt-<?php echo $id ?>').val(), 0)">[Test]</a> &nbsp;
							<a href="javascript:void(0)" onclick="RequestAPI($('#txt-<?php echo $id ?>').val(), 1)">[New Window]</a> &nbsp;
							<div class="api-details" id="details-<?php echo $id ?>">
								<?php DUPX_U::dump($class, 1); ?>
							</div>
						</form>
					</div>
				<?php endforeach; ?>					
			</td>
			<td>
				<b>TEST RESULTS:</b> <br/>
				<input id="api-results-txt" type="text" readonly="true" /> <br/>
				<iframe id="api-results" />
			</td>
		</tr>
	</table>
	</div>
</div>
<!-- END OF VIEW API -->