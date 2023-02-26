<?php

use Illuminate\Support\Facades\Storage;

function importImage($userId, $data, $type)
{
    $date = date('Y-m-d');
    $path = "$userId/$date/$type";
    $photo = $data->get('photo');
    $name = $userId . '-' . time() . '.' . explode('/', explode(':', substr($photo, 0, strpos($photo, ';')))[1])[1];
    Storage::putFileAs('/public/' . $path, $photo, $name);
    return $path . '/' . $name;
}

function activityImage($data)
{
    $path = "activity";
    $photo = $data->get('photo');
    $name = $data->activity_id . time() . '.' . explode('/', explode(':', substr($photo, 0, strpos($photo, ';')))[1])[1];
    Storage::putFileAs('/public/' . $path, $photo, $name);
    return $path . '/' . $name;
}

function photoProfile($data, $model)
{
    $path = "profile";
    $photo = $data->get('photo');
    $name = $model->id . time() . '.' . explode('/', explode(':', substr($photo, 0, strpos($photo, ';')))[1])[1];
    Storage::putFileAs('/public/' . $path, $photo, $name);
    return $path . '/' . $name;
}

function imageUrl($path, $image)
{
    $url = null;
    if (env('STORAGE_PUBLIC')) {
        $url = asset(Storage::url('public/' . $path . $image->storage));
    } else 
        if (env('UPLOAD_PUBLIC')) {
        if (env('APP_URL')) {
            $url = asset(env('MASTER_URL') . '/uploads/' . $path . $image->fullname);
        } else
            $url = asset(env('MASTER_URL') . '/uploads/' . $path . $image->fullname);
    }
    return $url;
}
