<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo '<pre>'.print_r($_POST,true).'</pre>';
?><html>
    <head>
        <title>Barbavid Upload</title>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    </head>
    <body style="background-color:white;color:black;">
	
	
	
<?php
include(dirname(dirname(__FILE__)).'/include/init.php');
include(BP.'/include/process_upload.php');

//I am not sure the session is useful here... sure it shares top domain but its on a separate server physically so session should not contain anything here... to review...
ini_set('session.cookie_domain', '.'.$main_domain);
session_start();


//ini_set("memory_limit", "10240"); //?? should I raise this??
ini_set("max_input_time", 3600*48);
//ini_set("max_execution_time", 3600*48); //it seems this may not be working will try set_time_limit()
set_time_limit(3600*48);


include(dirname(dirname(__FILE__)).'/include/curl.php');


//check upload_code, if correct note user_id, if not just die or something...
//TODO need to add validation user has rights on this channel
$user_id=false;
$channel_id=false;
$check_upload_code_url=$path_to_main_server.'curl/check_upload_code.php';
if(!isset($_POST['upload_code']) || !isset($_POST['channel'])){
    die('upload_code or channel post data missing');
}
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
$langfile=dirname(dirname(__FILE__)).'/include/language_'.urlencode($language).'.php';
if(!include($langfile))
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
$bg_upload_started=0;



//echo '$videos_vault_dir: '.$videos_vault_dir.'<br />';
$freespacebytes=disk_free_space($videos_vault_dir);
$freespaceK=$freespacebytes/1024;
$freespaceM=$freespaceK/1024;
$freespaceinG=$freespaceM/1024;
echo '$freespaceinG: '.$freespaceinG.'<br />';
echo '$minimumspaceinGigs: '.$minimumspaceinGigs.'<br />';
if($freespaceinG<$minimumspaceinGigs)
{
    $nospace=1;
    echo 'no space<br>'
;}
else
{
    echo 'has space<br>';
    if(isset($argv[1]))
    {
        //this option is outdated and not supported anymore, it doesnt have the support for channel and user id, cant use for the moment..
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
        if($_POST['file_or_url']=='url'){
            /*
             * first test, this did not work out was getting 500 error, turns out it was due to upload_progress
             * I solved that (by not sending the UPLOAD_IDENTIFIER for url upload
             * but I still [comment ends like that.. ok.. anyways this first block was first attempt at file upload
             * but I ended redoing it all over in bg mode lower... much better like this..]
             *
            //try to download file at url now!
            echo 'downloading form '.$_POST['url'].'<br>';
            //$posoflastdot=strripos($_POST['url'],'.');
            //$stuffafter=urlencode(substr($_POST['url'],$posoflastdot+1));
            //$target_filename=$nowtime.'_'.$ip.'.'.$stuffafter;
            $target_filename=$nowtime.'_'.$ip.'.'.md5($_POST['url']);
            echo 'saving to '.$target_filename.'<br>';
            $target = $upload_dir . '/' . $target_filename;
            downloadDistantFile($_POST['url'], $target);
            */

            //=====thats the final plan that was followed to successfully implement url upload=====
            //this is a new feature, download from URL
            //1-will save all the upload info in a file on the server
            //2-will launch a background terminal process (with the file name from step 1 as argument)
            //3-the bg process will
                //a)get the upload info from the file
                //b)start the upload with curl and write the progress data in a file
                //c)once upload is complete run process_upload() to create the upload page and place file in the encoding queue
                //d)delete the upload info file
            //4-this script will stop here, all the rest of the script is only for standard file upload
            //5-the upload progress called by ajax on the upload page will know that the file upload is url and behave differently:
                //a)instead of calling the upload_progress() function to get progress it will look in the file from 3.b)
                //b)instead of waiting for the upload page to stop the process, in this mode its the upload progress that will mark the upload as complete

            //save all the upload info in a file on the server
            $file_upload_id=time().'_'.rand(0,999999);
            $infopass_filename = $upload_dir . '/urlupload_infopass_'.$file_upload_id.'.txt';
            $infopass_data = array(
                'uploadie_file_url'=>$_POST['url'],
                'destination_path'=>$upload_dir. '/' .$file_upload_id,
                'title'=>$_POST['title'],
                'description'=>$_POST['description'],
                'channel_id'=>$channel_id,
                'user_id'=>$user_id,
                'language'=>$language,
            );
            file_put_contents($infopass_filename,serialize($infopass_data));

            //launch a background terminal process (with the file name from step 1 as argument)
            $command = $php_path.' ' . $working_dir . '/web/cron/download_from_url.php ' . $file_upload_id;
            echo 'executing: ' . $command . '<br>';
            //that is the initial way of calling the command, still in use at the moment..
            exec("$command > /dev/null &", $arrOutput);
            //calling the command like this allowed to see the output of the command in the admin iframe
            //but it broke the ajax download % meter, so going back to initial way for now
            /*exec("$command 2>&1", $output, $return_var);
            foreach($output as $outputline){
                echo $outputline.'<br>';
            }*/
            
            $bg_upload_started=1;

        }else {

            //this is the classic case, standard file upload...
            //at the time this runs, the upload is already done (it could have been long depending on the file size..)
            //the function process_upload() will run below which will place the uploaded file in the queue and create the upload page
            //then the page will call a js on the parent which will tell the upload monitoring JS that the upload is done

            echo '$_FILES["file"]: ';
            print_r($_FILES["file"]);
            echo '<br />';
            if ($_FILES["file"]["error"] > 0) {
                $error = 1;
                $fileerror = 1;
            }
            //if($_FILES["file"]["size"]>$maxfilesize)
            //{$error=1; $filetoobig=1;}
            if ($error == 0) {
                $filename = $_FILES["file"]["name"];
                $posoflastdot = strripos($filename, '.');
                $stuffafter = urlencode(substr($filename, $posoflastdot + 1));
                $target_filename = $nowtime . '_' . $ip . '.' . $stuffafter;
                $target = $upload_dir . '/' . $target_filename;

                //debug
                //echo "moving file from ".$_FILES["file"]["tmp_name"]." to $target<br>";

                if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target)) {
                    echo 'error with move_uploaded_file() from ' . $_FILES["file"]["tmp_name"] . ' to ' . $target;
                } else {
                    $gotfile = 1;
                }
            }
        }
    }




    if($gotfile==1)
    {
        $arandomhash = process_upload($target,$_POST['title'],$_POST['description'],$channel_id,$user_id);
        if($arandomhash){
            $give_link=1;
        }
    }
}


//show results (for standard upload only...)
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

if($bg_upload_started==1){
    $remember_success='
    console.log("setting file upload (url) id: '.$file_upload_id.'");
    window.top.window.remember_file_upload_id("'.$file_upload_id.'");';
}

if($queued_for_encoding==1)
{$shorezu=$shorezu.'<div>'.$text[6].'</div>';}
if($nospace==1)
{$shorezu=$shorezu.'<div>'.$text[7].'</div>';}
?>



        <script language="javascript" type="text/javascript">
            document.domain = "<?php echo $main_domain ?>";
            <?php if($_POST['file_or_url']!='url'){ ?>
                window.top.window.stopUpload(<?php echo json_encode($shorezu); ?>,"from: <?php echo $servername.'.'.$main_domain; ?>/upload.php (upload finished)");
            <?php } ?>
            <?php echo $remember_success; ?>
        </script>
    </body>
</html>

