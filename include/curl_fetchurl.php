<?php


function downloadDistantFile($url, $dest, $statusName){
    var_dump('downloading '.$url);
    var_dump('to '.$dest);
    $options = array(
        CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, 'w'),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_URL => $url,
        CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error

        //this will allow me to track progress of the download with a function!
        //source: https://stackoverflow.com/questions/13958303/curl-download-progress-in-php
        //CURLOPT_PROGRESSFUNCTION => 'downloadDistantFileProgress',
        CURLOPT_PROGRESSFUNCTION => function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($statusName) {
            downloadDistantFileProgress($resource, $downloadSize, $downloaded, $uploadSize, $uploaded, $statusName);
        },
        CURLOPT_NOPROGRESS => false
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $return = curl_exec($ch);

    if ($return === false) {
        return curl_error($ch);
    } else {
        return true;
    }
}

function downloadDistantFileProgress($resource,$download_size, $downloaded, $upload_size, $uploaded, $file_upload_id)
{
    global $upload_dir;

    $upload_progress_info_file = $upload_dir . '/urlupload_progress_'.$file_upload_id.'.txt';
    echo $download_size.' '.$downloaded.' '.$upload_size.' '.$upload_size.'
';
    //$upload_progress_data = ($downloaded * 100) / $download_size;
    $upload_progress_data = json_encode(array(
        'status'=>'downloading',
        'downloading_info'=>array(
            'download_size'=>$download_size,
            'downloaded'=>$downloaded,
            'upload_size'=>$upload_size,
            'uploaded'=>$uploaded,
        )
    ));
    file_put_contents($upload_progress_info_file,$upload_progress_data);
    sleep(1); //that is to pace the writing to the progress file
}

?>