<?php
$main_domain='barba.local';
$servername='upload1';
$videohostname='video1';  //the video host that this upload server should send the file to, that makes sense in a scenario where the uploadserver is always on same server than videohost, but when the uploadserver is on a separate server, it may make more sense to implement a system to judge which videohost to send to

//new parameters to allow upload server on different server than videohost!!
$videohost_ison_different_server=0; //if this is set to 1, the successfully encoded video will be sent to the videohost via ftp!
$videohost_ftp_host='';
$videohost_ftp_user='';
$videohost_ftp_password='';

$ffmpeg_path='/usr/local/bin/ffmpeg';
$qt_faststart_path='/usr/local/bin/qt-faststart';
$php_path='/Applications/MAMP/bin/php/php7.4.9/bin/php';

$working_dir = dirname(__FILE__);
$path_to_main_server = 'https://barba.local/';
$upload_dir = dirname(dirname(__FILE__)).'/uploads';
$videos_vault_dir = dirname(dirname(dirname(__FILE__))).'/videohost1/videos';  //should be path to real videovault of videohost if on same server than videohost, but just a temp folder if on a separate server

$maxfilesize = 5 * 1024 * 1024 * 1024;
$maxtitlelen = 100;
$maxdesclen = 3000;
$maxpopURLlen = 1024;
$minimumspaceinGigs = 30;


//======H.264 QUALITY SETTINGS
//bitrate in K (or in M)
//$video_b = '450k';
$video_b = '1M';
$audio_b = '128k';

//The general guideline is to use the slowest preset that you have patience for. Current presets in descending order of speed are: ultrafast,superfast, veryfast, faster, fast, medium, slow, slower, veryslow, placebo.

//$pass1_preset='fast_firstpass';
//$pass2_preset='medium';
//$pass1_preset='ultrafast_firstpass';
//$pass2_preset='ultrafast';

$pass1_preset = 'medium';
$pass2_preset = 'slower';

//$pass1_preset='veryslow_firstpass';
//$pass2_preset='veryslow';
//chunks lenght, split videos in chunks of how many minutes
$chunk_len = 30;
//allow up to x simultaneous encoding processes to take place
$how_many_encodings = 2;

$adminemail='info@barbavid.com';

$allowed_ips=array(
    '127.0.0.1',
    '::1',
    'x.x.x.x', //main server ip
);
