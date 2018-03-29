<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$installer_state = DUPX_InstallerState::getInstance();

$coreDirectories = array(
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-admin',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-includes',
    $installer_state->ovr_wp_content_dir);

//$coreDirectories = array(
//    $GLOBALS['CURRENT_ROOT_PATH'] . '/test'
//);

// Want to be able to do all php but that is pretty dangerous so stick with known files only
//$coreFilepaths = array($GLOBALS['CURRENT_ROOT_PATH'] . '/*.php');

$coreFilepaths = array(
    $GLOBALS['CURRENT_ROOT_PATH'] . '/index.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/license.txt',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/readme.html',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-activate.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-blog-header.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-comments-post.php',
    //$GLOBALS['CURRENT_ROOT_PATH'] . '/wp-config.php', -> keep this around
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-config-sample.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-cron.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-links-opml.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-load.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-login.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-mail.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-settings.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-signup.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-trackback.php',
    $GLOBALS['CURRENT_ROOT_PATH'] . '/xmlrpc.php');

//$coreFilepaths = array(
//$GLOBALS['CURRENT_ROOT_PATH'] . '/wp-config.php',
//    $GLOBALS['CURRENT_ROOT_PATH'] . '/test1.txt',
//    $GLOBALS['CURRENT_ROOT_PATH'] . '/test2.txt',
//    $GLOBALS['CURRENT_ROOT_PATH'] . '/test3.txt',
//    $GLOBALS['CURRENT_ROOT_PATH'] . '/test4.txt'
//);

$backupDirectory = '';

do
{
    $backupDirectory = $GLOBALS['CURRENT_ROOT_PATH'] . '/dpbak_' . SnapLibUtil::make_hash(); 

    DUPX_Log::info("Using {$backupDirectory} as the site backup directory");
   
} while(file_exists($backupDirectory));

//mkdir($backupDirectory);

//echo('bobster');
?>
<script>
    DUPX.moveCoreFiles = function()
    {
        var $form = $('#s1-input-form');
        var request = new Object();
		
        request.action = "move_files";
        request.directories = <?php echo json_encode($coreDirectories); ?>;   // TODO: list of directories to move
        request.files = <?php echo json_encode($coreFilepaths); ?>;         // TODO: list of files to move
        request.excluded_files = [];
        request.destination = "<?php echo $backupDirectory ?>";

        var requestString = JSON.stringify(request);

        console.log("FILEOPS url=" + DUPX.FILEOPS.url);
        console.log("requeststring=" + requestString);

        $("#operation-text").text("Moving core site files");

        $.ajax({
            type: "POST",
            timeout: DUPX.FILEOPS.standardTimeoutInSec * 1000,
            dataType: "json",
            url: DUPX.FILEOPS.url,
            data: requestString,
            beforeSend: function () {
                DUPX.showProgressBar();
                $form.hide();
                $('#s1-result-form').show();
                //	DUPX.updateProgressPercent(0);
            },
            success: function (data) {

                if (typeof (data) != 'undefined' && data.pass == 1) {

                    console.log("movecorefiles:Completed");

                    var status = data.status;
                
                    if (status.errors.length > 0) {

                        console.log(status.errors);
                        var errorMessage = "moveCoreFiles: Problems when moving core files. May be non-critical so continuing with install.\n------\n";
                        var len = status.errors.length;

                        for (var j = 0; j < len; j++) {
                            errorMessage += status.errors[j];
                        }

                        alert(errorMessage);
                    }

                    var dataJSON = JSON.stringify(data);

                    // Don't stop for non-critical failures - just display those at the end

                    $("#ajax-logging").val($("input:radio[name=logging]:checked").val());
                    $("#ajax-retain-config").val($("#retain_config").is(":checked") ? 1 : 0);
                    $("#ajax-json").val(escape(dataJSON));

                    DUPX.startExtraction();

                    $("#ajax-json-debug").val(dataJSON);
                    
                } else {
                    $('#ajaxerr-data').html("Error moving old site");;
                    DUPX.hideProgressBar();
                }
            },
            error: function (xHr, textStatus) {

                console.log('moveCoreFiles:AJAX error. textStatus=');
                console.log(textStatus);

                DUPX.ajaxCommunicationFailed(xHr);
            }
        });
    };

    DUPX.handleDeleteSiteCommunicationProblem = function (xHr, pingDAWS)
    {
        DUPX.FILEOPS.deleteSiteFailureCount++;

        if (DUPX.FILEOPS.deleteSiteFailureCount <= DUPX.DAWS.MaxRetries) {

            var callback = DUPX.pingDAWS;

            if (pingDAWS) {
                console.log('!!!PING FAILURE #' + DUPX.FILEOPS.deleteSiteFailureCount);
            } else {
                console.log('!!!KICKOFF FAILURE #' + DUPX.FILEOPS.deleteSiteFailureCount);
                callback = DUPX.kickOffDupArchiveExtract;
            }
            console.log(xHr);
            DUPX.throttleDelay = 9;	// Equivalent of 'low' server throttling
            console.log('Relaunching in ' + DUPX.DAWS.RetryDelayInMs);
            setTimeout(callback, DUPX.DAWS.RetryDelayInMs);
        } else {
            console.log('Too many failures when deleting site.');
            DUPX.ajaxCommunicationFailed(xHr);
        }
    };
</script>
