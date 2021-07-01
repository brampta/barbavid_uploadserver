<?php
$main_domain='barba.local';
$servername='upload1';
$videohostname='video1';  //the video host that this upload server should send the file to, that makes sense in a scenario where the uploadserver is always on same server than videohost, but when the uploadserver is on a separate server, it may make more sense to implement a system to judge which videohost to send to

//new parameters to allow upload server on different server than videohost!!
$videohost_ison_different_server=0; //if this is set to 1, the successfully encoded video will be sent to the videohost via ftp! ..lol what?? is this even implemented??
$videohost_ftp_host='';
$videohost_ftp_user='';
$videohost_ftp_password='';

$ffmpeg_path='/usr/local/bin/ffmpeg';
$ffprobe_path='/usr/local/bin/ffprobe';
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


//new encoding params... to replace above.. more flexible..
$skip_encoding_when_possible=true;
$accepted_video_extensions=array('mp4');
$accepted_video_formats=array('h264');
$accepted_audio_formats=array('aac');
$maximum_bytes_per_second=150000;

$two_passes=false;
$video_params='-vcodec libx264 -crf 23';
$audio_params='-ac 2 -acodec aac -ab 128k';
$ffmpeg_options_1stpass='-threads 0 -f rawvideo';
$ffmpeg_options_2ndpass='-metadata:s:v:0 rotate=0 -threads 0';
//if using 2 pass you can replace -crf 10 with something like -b:v 1M or -b:v 4000k,
//but crf is not compatible with 2 pass, crf has range 0-51 for x264 and 4-63 for vpx, the lower the better, 0 is lossless,
//crf aims for a specific quality level but size may vary,
//while using -b:v 4000k enforces a certain max bitrate,
//which means your file size will be more consistent but quality in some parts of the videos that cannot easily be rendered with that bitrate may suffer..

//The general guideline is to use the slowest preset that you have patience for. Current presets in descending order of speed are: ultrafast,superfast, veryfast, faster, fast, medium, slow, slower, veryslow, placebo.
$pass1_preset = 'medium';
$pass2_preset = 'slower';


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
