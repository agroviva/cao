<?php

namespace CAO\Controllers;

use CAO\Core\Filesystem;

class FileController
{
    public static function upload()
    {
        $Files = [];

        foreach ($_FILES['UploadFile'] as $key => $value) {
            foreach ($value as $propertyKey => $propertyValue) {
                $Files[$propertyKey][$key] = $propertyValue;
            }
        }
        foreach ($Files as $File) {
            Filesystem::upload($File);
        }
    }
}
