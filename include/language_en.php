<?php
function make_text1($maxfilesize,$yourfilesize){
    $string = 'File too big. Max file size: '.$maxfilesize.'MB, your file size: '.$yourfilesize.'MB.';
    return $string;
}

$text[2]='File upload error: ';
$text[3]='The file upload was successful but ffmpeg was not able to understand the video file that you have uploaded. It is able to understand the following formats:';
$text[4]='The file upload was successful but according to ffmpeg the duration of the video that you have uploaded was less than 3 seconds. This either means that the file that you have uploaded was not actually a video or that it was a very very short video. Either way only videos of at least 3 seconds are accepted.';
$text[5]='Your video was uploaded successfully and can be viewed at the following URL: ';
$text[6]='Note that while your video was uploaded successfully, it will need to go through the encoding process before it can be watched.';
$text[7]='The upload cannot be received because this video server is full, try again later.';


$text[40]='bytes uploaded';
$text[41]='transfer speed';
$text[42]='time elapsed';
$text[43]='estimated time left';
$text[44]='average';
$text[45]='KB/s';
$text[46]='The upload cannot be received because this video server is full, try again later.';


$text[51]='waiting for server to grab file.';
$text[52]='downloading file';
$text[53]='downloaded, saving file';
$text[54]='error processing file';

