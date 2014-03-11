<?php
class Uploader
{
    public static $allow_type = 'jpg|jpeg|gif|png';

    public static function file($file, $to_filename)
    {
        if (!is_uploaded_file($file))
        {
            return false;
        }
        $save_dir = dirname($to_filename);
        if (!file_exists($save_dir) && !@mkdir($save_dir, 0777, true))
        {
            return false;
        }
        if (!@copy($file, $to_filename) && !@move_uploaded_file($file, $to_filename))
        {
            return false;
        }

        return true;
    }

    public static function data($data, $to_filename)
    {
        $save_dir = dirname($to_filename);
        if (!file_exists($save_dir) && !@mkdir($save_dir, 0777, true))
        {
            return false;
        }
        if (!@file_put_contents($to_filename, $data))
        {
            return false;
        }

        return basename($to_filename);
    }
}
?>