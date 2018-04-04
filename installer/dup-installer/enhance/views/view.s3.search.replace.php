<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<!-- =========================
SEARCH AND REPLACE -->
<div class="hdr-sub1 toggle-hdr" data-type="toggle" data-target="#s3-custom-replace">
    <a href="javascript:void(0)"><i class="fa fa-plus-square"></i>Replace</a>
</div>

<div id='s3-custom-replace' style="display:none;">
    <div class="help-target">
        <a href="<?php echo $GLOBALS['_HELP_URL_PATH'];?>#help-s3" target="help"><i class="fa fa-question-circle"></i></a>
    </div><br/>

    <table class="s3-opts" id="search-replace-table">
        <tr valign="top" id="search-0">
            <td>Search:</td>
            <td><input type="text" name="search[]" style="margin-right:5px"></td>
        </tr>
        <tr valign="top" id="replace-0"><td>Replace:</td><td><input type="text" name="replace[]"></td></tr>
    </table>
    <button type="button" onclick="DUPX.addSearchReplace();return false;" style="font-size:12px;display: block; margin: 10px 0 0 0; " class="default-btn">Add More</button>
</div>
<br/><br/>