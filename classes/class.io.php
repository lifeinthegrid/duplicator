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
}
