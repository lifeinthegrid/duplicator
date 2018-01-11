<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
                  * and open the template in the editor.
 */
?>
<script>
$(document).ready(function ()
{
    DUPX.FILEOPS = new Object();

    /* Operation Parameters */
    DUPX.FILEOPS.workerTimeInSec = 6;
    DUPX.FILEOPS.throttleDelay = 0;
    DUPX.FILEOPS.url = document.URL.substr(0,document.URL.lastIndexOf('/')) + '/lib/fileops/fileops.php';
    DUPX.FILEOPS.directoriesToDelete = []; // rsr todo: wp-content, includes, wp-admin
    DUPX.FILEOPS.directoriesToExclude = []; // rsr todo: wp-content, includes, wp-admin
    DUPX.FILEOPS.filesToExclude = []; //rsr todo: archive name/path (would need to ensure that directory itself wasn't deleted nor the archive

    /* Communication Parameters */
    DUPX.FILEOPS.maxRetries = 10;
    DUPX.FILEOPS.retryDelayInMs = 8000;
    DUPX.FILEOPS.deleteSiteFailureCount = 0;
});

DUPX.deleteSite = function (newRequest)
{
    var $form = $('#s1-input-form');
	var request = new Object();

	request.action = "deltree";
	request.directories = DUPX.FILEOPS.directoriesToDelete;
	request.worker_time = DUPX.FILEOPS.workerTimeInSec;
	request.throttle_delay = DUPX.FILEOPS.throttleDelay;
	request.excluded_directories = DUPX.FILEOPS.directoriesToExclude;
    request.excluded_files = DUPX.FILEOPS.filesToExclude;
    request.newRequest = newRequest;

	var requestString = JSON.stringify(request);

	console.log("FILEOPS url=" + DUPX.FILEOPS.url);
	console.log("requeststring=" + requestString);

    $("#operation-text").text("<?php DUP_PRO_U::_e('Deleting Existing Site')?>");

    $.ajax({
		type: "POST",
		timeout: DUPX.FILEOPS.workerTimeInSec * 2000,  // Double worker time and convert to ms
		dataType: "json",
		url: DUPX.FILEOPS.url,
		data: requestString,
		beforeSend: function () {
			DUPX.showProgressBar();
			$form.hide();
			$('#s1-result-form').show();
			DUPX.updateProgressPercent(0);
		},
		success: function (data) {

			DUPX.FILEOPS.deleteSiteFailureCount = 0;
			console.log("deleteSite:AJAX success. Resetting failure count");

			// DATA FIELDS
            // next_inode_index, total_inodes, failures, is_done

			if (typeof (data) != 'undefined' && data.pass == 1) {

				console.log("deleteSite:Passed");

				var status = data.status;
				var percent = Math.round((status.next_inode_index * 100.0) / status.total_inodes);

				console.log("deleteSite:updating progress percent");
				DUPX.updateProgressPercent(percent);

				var criticalFailureText = DUPX.getCriticalFailureText(status.failures);

				if(status.failures.length > 0) {
					console.log("deleteSite:There are failures present. (" + status.failures.length) + ")";
				}

				if (criticalFailureText === null) {
					console.log("deleteSite:No critical failures");
					if (status.is_done) {

						console.log("deleteSite:archive has completed");
						if(status.failures.length > 0) {

							console.log(status.failures);
							var errorMessage = "deleteSite:Problems during extract. These may be non-critical so continue with install.\n------\n";
							var len = status.failures.length;

							for(var j = 0; j < len; j++) {
								failure = status.failures[j];
								errorMessage += failure.subject + ":" + failure.description + "\n";
							}

							alert(errorMessage);
						}

						DUPX.clearDupArchiveStatusTimer();
						console.log("deleteSite:calling finalizeDupArchiveExtraction");
						DUPX.finalizeDupArchiveExtraction(status);
						console.log("deleteSite:after finalizeDupArchiveExtraction");

						var dataJSON = JSON.stringify(data);

						// Don't stop for non-critical failures - just display those at the end

						$("#ajax-logging").val($("input:radio[name=logging]:checked").val());
						$("#ajax-retain-config").val($("#retain_config").is(":checked") ? 1 : 0);
						$("#ajax-json").val(escape(dataJSON));

                        DUPX.startExtraction();

						$("#ajax-json-debug").val(dataJSON);
					} else {
						console.log('deleteSite:Archive not completed so keep deleting in 500');
						setTimeout(function() { DUPX.deleteSite(false); }, 500);
					}
				}
				else {
					console.log("deleteSite:critical failures present");
					// If we get a critical failure it means it's something we can't recover from so no purpose in retrying, just fail immediately.
					var errorString = 'Error Processing Step 1<br/>';

					errorString += criticalFailureText;

					DUPX.handleDeleteSiteProcessingFailed(errorString);
				}
			} else {
				var errorString = 'Error Processing Step 1<br/>';
				errorString += data.error;

				DUPX.handleDeleteSiteProcessingProblem(errorString, true);
			}
		},
		error: function (xHr, textStatus) {

			console.log('deleteSite:AJAX error. textStatus=');
			console.log(textStatus);

			DUPX.handleDeleteSiteCommunicationProblem(xHr, false);
		}
	});
};

DUPX.clearDeleteSiteStatusTimer = function ()
{
	if (DUPX.deleteSiteStatusIntervalID != -1) {
		clearInterval(DUPX.deleteSiteStatusIntervalID);
		DUPX.deleteSiteStatusIntervalID = -1;
	}
};

DUPX.handleDeleteSiteProcessingFailed = function(errorText)
{
	DUPX.clearDeleteSiteStatusTimer();
	$('#ajaxerr-data').html(errorText);
	DUPX.hideProgressBar();
}

DUPX.handleDeleteSiteProcessingProblem = function(errorText, pingDAWS) {

	DUPX.FILEOPS.deleteSiteFailureCount++;

	if(DUPX.FILEOPS.deleteSiteFailureCount <= DUPX.FILEOPS.maxRetries) {
		var callback = DUPX.pingDAWS;

		if(pingDAWS) {
			console.log('!!!DELETE SITE FAILURE #' + DUPX.DAWS.FailureCount);
		} else {
			console.log('!!!KICKOFF FAILURE #' + DUPX.DAWS.FailureCount);
			callback = DUPX.kickOffDupArchiveExtract;
		}

		DUPX.throttleDelay = 9;	// Equivalent of 'low' server throttling
		console.log('Relaunching in ' + DUPX.FILEOPS.retryDelayInMs);
		setTimeout(callback, DUPX.FILEOPS.retryDelayInMs);
	}
	else {
		console.log('Too many failures.');
		DUPX.handleDeleteSiteProcessingFailed(errorText);
	}
};

DUPX.handleDeleteSiteCommunicationProblem = function(xHr, pingDAWS)
{
	DUPX.FILEOPS.deleteSiteFailureCount++;

	if(DUPX.FILEOPS.deleteSiteFailureCount <= DUPX.DAWS.MaxRetries) {

		var callback = DUPX.pingDAWS;

		if(pingDAWS) {
			console.log('!!!PING FAILURE #' + DUPX.FILEOPS.deleteSiteFailureCount);
		} else {
			console.log('!!!KICKOFF FAILURE #' + DUPX.FILEOPS.deleteSiteFailureCount);
			callback = DUPX.kickOffDupArchiveExtract;
		}
		console.log(xHr);
		DUPX.throttleDelay = 9;	// Equivalent of 'low' server throttling
		console.log('Relaunching in ' + DUPX.DAWS.RetryDelayInMs);
		setTimeout(callback, DUPX.DAWS.RetryDelayInMs);
	}
	else {
		console.log('Too many failures when deleting site.');
		DUPX.ajaxCommunicationFailed(xHr);
	}
};
</script>