<?php

namespace huqis\helper;

use huqis\exception\TemplateException;

/**
 * File helper
 */
class FileHelper {

    /**
     * Reads a file
     * @param string $file Path to the file
     * @return boolean|string Contents of the file if succeeded, false otherwise
     */
    public static function read($file) {
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        return file_get_contents($file);
    }

    /**
     * Writes a file
     * @param string $file Path to the file
     * @param string $contents Contents for the file
     * @return null
     * @throws \huqis\exception\TemplateException when the directory or
     * file could not be created
     */
    public static function write($file, $contents) {
        $directory = mb_substr($file, 0, strrpos($file, '/'));

        if (!file_exists($directory) || !is_dir($directory)) {
            $result = @mkdir($directory, 0755, true);
            if ($result === false) {
                $error = error_get_last();

                throw new TemplateException('Could not create ' . $directory . ': ' . $error['message']);
            }
        }

        $result = @file_put_contents($file, $contents);
        if ($result === false) {
            $error = error_get_last();

            throw new TemplateException('Could not write ' . $file . ': ' . $error['message']);
        }
    }

    /**
     * Deletes a file or directory
     * @param string $file Path to the file or directory
     * @return null
     * @throws \huqis\exception\TemplateException when the file or
     * directory could not be deleted
     */
    public static function delete($file) {
        if (is_dir($file)) {
            self::deleteDirectory($file);
        } elseif (file_exists($file)) {
            self::deleteFile($file);
        }
    }

    /**
     * Deletes a file
     * @param string $file Path to the file
     * @return null
     * @throws \huqis\exception\TemplateException when the file could
     * not be deleted
     */
    private static function deleteFile($file) {
        $result = @unlink($file);
        if (!$result) {
            $error = error_get_last();

            throw new TemplateException('Could not delete ' . $file . ': ' . $error['message']);
        }
    }

    /**
     * Deletes a directory
     * @param string $file Path to the directory
     * @return null
     * @throws \huqis\exception\TemplateException when the directory
     * could not be deleted
     */
    private static function deleteDirectory($file) {
        if (!($handle = @opendir($file))) {
            throw new TemplateException('Could not delete ' . $file . ': directory could not be read');
        }

        while (($f = readdir($handle)) !== false) {
            if ($f != '.' && $f != '..') {
                self::delete($file . '/' . $f);
            }
        }

        closedir($handle);

        $result = @rmdir($file);
        if (!$result) {
            $error = error_get_last();

            throw new TemplateException('Could not delete ' . $path . ': ' . $error['message']);
        }
    }

}
