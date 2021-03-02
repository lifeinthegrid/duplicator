<?php
/**
 * Snap I/O utils
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package DupLiteSnapLib
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

if (!class_exists('DupLiteSnapLibIOU', false)) {

    require_once(dirname(__FILE__).'/class.snaplib.u.string.php');
    require_once(dirname(__FILE__).'/class.snaplib.u.os.php');

    class DupLiteSnapLibIOU
    {

        // Real upper bound of a signed int is 214748364.
        // The value chosen below, makes sure we have a buffer of ~4.7 million.
        const FileSizeLimit32BitPHP = 1900000000;
        const FWRITE_CHUNK_SIZE     = 4096; // bytes

        public static function rmPattern($filePathPattern)
        {
            @array_map('unlink', glob($filePathPattern));
        }

        /**
         * 
         * @param string $path
         * @param array $args    // array key / val where key is the var name in include
         * @param bool $required
         * @return string
         * @throws Exception // thorw exception if is $required and file can't be read
         */
        public static function getInclude($path, $args = array(), $required = true)
        {
            if (!is_readable($path)) {
                if ($required) {
                    throw new Exception('Can\'t read required file '.$path);
                } else {
                    return '';
                }
            }

            foreach ($args as $var => $value) {
                ${$var} = $value;
            }

            ob_start();
            if ($required) {
                require ($path);
            } else {
                include ($path);
            }
            return ob_get_clean();
        }

        public static function chmodPattern($filePathPattern, $mode)
        {
            $filePaths = glob($filePathPattern);

            $modes = array();

            foreach ($filePaths as $filePath) {
                $modes[] = $mode;
            }
            array_map(array(__CLASS__, 'chmod'), $filePaths, $modes);
        }

        public static function copy($source, $dest, $overwriteIfExists = true)
        {
            if (file_exists($dest)) {
                if ($overwriteIfExists) {
                    self::rm($dest);
                } else {
                    return false;
                }
            }
            return copy($source, $dest);
        }

        /**
         * 
         * @param string $source
         * @param string $dest
         * @return boolean false if fail
         */
        public static function rcopy($source, $dest)
        {
            if (!is_readable($source)) {
                return false;
            }

            if (is_dir($source)) {
                if (!file_exists($dest)) {
                    if (!self::mkdir($dest)) {
                        return false;
                    }
                }

                if (($handle = opendir($source)) != false) {
                    return false;
                }

                while ($file = readdir($handle)) {
                    if ($file == "." && $file == "..") {
                        continue;
                    }

                    if (is_dir($source.'/'.$file)) {
                        if (!self::rcopy($source.'/'.$file, $dest.'/'.$file)) {
                            return false;
                        }
                    } else {
                        if (!self::copy($source.'/'.$file, $dest.'/'.$file)) {
                            return false;
                        }
                    }
                }
                closedir($handle);
                return true;
            } else {
                return self::copy($source, $dest);
            }
        }

        public static function untrailingslashit($path)
        {
            return rtrim($path, '/\\');
        }

        public static function trailingslashit($path)
        {
            return self::untrailingslashit($path).'/';
        }

        public static function safePath($path, $real = false)
        {
            if ($real) {
                $res = realpath($path);
            } else {
                $res = $path;
            }
            return self::normalize_path($path);
        }

        public static function safePathUntrailingslashit($path, $real = false)
        {
            if ($real) {
                $res = realpath($path);
            } else {
                $res = $path;
            }
            return rtrim(self::normalize_path($res), '/');
        }

        public static function safePathTrailingslashit($path, $real = false)
        {
            return self::safePathUntrailingslashit($path, $real).'/';
        }

        /**
         * 
         * @param string $sourceFolder
         * @param string $destFolder
         * @param bool $skipIfExists
         * @return boolean
         */
        public static function moveContentDirToTarget($sourceFolder, $destFolder, $skipIfExists = false)
        {
            if (!is_dir($sourceFolder) || !is_readable($sourceFolder)) {
                return false;
            }

            if (!is_dir($destFolder) || !is_writable($destFolder)) {
                return false;
            }

            $sourceIterator = new DirectoryIterator($sourceFolder);
            foreach ($sourceIterator as $fileinfo) {
                if ($fileinfo->isDot()) {
                    continue;
                }
                $destPath = $destFolder.'/'.$fileinfo->getBasename();

                if (file_exists($destPath)) {
                    if ($skipIfExists) {
                        continue;
                    } else {
                        return false;
                    }
                }

                if (self::rename($fileinfo->getPathname(), $destPath) == false) {
                    return false;
                }
            }

            return true;
        }

        public static function massMove($fileSystemObjects, $destination, $exclusions = null, $exceptionOnError = true)
        {
            $failures = array();

            $destination = rtrim($destination, '/\\');

            if (!file_exists($destination) || !is_writeable($destination)) {
                self::mkdir($destination, 'u+rwx');
            }

            foreach ($fileSystemObjects as $fileSystemObject) {
                $shouldMove = true;

                if ($exclusions != null) {

                    foreach ($exclusions as $exclusion) {
                        if (preg_match($exclusion, $fileSystemObject) === 1) {
                            $shouldMove = false;
                            break;
                        }
                    }
                }

                if ($shouldMove) {

                    $newName = $destination.'/'.basename($fileSystemObject);

                    if (!file_exists($fileSystemObject)) {
                        $failures[] = "Tried to move {$fileSystemObject} to {$newName} but it didn't exist!";
                    } else if (!@rename($fileSystemObject, $newName)) {
                        $failures[] = "Couldn't move {$fileSystemObject} to {$newName}";
                    }
                }
            }

            if ($exceptionOnError && count($failures) > 0) {
                throw new Exception(implode(',', $failures));
            }

            return $failures;
        }

        public static function rename($oldname, $newname, $removeIfExists = false)
        {
            if ($removeIfExists) {
                if (file_exists($newname)) {
                    if (is_dir($newname)) {
                        self::rmdir($newname);
                    } else {
                        self::rm($newname);
                    }
                }
            }

            if (!@rename($oldname, $newname)) {
                throw new Exception("Couldn't rename {$oldname} to {$newname}");
            }

            return true;
        }

        public static function fopen($filepath, $mode, $throwOnError = true)
        {
            if (strlen($filepath) > DupLiteSnapLibOSU::maxPathLen()) {
                throw new Exception('Skipping a file that exceeds allowed max path length ['.DupLiteSnapLibOSU::maxPathLen().']. File: '.$filepath);
            }

            if (DupLiteSnapLibStringU::startsWith($mode, 'w') || DupLiteSnapLibStringU::startsWith($mode, 'c') || file_exists($filepath)) {
                $file_handle = @fopen($filepath, $mode);
            } else {
                if ($throwOnError) {
                    throw new Exception("$filepath doesn't exist");
                } else {
                    return false;
                }
            }

            if (!is_resource($file_handle)) {
                if ($throwOnError) {
                    throw new Exception("Error opening $filepath");
                } else {
                    return false;
                }
            } else {
                return $file_handle;
            }
        }

        public static function touch($filepath, $time = null)
        {
            if (!function_exists('touch')) {
                return false;
            }
            
            if ($time === null) {
                $time = time();
            }
            return @touch($filepath, $time);
        }

        public static function rmdir($dirname, $mustExist = false)
        {
            if (file_exists($dirname)) {
                self::chmod($dirname, 'u+rwx');
                if (self::rrmdir($dirname) === false) {
                    throw new Exception("Couldn't remove {$dirname}");
                }
            } else if ($mustExist) {
                throw new Exception("{$dirname} doesn't exist");
            }
        }

        public static function rm($filepath, $mustExist = false)
        {
            if (file_exists($filepath)) {
                self::chmod($filepath, 'u+rw');
                if (@unlink($filepath) === false) {
                    throw new Exception("Couldn't remove {$filepath}");
                }
            } else if ($mustExist) {
                throw new Exception("{$filepath} doesn't exist");
            }
        }

        /**
         * 
         * @param resource $handle
         * @param srtring $string
         * @return int
         * @throws Exception
         */
        public static function fwrite($handle, $string)
        {
            $bytes_written = @fwrite($handle, $string);

            if ($bytes_written != strlen($string)) {
                throw new Exception('Error writing to file.');
            } else {
                return $bytes_written;
            }
        }

        /**
         * wrinte file in chunk mode. For big data.
         * @param resource $handle
         * @param string $content
         * @return int
         * @throws Exception
         */
        public static function fwriteChunked($handle, $content)
        {
            $pieces  = str_split($content, self::FWRITE_CHUNK_SIZE);
            $written = 0;

            foreach ($pieces as $piece) {
                if (($fwResult = @fwrite($handle, $piece, self::FWRITE_CHUNK_SIZE)) === false) {
                    throw new Exception('Error writing to file.');
                }
                $written += $fwResult;
            }

            if ($written != strlen($content)) {
                throw new Exception('Error writing to file.');
            }

            return $written;
        }

        public static function fgets($handle, $length)
        {
            $line = fgets($handle, $length);

            if ($line === false) {
                throw new Exception('Error reading line.');
            }

            return $line;
        }

        public static function fclose($handle, $exception_on_fail = true)
        {
            if ((@fclose($handle) === false) && $exception_on_fail) {
                throw new Exception("Error closing file");
            }
        }

        public static function flock($handle, $operation)
        {
            if (@flock($handle, $operation) === false) {
                throw new Exception("Error locking file");
            }
        }

        public static function ftell($file_handle)
        {
            $position = @ftell($file_handle);

            if ($position === false) {
                throw new Exception("Couldn't retrieve file offset.");
            } else {
                return $position;
            }
        }

        /**
         * Safely remove a directory and recursively files and directory upto multiple sublevels
         *
         * @param path $dir The full path to the directory to remove
         *
         * @return bool Returns true if all content was removed
         */
        public static function rrmdir($path)
        {
            if (is_dir($path)) {
                if (($dh = opendir($path)) === false) {
                    return false;
                }
                while (($object = readdir($dh)) !== false) {
                    if ($object == "." || $object == "..") {
                        continue;
                    }
                    if (!self::rrmdir($path."/".$object)) {
                        closedir($dh);
                        return false;
                    }
                }
                closedir($dh);
                return @rmdir($path);
            } else {
                if (is_writable($path)) {
                    return @unlink($path);
                } else {
                    return false;
                }
            }
        }

        public static function filesize($filename)
        {
            $file_size = @filesize($filename);

            if ($file_size === false) {
                throw new Exception("Error retrieving file size of $filename");
            }

            return $file_size;
        }

        public static function fseek($handle, $offset, $whence = SEEK_SET)
        {
            $ret_val = @fseek($handle, $offset, $whence);

            if ($ret_val !== 0) {
                $filepath = stream_get_meta_data($handle);
                $filepath = $filepath["uri"];
                $filesize = self::filesize($filepath);
                // For future debug
                /*
                  error_log('$offset: '.$offset);
                  error_log('$filesize: '.$filesize);
                  error_log($whence. ' == '. SEEK_SET);
                 */
                if ($ret_val === false) {
                    throw new Exception("Trying to fseek($offset, $whence) and came back false");
                }
                //This check is not strict, but in most cases 32 Bit PHP will be the issue
                else if (abs($offset) > self::FileSizeLimit32BitPHP || $filesize > self::FileSizeLimit32BitPHP || ($offset <= 0 && ($whence == SEEK_SET || $whence == SEEK_END))) {
                    throw new DupLiteSnapLib_32BitSizeLimitException("Trying to seek on a file beyond the capability of 32 bit PHP. offset=$offset filesize=$filesize");
                } else {
                    throw new Exception("Error seeking to file offset $offset. Retval = $ret_val");
                }
            }
        }

        public static function filemtime($filename)
        {
            $mtime = filemtime($filename);

            if ($mtime === E_WARNING) {
                throw new Exception("Cannot retrieve last modified time of $filename");
            }

            return $mtime;
        }

        /**
         * exetute a file put contents after some checks. throw exception if fail.
         * 
         * @param string $filename
         * @param mixed $data
         * @return boolean
         * @throws Exception if putcontents fails
         */
        public static function filePutContents($filename, $data)
        {
            if (($dirFile = realpath(dirname($filename))) === false) {
                throw new Exception('FILE ERROR: put_content for file '.$filename.' failed [realpath fail]');
            }
            if (!is_dir($dirFile)) {
                throw new Exception('FILE ERROR: put_content for file '.$filename.' failed [dir '.$dirFile.' don\'t exists]');
            }
            if (!is_writable($dirFile)) {
                throw new Exception('FILE ERROR: put_content for file '.$filename.' failed [dir '.$dirFile.' exists but isn\'t writable]');
            }
            $realFileName = $dirFile.basename($filename);
            if (file_exists($realFileName) && !is_writable($realFileName)) {
                throw new Exception('FILE ERROR: put_content for file '.$filename.' failed [file exist '.$realFileName.' but isn\'t writable');
            }
            if (file_put_contents($filename, $data) === false) {
                throw new Exception('FILE ERROR: put_content for file '.$filename.' failed [Couldn\'t write data to '.$realFileName.']');
            }
            return true;
        }

        public static function getFileName($file_path)
        {
            $info = new SplFileInfo($file_path);
            return $info->getFilename();
        }

        public static function getPath($file_path)
        {
            $info = new SplFileInfo($file_path);
            return $info->getPath();
        }

        /**
         * this function make a chmod only if the are different from perms input and if chmod function is enabled
         *
         * this function handles the variable MODE in a way similar to the chmod of lunux
         * So the MODE variable can be
         * 1) an octal number (0755)
         * 2) a string that defines an octal number ("644")
         * 3) a string with the following format [ugoa]*([-+=]([rwx]*)+
         *
         * examples
         * u+rw         add read and write at the user
         * u+rw,uo-wx   add read and write ad the user and remove wx at groupd and other
         * a=rw         is equal at 666
         * u=rwx,go-rwx is equal at 700
         *
         * @param string $file
         * @param int|string $mode
         * @return boolean
         */
        public static function chmod($file, $mode)
        {
            if (!file_exists($file)) {
                return false;
            }

            $octalMode = 0;

            if (is_int($mode)) {
                $octalMode = $mode;
            } else if (is_numeric($mode)) {
                $octalMode = intval((($mode[0] === '0' ? '' : '0').$mode), 8);
            } else if (is_string($mode) && preg_match_all('/(a|[ugo]{1,3})([-=+])([rwx]{1,3})/', $mode, $gMatch, PREG_SET_ORDER)) {
                if (!function_exists('fileperms')) {
                    return false;
                }

                // start by file permission
                $octalMode = (fileperms($file) & 0777);

                foreach ($gMatch as $matches) {
                    // [ugo] or a = ugo
                    $group = $matches[1];
                    if ($group === 'a') {
                        $group = 'ugo';
                    }
                    // can be + - =
                    $action = $matches[2];
                    // [rwx]
                    $gPerms = $matches[3];

                    // reset octal group perms
                    $octalGroupMode = 0;

                    // Init sub perms
                    $subPerm = 0;
                    $subPerm += strpos($gPerms, 'x') !== false ? 1 : 0; // mask 001
                    $subPerm += strpos($gPerms, 'w') !== false ? 2 : 0; // mask 010
                    $subPerm += strpos($gPerms, 'r') !== false ? 4 : 0; // mask 100

                    $ugoLen = strlen($group);

                    if ($action === '=') {
                        // generate octal group permsissions and ugo mask invert
                        $ugoMaskInvert = 0777;
                        for ($i = 0; $i < $ugoLen; $i++) {
                            switch ($group[$i]) {
                                case 'u':
                                    $octalGroupMode = $octalGroupMode | $subPerm << 6; // mask xxx000000
                                    $ugoMaskInvert  = $ugoMaskInvert & 077;
                                    break;
                                case 'g':
                                    $octalGroupMode = $octalGroupMode | $subPerm << 3; // mask 000xxx000
                                    $ugoMaskInvert  = $ugoMaskInvert & 0707;
                                    break;
                                case 'o':
                                    $octalGroupMode = $octalGroupMode | $subPerm; // mask 000000xxx
                                    $ugoMaskInvert  = $ugoMaskInvert & 0770;
                                    break;
                            }
                        }
                        // apply = action
                        $octalMode = $octalMode & ($ugoMaskInvert | $octalGroupMode);
                    } else {
                        // generate octal group permsissions
                        for ($i = 0; $i < $ugoLen; $i++) {
                            switch ($group[$i]) {
                                case 'u':
                                    $octalGroupMode = $octalGroupMode | $subPerm << 6; // mask xxx000000
                                    break;
                                case 'g':
                                    $octalGroupMode = $octalGroupMode | $subPerm << 3; // mask 000xxx000
                                    break;
                                case 'o':
                                    $octalGroupMode = $octalGroupMode | $subPerm; // mask 000000xxx
                                    break;
                            }
                        }
                        // apply + or - action
                        switch ($action) {
                            case '+':
                                $octalMode = $octalMode | $octalGroupMode;
                                break;
                            case '-':
                                $octalMode = $octalMode & ~$octalGroupMode;
                                break;
                        }
                    }
                }
            } else {
                return true;
            }

            // if input permissions are equal at file permissions return true without performing chmod
            if (function_exists('fileperms') && $octalMode === (fileperms($file) & 0777)) {
                return true;
            }

            if (!function_exists('chmod')) {
                return false;
            }

            return @chmod($file, $octalMode);
        }

        /**
         * return file perms in string 
         * 
         * @param int|string $perms
         * @return string|bool // false if fail
         */
        public static function permsToString($perms)
        {
            if (is_int($perms)) {
                return decoct($perms);
            } else if (is_numeric($perms)) {
                return ($perms[0] === '0' ? '' : '0').$perms;
            } else if (is_string($perms)) {
                return $perms;
            } else {
                false;
            }
        }

        /**
         * this function creates a folder if it does not exist and performs a chmod.
         * it is different from the normal mkdir function to which an umask is applied to the input permissions.
         *
         * this function handles the variable MODE in a way similar to the chmod of lunux
         * So the MODE variable can be
         * 1) an octal number (0755)
         * 2) a string that defines an octal number ("644")
         * 3) a string with the following format [ugoa]*([-+=]([rwx]*)+
         *
         * @param string $path
         * @param int|string $mode
         * @param bool $recursive
         * @param resource $context // not used fo windows bug
         * @return boolean bool TRUE on success or FALSE on failure.
         *
         * @todo check recursive true and multiple chmod
         */
        public static function mkdir($path, $mode = 0777, $recursive = false, $context = null)
        {
            if (strlen($path) > DupLiteSnapLibOSU::maxPathLen()) {
                throw new Exception('Skipping a file that exceeds allowed max path length ['.DupLiteSnapLibOSU::maxPathLen().']. File: '.$path);
            }

            if (!file_exists($path)) {
                if (!function_exists('mkdir')) {
                    return false;
                }
                if (!@mkdir($path, 0777, $recursive)) {
                    return false;
                }
            }

            return self::chmod($path, $mode);
        }

        /**
         * this function call snap mkdir if te folder don't exists od don't have write or exec permissions
         *
         * this function handles the variable MODE in a way similar to the chmod of lunux
         * The mode variable can be set to have more flexibility but not giving the user write and read and exec permissions doesn't make much sense
         *
         * @param string $path
         * @param int|string $mode
         * @param bool $recursive
         * @param resource $context
         * @return boolean
         */
        public static function dirWriteCheckOrMkdir($path, $mode = 'u+rwx', $recursive = false, $context = null)
        {
            if (!file_exists($path)) {
                return self::mkdir($path, $mode, $recursive, $context);
            } else if (!is_writable($path) || !is_executable($path)) {
                return self::chmod($path, $mode);
            } else {
                return true;
            }
        }

        /**
         * from wordpress function wp_is_stream 
         *
         * @param string $path The resource path or URL.
         * @return bool True if the path is a stream URL.
         */
        public static function is_stream($path)
        {
            $scheme_separator = strpos($path, '://');

            if (false === $scheme_separator) {
                // $path isn't a stream
                return false;
            }

            $stream = substr($path, 0, $scheme_separator);

            return in_array($stream, stream_get_wrappers(), true);
        }

        /**
         * From Wordpress function: wp_mkdir_p
         * 
         * Recursive directory creation based on full path.
         *
         * Will attempt to set permissions on folders.
         *
         * @param string $target Full path to attempt to create.
         * @return bool Whether the path was created. True if path already exists.
         */
        public static function mkdir_p($target)
        {
            $wrapper = null;

            // Strip the protocol.
            if (self::is_stream($target)) {
                list( $wrapper, $target ) = explode('://', $target, 2);
            }

            // From php.net/mkdir user contributed notes.
            $target = str_replace('//', '/', $target);

            // Put the wrapper back on the target.
            if ($wrapper !== null) {
                $target = $wrapper.'://'.$target;
            }

            /*
             * Safe mode fails with a trailing slash under certain PHP versions.
             * Use rtrim() instead of untrailingslashit to avoid formatting.php dependency.
             */
            $target = rtrim($target, '/');
            if (empty($target)) {
                $target = '/';
            }

            if (file_exists($target)) {
                return @is_dir($target);
            }

            // We need to find the permissions of the parent folder that exists and inherit that.
            $target_parent = dirname($target);
            while ('.' != $target_parent && !is_dir($target_parent) && dirname($target_parent) !== $target_parent) {
                $target_parent = dirname($target_parent);
            }

            // Get the permission bits.
            if ($stat = @stat($target_parent)) {
                $dir_perms = $stat['mode'] & 0007777;
            } else {
                $dir_perms = 0777;
            }

            if (@mkdir($target, $dir_perms, true)) {

                /*
                 * If a umask is set that modifies $dir_perms, we'll have to re-set
                 * the $dir_perms correctly with chmod()
                 */
                if ($dir_perms != ( $dir_perms & ~umask() )) {
                    $folder_parts = explode('/', substr($target, strlen($target_parent) + 1));
                    for ($i = 1, $c = count($folder_parts); $i <= $c; $i++) {
                        @chmod($target_parent.'/'.implode('/', array_slice($folder_parts, 0, $i)), $dir_perms);
                    }
                }

                return true;
            }

            return false;
        }

        /**
         * 
         * @param string|bool $path     // return false if path isn't a sub path of main path or return the relative path
         */
        public static function getRelativePath($path, $mainPath)
        {
            if (strlen($mainPath) == 0) {
                return ltrim(self::safePathUntrailingslashit($path), '/');
            }

            $safePath     = self::safePathUntrailingslashit($path);
            $safeMainPath = self::safePathUntrailingslashit($mainPath);

            if ($safePath === $safeMainPath) {
                return '';
            } else if (strpos($safePath, self::trailingslashit($safeMainPath)) === 0) {
                return ltrim(substr($safePath, strlen($safeMainPath)), '/');
            } else {
                return false;
            }
        }

        /**
         * from wp_normalize_path
         *
         * @param string $path Path to normalize.
         * @return string Normalized path.
         */
        public static function normalize_path($path)
        {
            $wrapper = '';
            if (self::is_stream($path)) {
                list( $wrapper, $path ) = explode('://', $path, 2);
                $wrapper .= '://';
            }

            // Standardise all paths to use /
            $path = str_replace('\\', '/', $path);

            // Replace multiple slashes down to a singular, allowing for network shares having two slashes.
            $path = preg_replace('|(?<=.)/+|', '/', $path);
            if (strpos($path, '//') === 0) {
                $path = substr($path, 1);
            }

            // Windows paths should uppercase the drive letter
            if (':' === substr($path, 1, 1)) {
                $path = ucfirst($path);
            }

            return $wrapper.$path;
        }

        /**
         * Get common parent path from given paths
         * 
         * @param array $paths - array of paths
         * @return common parent path
         */
        public static function getCommonPath($paths = array())
        {
            if (empty($paths)) {
                return '';
            } if (!is_array($paths)) {
                $paths = array($paths);
            } else {
                $paths = array_values($paths);
            }

            $pathAssoc    = array();
            $numPaths     = count($paths);
            $minPathCouts = PHP_INT_MAX;

            for ($i = 0; $i < $numPaths; $i++) {
                $pathAssoc[$i] = explode('/', self::safePathUntrailingslashit($paths[$i]));
                $pathCount     = count($pathAssoc[$i]);
                if ($minPathCouts > $pathCount) {
                    $minPathCouts = $pathCount;
                }
            }

            for ($partIndex = 0; $partIndex < $minPathCouts; $partIndex++) {
                $currentPart = $pathAssoc[0][$partIndex];
                for ($currentPath = 1; $currentPath < $numPaths; $currentPath++) {
                    if ($pathAssoc[$currentPath][$partIndex] != $currentPart) {
                        break 2;
                    }
                }
            }

            $resultParts = array_slice($pathAssoc[0], 0, $partIndex);

            return implode('/', $resultParts);
        }

        /**
         * remove root path transforming the current path into a relative path
         * 
         * ex. /aaa/bbb  become aaa/bbb
         * ex. C:\aaa\bbb become aaa\bbb
         * 
         * @param string $path
         * @return string
         */
        public static function removeRootPath($path)
        {
            return preg_replace('/^(?:[A-Za-z]:)?[\/](.*)/', '$1', $path);
        }

        /**
         * Returns the last N lines of a file. Simular to tail command
         *
         * @param string $filepath The full path to the file to be tailed
         * @param int $lines The number of lines to return with each tail call
         *
         * @return string The last N parts of the file
         */
        public static function tailFile($filepath, $lines = 2)
        {
            // Open file
            $f = @fopen($filepath, "rb");
            if ($f === false)
                return false;

            // Sets buffer size
            $buffer = 256;

            // Jump to last character
            fseek($f, -1, SEEK_END);

            // Read it and adjust line number if necessary
            // (Otherwise the result would be wrong if file doesn't end with a blank line)
            if (fread($f, 1) != "\n")
                $lines -= 1;

            // Start reading
            $output = '';
            $chunk  = '';

            // While we would like more
            while (ftell($f) > 0 && $lines >= 0) {
                // Figure out how far back we should jump
                $seek   = min(ftell($f), $buffer);
                // Do the jump (backwards, relative to where we are)
                fseek($f, -$seek, SEEK_CUR);
                // Read a chunk and prepend it to our output
                $output = ($chunk  = fread($f, $seek)).$output;
                // Jump back to where we started reading
                fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                // Decrease our line counter
                $lines  -= substr_count($chunk, "\n");
            }

            // While we have too many lines
            // (Because of buffer size we might have read too many)
            while ($lines++ < 0) {
                // Find first newline and remove all text before that
                $output = substr($output, strpos($output, "\n") + 1);
            }
            fclose($f);
            return trim($output);
        }

        /**
         * @param string $path Path to the file
         * @param int $n Number of lines to get
         * @param int $charLimit Number of chars to include in each line
         * @return bool|array Last $n lines of file
         * @throws Exception
         */
        public static function getLastLinesOfFile($path, $n, $charLimit = null)
        {
            if (!is_readable($path)) {
                return false;
            }

            if (($handle = self::fopen($path, 'r', false)) === false) {
                return false;
            }

            $result      = array();
            $pos         = -1;
            $currentLine = '';
            $counter     = 0;

            while ($counter < $n && -1 !== fseek($handle, $pos, SEEK_END)) {
                $char = fgetc($handle);
                if (PHP_EOL == $char) {
                    $trimmedValue = trim($currentLine);
                    if (is_null($charLimit)) {
                        $currentLine = substr($currentLine, 0);
                    } else {
                        $currentLine = substr($currentLine, 0, (int) $charLimit);
                        if (strlen($currentLine) == $charLimit) {
                            $currentLine .= '...';
                        }
                    }

                    if (!empty($trimmedValue)) {
                        $result[] = $currentLine;
                        $counter++;
                    }
                    $currentLine = '';
                } else {
                    $currentLine = $char.$currentLine;
                }
                $pos--;
            }
            self::fclose($handle, false);

            return array_reverse($result);
        }

        /**
         * return a list of paths
         * 
         * @param string $dir
         * @param callable $callback
         * @param array $options // array(
         *                              'regexFile'     => [bool|string|array], // if is bool alrays or never match, if is string o array of string check if rexeses match file name
         *                              'regexFolder'   => [bool|string|array], // if is bool alrays or never match, if is string o array of string check if rexeses match file name
         *                              'checkFullPath' => bool,                // if false only current file/folder name is passed at regex if true is passed the full path
         *                              'recursive'     => bool,                // if false check only passed folder or all sub folder recursively
         *                              'invert'        => bool,                // if false pass invert the result
         *                              'childFirst'    => bool                 // if false is parsed parent folters first or child folders first
         *                          )
         *                          
         * @return boolean
         */
        public static function regexGlob($dir, $options)
        {
            $result = array();

            self::regexGlobCallback($dir, function ($path) use (&$result) {
                $result[] = $path;
            }, $options);

            return $result;
        }

        /**
         * execute the callback function foreach right element, private function for optimization
         * 
         * @param string $dir
         * @param callable $callback
         * @param array $options // array(
         *                              'regexFile'     => [bool|string|array], // if is bool alrays or never match, if is string o array of string check if rexeses match file name
         *                              'regexFolder'   => [bool|string|array], // if is bool alrays or never match, if is string o array of string check if rexeses match file name
         *                              'checkFullPath' => bool,                // if false only current file/folder name is passed at regex if true is passed the full path
         *                              'recursive'     => bool,                // if false check only passed folder or all sub folder recursively
         *                              'invert'        => bool,                // if false pass invert the result
         *                              'childFirst'    => bool                 // if false is parsed parent folters first or child folders first
         *                          )
         *                          
         * @return boolean
         */
        protected static function regexGlobCallbackPrivate($dir, $callback, $options)
        {
            if (!is_dir($dir) || !is_readable($dir)) {
                return false;
            }

            if (($dh = opendir($dir)) == false) {
                return false;
            }

            $trailingslashitDir = self::trailingslashit($dir);

            while (($elem = readdir($dh)) !== false) {
                if ($elem === '.' || $elem === '..') {
                    continue;
                }

                $fullPath  = $trailingslashitDir.$elem;
                $regex     = is_dir($fullPath) ? $options['regexFolder'] : $options['regexFile'];
                $pathCheck = $options['checkFullPath'] ? $fullPath : $elem;

                if (is_bool($regex)) {
                    $match = ($regex xor $options['invert']);
                } else {
                    $match = false;
                    foreach ($regex as $currentRegex) {
                        if (preg_match($currentRegex, $pathCheck) === 1) {
                            $match = true;
                            break;
                        }
                    }

                    if ($options['invert']) {
                        $match = !$match;
                    }
                }

                if ($match) {
                    if ($options['recursive'] && $options['childFirst'] === true && is_dir($fullPath)) {
                        self::regexGlobCallbackPrivate($fullPath, $callback, $options);
                    }

                    call_user_func($callback, $fullPath);

                    if ($options['recursive'] && $options['childFirst'] === false && is_dir($fullPath)) {
                        self::regexGlobCallbackPrivate($fullPath, $callback, $options);
                    }
                }
            }
            closedir($dh);

            return true;
        }

        /**
         * execute the callback function foreach right element (folder or files)
         * 
         * @param string $dir
         * @param callable $callback
         * @param array $options // array(
         *                              'regexFile'     => [bool|string|array], // if is bool alrays or never match, if is string o array of string check if rexeses match file name
         *                              'regexFolder'   => [bool|string|array], // if is bool alrays or never match, if is string o array of string check if rexeses match file name
         *                              'checkFullPath' => bool,                // if false only current file/folder name is passed at regex if true is passed the full path
         *                              'recursive'     => bool,                // if false check only passed folder or all sub folder recursively
         *                              'invert'        => bool,                // if false pass invert the result
         *                              'childFirst'    => bool                 // if false is parsed parent folters first or child folders first
         *                          )
         *                          
         * @return boolean
         */
        public static function regexGlobCallback($dir, $callback, $options = array())
        {
            if (!is_callable($callback)) {
                return false;
            }

            $options = array_merge(array(
                'regexFile'     => true,
                'regexFolder'   => true,
                'checkFullPath' => false,
                'recursive'     => false,
                'invert'        => false,
                'childFirst'    => false
                ), (array) $options);

            if (is_scalar($options['regexFile']) && !is_bool($options['regexFile'])) {
                $options['regexFile'] = array($options['regexFile']);
            }

            if (is_scalar($options['regexFolder']) && !is_bool($options['regexFolder'])) {
                $options['regexFolder'] = array($options['regexFolder']);
            }

            return self::regexGlobCallbackPrivate(self::safePath($dir), $callback, $options);
        }

        public static function emptyDir($dir)
        {
            $dir = self::safePathTrailingslashit($dir);
            if (!is_dir($dir) || !is_readable($dir)) {
                return false;
            }

            if (($dh = opendir($dir)) == false) {
                return false;
            }

            $listToDelete = array();

            while (($elem = readdir($dh)) !== false) {
                if ($elem === '.' || $elem === '..') {
                    continue;
                }

                $fullPath = $dir.$elem;
                if (is_writable($fullPath)) {
                    $listToDelete[] = $fullPath;
                }
            }
            closedir($dh);

            foreach ($listToDelete as $path) {
                self::rrmdir($path);
            }
            return true;
        }

        /**
         * Returns a path to the base root folder of path taking into account the
         * open_basedir setting.
         *
         * @param $path
         * @return bool|string Base root path of $path if it's accessible, otherwise false;
         */
        public static function getMaxAllowedRootOfPath($path)
        {
            $path = self::safePathUntrailingslashit($path, true);

            if (!self::isOpenBaseDirEnabled()) {
                $parts = explode("/", $path);
                return $parts[0]."/";
            } else {
                return self::getOpenBaseDirRootOfPath($path);
            }
        }

        /**
         * @return bool true if open_basedir is set
         */
        public static function isOpenBaseDirEnabled()
        {
            $iniVar = ini_get("open_basedir");
            return !empty($iniVar);
        }

        /**
         * @return array Paths contained in the open_basedir setting. Empty array if the setting
         *               is not enabled.
         */
        public static function getOpenBaseDirPaths()
        {
            if (!($openBase = ini_get("open_basedir"))) {
                return array();
            }
            return explode(PATH_SEPARATOR, $openBase);
        }

        /**
         * @param $path
         * @return bool|mixed|string Path to the base dir of $path if it exists, otherwise false
         */
        public static function getOpenBaseDirRootOfPath($path)
        {
            foreach (self::getOpenBaseDirPaths() as $allowedPath) {
                $allowedPath = $allowedPath !== "/" ? self::safePathUntrailingslashit($allowedPath) : "/";
                if (strpos($path, $allowedPath) === 0) {
                    return $allowedPath;
                }
            }

            return false;
        }
    }
}
