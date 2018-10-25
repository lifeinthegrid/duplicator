<?php
defined("ABSPATH") or die("");

/**
 * @copyright 2018 Snap Creek LLC
 * Class for all IO operations
 */
class DUP_IO
{
    /**
     * Safely deletes a file
     *
     * @param string $file	The full filepath to the file
     *
     * @return TRUE on success or if file does not exist. FALSE on failure
     */
    public static function deleteFile($file)
	{
		if (file_exists($file)) {
			if (@unlink($file) === false) {
				DUP_Log::Info("Could not delete file: {$file}");
				return false;
			}
		}
		return true;
	}

	/**
     * Removes a directory recursively except for the root of a WP Site
     *
     * @param string $directory	The full filepath to the directory to remove
     *
     * @return TRUE on success FALSE on failure
     */
	public static function deleteTree($directory)
	{
		$success = true;

        if(!file_exists("{$directory}/wp-config.php")) {
            $filenames = array_diff(scandir($directory), array('.', '..'));

            foreach ($filenames as $filename) {
                if (is_dir("$directory/$filename")) {
                    $success = self::deleteTree("$directory/$filename");
                } else {
                    $success = @unlink("$directory/$filename");
                }

                if ($success === false) {
					break;
                }
            }
        } else {
            return false;
        }

		return $success && @rmdir($directory);
	}




}
