<html>
    <head>
        <title>Barbavid Upload</title>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    </head>
    <body style="background-color:white;color:black;">
	
	
	
<?php
include(dirname(dirname(__FILE__)).'/settings.php');

//I am not sure the session is useful here... sure it shares top domain but its on a separate server physically so session should not contain anything here... to review...
ini_set('session.cookie_domain', '.'.$main_domain);
session_start();


//ini_set("memory_limit", "10240"); //?? should I raise this??
ini_set("max_input_time", 3600*48);
//ini_set("max_execution_time", 3600*48); //it seems this may not be working will try set_time_limit()
set_time_limit(3600*48);


include(dirname(dirname(__FILE__)).'/include/curl.php');


//check upload_code, if correct note user_id, if not just die or something...
$user_id=false;
$channel_id=false;
$check_upload_code_url=$path_to_main_server.'curl/check_upload_code.php';
$check_upload_code_postdata='&code='.urlencode($_POST['upload_code']).'&channel_hash='.urlencode($_POST['channel']);
$check_upload_code_results=curl_post($check_upload_code_url,$check_upload_code_postdata);
if(substr($check_upload_code_results,0,3)=='ok:'){
    $exploded_result=explode(':',$check_upload_code_results);
    if($exploded_result[1]){
        $user_id=$exploded_result[1];
    }
    if($exploded_result[2]){
        $channel_id=$exploded_result[2];
    }
}
if(!$user_id || !$channel_id){
    die('invalid upload code');
}

//===========get language
if(isset($_GET['language']))
{$language=$_GET['language'];}
else if(isset($_COOKIE['language']))
{$language=$_COOKIE['language'];}
else
{$language='en';}
setcookie("language", $language, time()+(4*365*24*3600),'/','.'.$main_domain);
if(!@include(dirname(dirname(__FILE__)).'/include/language_'.urlencode($language).'.php'))
{die('incorrect language');}
//===========get language



$nowtime=time();
$ip=$_SERVER['REMOTE_ADDR'];

$error=0;
$filetoobig=0;
$fileerror=0;
$gotfile=0;
$ffmpeg_nounderstand=0;
$ffmpeg_tooshort=0;
$give_link=0;
$queued_for_encoding=0;
$nospace=0;




//echo '$videos_vault_dir: '.$videos_vault_dir.'<br />';
$freespacebytes=disk_free_space($videos_vault_dir);
$freespaceK=$freespacebytes/1024;
$freespaceM=$freespaceK/1024;
$freespaceinG=$freespaceM/1024;
//echo '$freespaceinG: '.$freespaceinG.'<br />';
//echo '$minimumspaceinGigs: '.$minimumspaceinGigs.'<br />';
if($freespaceinG<$minimumspaceinGigs)
{$nospace=1;}
else
{
    if(isset($argv[1]))
    {
        $filename=$argv[1];
        $posoflastdot=strripos($filename,'.');
        $stuffafter=urlencode(substr($filename,$posoflastdot+1));
        $target_filename=$nowtime.'_'.$ip.'.'.$stuffafter;
        $target=$upload_dir.'/'.$target_filename;
        $moveit='cp '.escapeshellarg($argv[1]).' '.escapeshellarg($target);
        echo htmlspecialchars($moveit).'<br />';
        exec($moveit);
        if(file_exists($target))
        {$gotfile=1;}
        else
        {echo 'error copying file';}

        $_POST['title']=$argv[2];
        $_POST['description']=$argv[3];
    }
    else
    {
		echo '$_FILES["file"]: '; print_r($_FILES["file"]); echo '<br />';
        if($_FILES["file"]["error"]>0)
        {$error=1; $fileerror=1;}
        if($_FILES["file"]["size"]>$maxfilesize)
        {$error=1; $filetoobig=1;}
        if($error==0)
        {
            $filename=$_FILES["file"]["name"];
            $posoflastdot=strripos($filename,'.');
            $stuffafter=urlencode(substr($filename,$posoflastdot+1));
            $target_filename=$nowtime.'_'.$ip.'.'.$stuffafter;
            $target=$upload_dir.'/'.$target_filename;
			
			//debug
			//echo "moving file from ".$_FILES["file"]["tmp_name"]." to $target<br>";
			
            if(!move_uploaded_file($_FILES["file"]["tmp_name"],$target))
            {echo 'error with move_uploaded_file() from '.$_FILES["file"]["tmp_name"].' to '.$target;}
            else
            {$gotfile=1;}
        }
    }




    if($gotfile==1)
    {
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



        if($elthinger==='invalid' || $eltime==='notime' || $elreso==='noreso')
        {
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
        }
        else if($eltime<3)
        {
            $ffmpeg_tooshort=1;
            
            echo 'file was too short according to ffmpeg, delete upload at '.$target.'<br />';
            unlink($target);
        }
        else
        {
            //get uploaded file md5
            $uploaded_file_md5=md5_file($target);
            $ufm_firstchar=substr($uploaded_file_md5,0,1);
            $ufm_secondchar=substr($uploaded_file_md5,1,1);


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

            //=======this part will be replaced
            /*
            //place info about this upload in array (title, description, file md5)
            $upload_info['file_md5']=$uploaded_file_md5;
            $upload_info['title']=base64_encode(mb_substr($_POST['title'],0,$maxtitlelen));
            $upload_info['description']=base64_encode(mb_substr($_POST['description'],0,$maxdesclen));
            $upload_info['suspend']='';
            $upload_info['time']=$nowtime;
            
            $upload_info['popup']=base64_encode(mb_substr($_POST['popup_URL'],0,$maxpopURLlen));
            //serialize array and save in file name with upload hash in uploads_library
            $saveupload=curl_post($path_to_main_server.'add_or_update_element.php','&hash='.urlencode($arandomhash).'&data='.urlencode(serialize($upload_info)).'&index_file='.urlencode('uploads_index.dat').'&');
            if($saveupload!='ok')
            {
                echo '$saveupload: post URL: '.$path_to_main_server.'add_or_update_element.php, data: &hash='.urlencode($arandomhash).'&data='.urlencode(serialize($upload_info)).'&index_file='.urlencode('uploads_index.dat').'& result: '.$saveupload.'<br />';
                die('error, lost connection with main server (2)');
            }
            */
            //==================

            //must record the upload data in the videos table on the main server,
            //if success set the new record video_id on $arandomhash variable

            $upload_info['hash']=$arandomhash;
            $upload_info['file_md5']=$uploaded_file_md5;
            $upload_info['title']=mb_substr($_POST['title'],0,$maxtitlelen);
            $upload_info['description']=mb_substr($_POST['description'],0,$maxdesclen);
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

            //=========that one goes too! new stats tbd later...
            /*
			//create upload_stats entry
			$upload_stats_info['h'] = 0;
			$upload_stats_info['l'] = $nowtime;
			$saveupload=curl_post($path_to_main_server.'add_or_update_element.php','&hash='.urlencode($arandomhash).'&data='.urlencode(serialize($upload_stats_info)).'&index_file='.urlencode('uploads_stats_index.dat').'&');
            */
            //==============================

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
                $file_info['filename']=$target_filename;
                $file_info['file_md5']=$uploaded_file_md5;
                $file_info['thinger']=$elthinger;
                $file_info['time']=$eltime;
                $file_info['oreso']=$elreso;
                file_put_contents($working_dir.'/encoding_queue/'.$nowtime.'_'.$uploaded_file_md5.'.queue_file',serialize($file_info));
                $queued_for_encoding=1;
            }
            //give link to user
            $give_link=1;
        }
    }
}


//show results
$shorezu='';
$remember_success='';
if($filetoobig==1)
{$shorezu=$shorezu.'<div>'.make_text1($maxfilesize/(1024*1024),$_FILES["file"]["size"]/(1024*1024)).'</div>';}
if($fileerror==1)
{$shorezu=$shorezu.'<div>'.$text[2].$_FILES["file"]["error"].'.</div>';}
if($ffmpeg_nounderstand==1)
{$shorezu=$shorezu.'<div>'.$text[3].'<br />'.$ffmpegout2.'</div>';}
if($ffmpeg_tooshort==1)
{$shorezu=$shorezu.'<div>'.$text[4].'</div>';}
if($give_link==1)
{
    $shorezu=$shorezu.'<div>'.$text[5].'<a href="https://'.$main_domain.'/video/'.$arandomhash.'" target="_blank">https://'.$main_domain.'/video/'.$arandomhash.'</a></div>';
    $los_cacados=urlencode('window.top.window.remembersuccess('.json_encode(mb_substr($_POST['title'],0,$maxtitlelen)).',"'.$arandomhash.'");');
	$remember_success='
//alert(decodeURIComponent("'.$los_cacados.'"));
window.top.window.remembersuccess('.json_encode(mb_substr($_POST['title'],0,$maxtitlelen)).',"'.$arandomhash.'");';
}
if($queued_for_encoding==1)
{$shorezu=$shorezu.'<div>'.$text[6].'</div>';}
if($nospace==1)
{$shorezu=$shorezu.'<div>'.$text[7].'</div>';}
?>



        <script language="javascript" type="text/javascript">
            document.domain = "<?php echo $main_domain ?>";
            window.top.window.stopUpload(<?php echo json_encode($shorezu); ?>);<?php echo $remember_success; ?>
        </script>
    </body>
</html>

