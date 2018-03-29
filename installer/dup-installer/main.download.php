<?php
defined("ABSPATH") or die("");
/*
  Copyright 2016 Snap Creek LLC  snapcreek.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
//LEGACY CODE REMOVE AFTER 3.5.7
//if (file_exists('dtoken.php')) {
//    //This is most likely inside the snapshot folder.
//
//    //DOWNLOAD ONLY: (Only enable download from within the snapshot directory)
//    if (isset($_GET['get']) && isset($_GET['file'])) {
//        //Clean the input, strip out anything not alpha-numeric or "_.", so restricts
//        //only downloading files in same folder, and removes risk of allowing directory
//        //separators in other charsets (vulnerability in older IIS servers), also
//        //strips out anything that might cause it to use an alternate stream since
//        //that would require :// near the front.
//    	$filename = preg_replace('/[^a-zA-Z0-9_.]*/','',$_GET['file']);
//    	if (strlen($filename) && file_exists($filename) && (strstr($filename, '_%fwrite_installer_base_name%'))) {
//            //Attempt to push the file to the browser
//    	    header('Content-Description: File Transfer');
//            header('Content-Type: application/octet-stream');
//            header('Content-Disposition: attachment; filename=installer.php');
//            header('Content-Transfer-Encoding: binary');
//            header('Expires: 0');
//            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//            header('Pragma: public');
//            header('Content-Length: ' . filesize($filename));
//            @ob_clean();
//            @flush();
//            if (@readfile($filename) == false) {
//                $data = file_get_contents($filename);
//                if ($data == false) {
//                    die("Unable to read installer file.  The server currently has readfile and file_get_contents disabled on this server.  Please contact your server admin to remove this restriction");
//                } else {
//                    print $data;
//                }
//            }
//        } else {
//            header("HTTP/1.1 404 Not Found", true, 404);
//            header("Status: 404 Not Found");
//        }
//    }
//
//	//Prevent Access from rovers or direct browsing in snapshop directory, or when
//    //requesting to download a file, should not go past this point.
//    exit;
//}
?>

<?php if (false) : ?>
	Error: PHP is not running. 
	Duplicator requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.
	<br/><br/><br/>
<?php endif; ?> 

