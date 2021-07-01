<?php

/*
 * receives file url, upload info (title, description, channel id) and user id
 *
 * returns a hash is upload is successful or false if not
 */
function process_upload($uploaded_file_path,$title,$description,$channel_id,$user_id){
    //vars from the outside that the function is going to need
    global $ffmpeg_path, $maxtitlelen, $servername, $working_dir, $path_to_main_server;

    //vars from the function that the upload.php script is going to need
    global $ffmpegout2;

    $target=$uploaded_file_path;

    //==========verify file with ffmpeg
    $mycommand=$ffmpeg_path.' -i '.escapeshellarg($target).' 2>&1';

    $ffmpegout=$mycommand.'<br />';
    exec($mycommand,$outputz);
    //print_r($outputz);
    foreach($outputz as $key => $value)
    {$ffmpegout=$ffmpegout.$value.'<br />';}
    echo $ffmpegout.'<br />';

    $elthinger='invalid';
    $posofinput0=stripos($ffmpegout,'Input #0, ');
    if($posofinput0!==false)
    {
        $starofinputthing=$posofinput0+strlen('Input #0, ');
        $virguleafter=stripos($ffmpegout,',',$starofinputthing);
        $elthinger=substr($ffmpegout,$starofinputthing,$virguleafter-$starofinputthing);
    }
    echo '$elthinger: '.$elthinger.'<br />';

    $eltime='notime';
    $posofinput0=stripos($ffmpegout,'Duration: ');
    if($posofinput0!==false)
    {
        $starofinputthing=$posofinput0+strlen('Duration: ');
        $virguleafter=stripos($ffmpegout,',',$starofinputthing);
        $eltime=substr($ffmpegout,$starofinputthing,$virguleafter-$starofinputthing);
        $exploded_time=explode('.',$eltime);
        $exploded_time=explode(':',$exploded_time[0]);
        $eltime=($exploded_time[0]*3600)+($exploded_time[1]*60)+$exploded_time[2];
    }
    echo '$eltime: '.$eltime.'<br />';

    $elreso='noreso';
    $find_reso=preg_match('/ ([0-9]*)x([0-9]*)( |,)/',$ffmpegout,$matches);
    if($find_reso!=0)
    {
        print_r($matches); echo '<br />';
        $elreso=$matches[1].'x'.$matches[2];
    }
    echo '$elreso: '.$elreso.'<br />';
    //==========end of verify file with ffmpeg


    if($elthinger==='invalid' || $eltime==='notime' || $elreso==='noreso')
    {
        //bad file...

        $ffmpeg_nounderstand=1;

        $mycommand='ffmpeg -formats 2>&1';
        echo $mycommand;

        $ffmpegout2='';
        exec($mycommand,$outputz2);
        //print_r($outputz);
        foreach($outputz2 as $key => $value)
        {$ffmpegout2=$ffmpegout2.$value.'<br />';}
        $starofstuff=stripos($ffmpegout2,'File formats:');
        if($starofstuff===false)
        {$ffmpegout2='error';}
        else
        {
            $starofstuff=$starofstuff+strlen('File formats:')+6;
            $ffmpegout2=substr($ffmpegout2,$starofstuff);
        }

        echo 'file was not understood by ffmpeg, delete upload at '.$target.'<br />';
        unlink($target);

        return false;
    }
    else if($eltime<3)
    {
        //video too short..

        $ffmpeg_tooshort=1;

        echo 'file was too short according to ffmpeg, delete upload at '.$target.'<br />';
        unlink($target);

        return false;
    }
    else
    {
        //video is good..

        //get uploaded file md5
        $uploaded_file_md5=md5_file($target);
        $ufm_firstchar=substr($uploaded_file_md5,0,1);
        $ufm_secondchar=substr($uploaded_file_md5,1,1);

        //===========Create upload==============
        //create unique hash to represent this upload
        $arandomhash='';
        $countturns=0;
        $maxturns=3;
        while($arandomhash=='' && $countturns<$maxturns)
        {
            $countturns++;
            $arandomhash=get_content_of_url($path_to_main_server.'curl/give_free_hash.php');
        }
        //sanitize that hash a bit!!
        $arandomhash = substr( preg_replace('#[^a-zA-Z0-9_]#', '', $arandomhash), 0, 12);
        if($arandomhash=='')
        {die('error, lost connection with main server (1)');}

        //must record the upload data in the videos table on the main server,
        //if success set the new record video_id on $arandomhash variable
        $upload_info['hash']=$arandomhash;
        $upload_info['file_md5']=$uploaded_file_md5;
        $upload_info['title']=mb_substr($title,0,$maxtitlelen);
        $upload_info['description']=mb_substr($description,0,$maxdesclen);
        $upload_info['user_id']=$user_id;
        $upload_info['channel_id']=$channel_id;

        $id = false;
        $saveupload_url=$path_to_main_server.'curl/add_or_update_element.php';
        $saveupload_postdata='&data='.urlencode(serialize($upload_info)).'&table_name=videos';
        $saveupload=curl_post($saveupload_url,$saveupload_postdata);
        if(substr($saveupload,0,3)=='ok:'){
            $exploded_result = explode(':',$saveupload);
            if(isset($exploded_result[1]) && $exploded_result[1]>1){
                $id = $exploded_result[1];
            }
        }
        if(!$id){
            echo '$saveupload: post URL: '.$saveupload_url.', data: '.$saveupload_postdata.' result: '.$saveupload.'<br />';
            die('error, lost connection with main server (2)');
        }

        //==========Send video to encoding!=======
        //check if we already have file with this file md5 in videos_library
        $video_exists='';
        $countturns=0;
        $maxturns=3;
        while($video_exists=='' && $countturns<$maxturns)
        {
            $countturns++;
            $video_exists=get_content_of_url($path_to_main_server.'curl/element_exists.php?hash='.urlencode($uploaded_file_md5).'&index_file='.urlencode('videos_index.dat'));
        }
        if($video_exists!='true' && $video_exists!='false')
        {die('error, lost connection with main server (3)');}
        if($video_exists=='true')
        {
            //if yes: delete uploaded file
            echo 'we already have this file, delete upload at '.$target.'<br />';
            unlink($target);
            //add this upload to video info
            $add_upload_to_video=get_content_of_url($path_to_main_server.'curl/add_upload_to_video.php?upload='.urlencode($arandomhash).'&video='.urlencode($uploaded_file_md5));
        }
        else if($video_exists=='false')
        {
            //if no:
            ////place file in video_library with file md5 for name and "queued for encoding (filename)" for content
            $video_info['server']=$servername;
            $video_info['time']=$eltime;
            $video_info['oreso']=$elreso;
            $video_info['reso']='?';
            $video_info['chunks']=array();
            $video_info['uploads']=array($arandomhash);
            $savevideo=curl_post($path_to_main_server.'curl/add_or_update_element.php','&hash='.urlencode($uploaded_file_md5).'&data='.urlencode(serialize($video_info)).'&index_file='.urlencode('videos_index.dat').'&');

            ////add uploaded file to encoding queue
            $file_info['filename']=basename($target);
            $file_info['file_md5']=$uploaded_file_md5;
            $file_info['thinger']=$elthinger;
            $file_info['time']=$eltime;
            $file_info['oreso']=$elreso;
            file_put_contents($working_dir.'/encoding_queue/'.time().'_'.$uploaded_file_md5.'.queue_file',serialize($file_info));
            $queued_for_encoding=1;
        }

        //give link to user
        //$give_link=1;
        return $arandomhash;

    }

}