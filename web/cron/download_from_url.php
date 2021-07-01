<?php

include(dirname(dirname(dirname(__FILE__))).'/include/init.php');
include(BP.'/include/curl.php');
include(BP.'/include/curl_fetchurl.php');
include(BP.'/include/process_upload.php');

//a)get the upload info from the file
if (!isset($argv[1])){
    die('info file num argument missing');
}
$file_upload_id=$argv[1];

$infopass_filename = $upload_dir . '/urlupload_infopass_'.$file_upload_id.'.txt';
if(!file_exists($infopass_filename)){
    die('error, info pass file '.$infopass_filename.' does not exist');
}
$infopass_data = unserialize(file_get_contents($infopass_filename));
$upload_progress_info_file = $upload_dir . '/urlupload_progress_'.$file_upload_id.'.txt';

//b)start the upload with curl and write the progress data in a file
downloadDistantFile($infopass_data['uploadie_file_url'],$infopass_data['destination_path'],$file_upload_id);

$upload_progress_data = json_encode(array(
        'status'=>'downloaded'
    ));
file_put_contents($upload_progress_info_file,$upload_progress_data);

//c)once upload is complete run process_upload() to create the upload page and place file in the encoding queue
$upload_hash=process_upload($infopass_data['destination_path'],$infopass_data['title'],$infopass_data['description'],$infopass_data['channel_id'],$infopass_data['user_id']);
if(!$upload_hash){
    $upload_progress_data = json_encode(array(
        'status'=>'error',
        'error_info'=>array(
            'message'=>$text[54],
        )
    ));
}else{
    $upload_progress_data = json_encode(array(
        'status'=>'success',
        'upload_info'=>array(
            'hash'=>$upload_hash,
        )
    ));
}
file_put_contents($upload_progress_info_file,$upload_progress_data);

//d)delete the upload info file
unlink($infopass_filename);