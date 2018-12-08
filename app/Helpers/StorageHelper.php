<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class StorageHelper
{
    /**
     * Save file into storage
     * @param $disk
     * @param $file
     * @return bool|string
     */
    public static function saveFile($file, $dirName, $fileName){

dd('23');        
        #If $file is instance of an uploaded file
        if($file instanceof UploadedFile){

            Storage::disk('s3')->putFileAs($dirName, $file, $fileName, 'public');

            return "$dirName/$fileName";
        } else {

            Storage::disk('s3')->put($dirName.$fileName, (string) $file, 'public');

            return "$dirName/$fileName";
        }

        #File not correct
        return false;
    }

    /**
     * Get file from storage folder based on name
     * @param $disk
     * @param $name
     * @return mixed
     */
    public static function getFile($name){
    	
        $file = Storage::disk('s3')->url($name);
        return $file;
    }

    /**
     * Delete file from storage folder based on name
     * @param $name
     * @return mixed
     */
    public static function delete($name)
    {
        return Storage::disk('s3')->delete($name);
    }
}
