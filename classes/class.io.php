<?php
defined("ABSPATH") or die("");

/**
 * @copyright 2016 Snap Creek LLC
 * Class for all IO operations
 */
class DUP_IO
{
    /**
     * Attempts to change the mode of the specified file.
     *
     * @param string	$file	Path to the file.
     * @param octal		$mode	The mode parameter consists of three octal number components specifying access restrictions for the owner
     *
     * @return TRUE on success or FALSE on failure.
     */
    public static function changeMode($file , $mode)
    {
        if (! file_exists($file))
            return false;

        if (@chmod($file , $mode) === false)
        {
        //	DUP_Log::Info("Error chaning the mode on: {$file}.");
        //	$bt = debug_backtrace();

        //	DUP_Log::InfoObject('backtrace', $bt);
            return false;
        }
        return true;
    }


    /**
     * Safely deletes a file
     *
     * @param string $file	The full filepath to the file
     *
     * @return TRUE on success or if file does not exist. FALSE on failure
     */
    public static function deleteFile($file)
    {
        if (file_exists($file))
        {
            if (@unlink($file) === false)
            {
                DUP_Log::Info("Could not delete file: {$file}");
                return false;
            }
        }
        return true;
    }


    /**
     * Safely copies a file to a directory
     *
     * @param string $source_file       The full filepath to the file to copy
     * @param string $dest_dir			The full path to the destination directory were the file will be copied
     * @param string $delete_first		Delete file before copying the new one
     *
     *  @return TRUE on success or if file does not exist. FALSE on failure
     */
    public static function copyFile($source_file, $dest_dir, $delete_first = false)
    {
        //Create directory
        if (file_exists($dest_dir) == false)
        {
            if (self::createDir($dest_dir, 0755, true) === false)
            {
                DUP_Log::Info("Error creating $dest_dir.");
                return false;
            }
        }

        //Remove file with same name before copy
        $filename = basename($source_file);
        $dest_filepath = $dest_dir . "/$filename";
        if($delete_first)
        {
            self::deleteFile($dest_filepath);
        }

        return copy($source_file, $dest_filepath);
    }


    /**
     * Get all of the files and directories of a path including dots
     * 
     * @param string $dir The full path to the directory
	 *
	 * Notes:
     * 	- Avoid using glob() as GLOB_BRACE is not an option on some operating systems
     * 	- Pre PHP 5.3 DirectoryIterator will crash on unreadable files
	 *  - Scandir will not crash on unreadable items and just skip file
     * 
     * @return array of all files in that path
     */
    public static function getFilesAll($dir = '.')
    {
		try {
			$files = array();
			foreach (new DirectoryIterator($dir) as $file) {
				$files[] = str_replace("\\", '/', $file->getPathname());
			}
			return $files;

		}  catch (Exception $exc) {

			$result = array();
			$files = @scandir($path);
			if (is_array($files)) {
				foreach ($files as $file) {
					$result[] = str_replace("\\", '/', $path) . $file;
				}
			}

			return $result;
		}
    }

    /**
     * Get only the files and excludes directories and dots
     *
     * @param string $dir The full path to the directory
     *
     * @return array of only files in that path
     */
	public static function  getFilesOnly($dir = '.')
	{
		$files = array();
		foreach (new DirectoryIterator($dir) as $file) {
			if (!$file->isDir())
				$files[] = str_replace("\\", '/', $file->getPathname());
		}
		return $files;
	}




    /**
     * Safely creates a directory
     *
     * @param string $dir		The full path to the directory to be created
     * @param octal  $mode			The mode is 0755 by default
     * @param bool	 $recursive		Allows the creation of nested directories specified in the pathname.
     *
     * @return TRUE on success and if directory already exists. FALSE on failure
     */
    public static function createDir($dir, $mode = 0755, $recursive = false)
    {
        if (file_exists($dir) && @is_dir($dir))
            return true;

        if (@mkdir($dir, $mode, $recursive) === false)
        {
            DUP_Log::Info("Error creating directory: {$dir}.");
            return false;
        }
        return true;
    }


    /**
     * List all of the directories of a path
     * 
     * @param string $dir to a system directory
     *
     * @return array of all directories in that path
     */
    public static function getDirs($dir = '.')
    {
        $dirs = array();
        foreach (new DirectoryIterator($dir) as $file)
        {
            if ($file->isDir() && !$file->isDot())
            {
                $dirs[] = DUP_Util::safePath($file->getPathname());
            }
        }
        return $dirs;
    }


    /**
     * Does the directory have content
     * 
     * @param string $dir	A system directory
     *
     * @return array of all directories in that path
     */
    public static function isDirEmpty($dir)
    {
        if (!is_readable($dir))
            return NULL;
        return (count(scandir($dir)) == 2);
    }


    /**
     * Size of the directory recursively in bytes
     * 
     * @param string $dir	A system directory
     *
     * @return int Returns the size of all data in the directory in bytes
     */
    public static function getDirSize($dir)
    {
        if (!file_exists($dir))
            return 0;
        if (is_file($dir))
            return filesize($dir);

        $size = 0;
        $list = glob($dir . "/*");
        if (!empty($list))
        {
            foreach ($list as $file)
                $size += self::getDirSize($file);
        }
        return $size;
    }

     /**
     * @todo ASKBOB
     */
    public static function restoreBackup($filepath, $backup_filepath)
    {
        if (is_dir($filepath) ||
                (file_exists($filepath) && (is_file($filepath) == false)))
        {
            DUP_Log::Info("Trying to restore backup to a directory ($filepath) rather than file which isn't allowed.");
        }

        if (file_exists($filepath))
        {
            DUP_Log::Info("Deleting $filepath");
            if (@unlink($filepath))
            {
                DUP_Log::Info("Deleted $filepath");
            }
            else
            {
                $message = "Couldn't delete $filepath";
                DUP_Log::error($message, false);
                DUP_Log::Info($message);
            }
        }

        if (file_exists($backup_filepath))
        {
            DUP_Log::Info("Renaming $backup_filepath to $filepath");

            if (@rename($backup_filepath, $filepath))
            {
                DUP_Log::Info("Renamed $backup_filepath to $filepath");
            }
            else
            {
                $message = "Couldn't rename $backup_filepath to $filepath";
                DUP_Log::error($message, false);
                DUP_Log::Info($message);
            }
        }
    }

     /**
     * @todo ASKBOB
     */
    public static function copyToDir($filepath, $directory)
    {
        if(!file_exists($directory))
        {
            @mkdir($directory);
        }

        $destination_filepath = $directory . '/' . basename($filepath);

        return @copy($filepath, $destination_filepath);
    }


    public static function deleteTree($directory)
	{
		$success = true;

        if(!file_exists("{$directory}/wp-config.php"))
        {
            $filenames = array_diff(scandir($directory), array('.', '..'));

            foreach ($filenames as $filename) {
                if (is_dir("$directory/$filename")) {
                    $success = self::deleteTree("$directory/$filename");
                } else {
                    $success = @unlink("$directory/$filename");
                }

                if ($success === false) {
                    DUP_Log::Info("Problem deleting $directory/$filename");
                    break;
                }
            }
        }
        else
        {
            DUP_Log::Info("Attempted to delete a directory with wp-config.php in it! ({$directory})");
            
            return false;
        }

		return $success && @rmdir($directory);
	}

	public static function copyDir($src, $dst)
    {
        $success = true;

        $dir = opendir($src);
        @mkdir($dst);

        while (false !== ( $file = readdir($dir)))
        {
            if (( $file != '.' ) && ( $file != '..' ))
            {
                if (is_dir($src . '/' . $file))
                {
                    $success = $success && self::copyDir($src . '/' . $file, $dst . '/' . $file);
                }
                else
                {
                    $src_filepath = $src . '/' . $file;
                    $dst_filepath = $dst . '/' . $file;

                    DUP_Log::Info("attempting to copy $src_filepath to $dst_filepath");
                    if (copy($src_filepath, $dst_filepath) === false)
                    {
                        $success = false;
                        //self::log("error copy $src_filepath to $dst_filepath");
                        DUP_Log::Info("error copy $src_filepath to $dst_filepath");
                    }
                }
            }
        }
        closedir($dir);

        return $success;
    }


    public static function copyWithVerify($source_filepath, $dest_filepath)
    {
        DUP_Log::Info("Copy with verify $source_filepath to $dest_filepath");

        $ret_val = false;

        if (copy($source_filepath, $dest_filepath))
        {
            if (function_exists('sha1_file'))
            {
                $source_sha1 = sha1_file($source_filepath);
                $dest_sha1 = sha1_file($dest_filepath);

                if ($source_sha1 === $dest_sha1 && ($source_sha1 !== false))
                {
                    DUP_Log::Info("Sha1 of $source_filepath and $dest_filepath match");
                    $ret_val = true;
                }
                else
                {
                    DUP_Log::Info("Sha1 hash of $dest_filepath doesn't match $source_filepath!");
                }
            }
            else
            {
                DUP_Log::Info("sha1_file not present so doing existence check");

                $ret_val = file_exists($dest_filepath);

                if ($ret_val != true)
                {
                    DUP_Log::Info("$dest_filepath doesn't exist after copy!");
                }
            }
        }
        else
        {
            DUP_Log::Info("Problem copying $source_filepath to $dest_filepath");
        }

        return $ret_val;
    }


    /**
     * @todo ASKBOB
     */
    // Copy source to destination while preserving the backup if the destination already exists
    // Note: Intended to be used during package building only since fatal log errors are utilized
    public static function copyWithBackup($source_filepath, $dest_filepath, $backup_filepath)
    {
        DUP_Log::Info("Copy with backup $source_filepath $dest_filepath $backup_filepath");
        if (is_dir($dest_filepath) ||
                (file_exists($dest_filepath) && (is_file($dest_filepath) == false)))
        {
            DUP_Log::error("Trying to copy to a directory ($dest_filepath) not a file which isn't allowed.");
        }

        // In the event there is a file with that same name present we have to save it off into $backup_filepath

        if (file_exists($backup_filepath))
        {
            DUP_Log::Info("Deleting $backup_filepath");
            if (@unlink($backup_filepath))
            {
                DUP_Log::Info("Deleted $backup_filepath");
            }
            else
            {
                DUP_Log::error("ERROR: Couldn't delete backup file $backup_filepath");
            }
        }

        if (file_exists($dest_filepath))
        {
            DUP_Log::Info("Renaming $dest_filepath to $backup_filepath");
            if (@rename($dest_filepath, $backup_filepath))
            {
                DUP_Log::Info("Renamed $dest_filepath to $backup_filepath");
            }
            else
            {
                DUP_Log::error("ERROR: Couldn't rename $dest_filepath $backup_filepath");
            }
        }

        DUP_Log::Info("Copying $source_filepath to $dest_filepath");
        if (copy($source_filepath, $dest_filepath))
        {
            DUP_Log::Info("Copied $source_filepath to $dest_filepath");
        }
        else
        {
            @rename($backup_filepath, $dest_filepath);

            DUP_Log::error("ERROR: Couldn't copy the $source_filepath to $dest_filepath");
        }
    }


}
