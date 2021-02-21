<?php

include('conf.php');
proc_nice(19);


if(isset($_GET['argv1']))
{$argv[1]=$_GET['argv1'];}


if (!isset($argv[1]))
{
    $maybe_instancenumber = 1;
    while ($maybe_instancenumber <= $how_many_encodings)
    {

        $grepper = "ps aux | grep encode.php";
        echo '
GREP: ' . $grepper . '
';
        unset($shoshit);
        exec($grepper, $shoshit);
        echo(implode('
', $shoshit)); echo('
');

        $thisinstanceisrunning = 0;

        foreach ($shoshit as $key => $value)
        {
            if (stripos($value, 'encode.php ' . $maybe_instancenumber) !== false && stripos($value, ' grep ') === false && stripos($value, ' sh -c ') === false)
            { $thisinstanceisrunning = 1; }
        }

        if ($thisinstanceisrunning == 0)
        {
            $command = 'php ' . $working_dir . '/encode.php ' . $maybe_instancenumber;
            echo $command . '
';


            exec("$command > /dev/null &", $arrOutput);

            break;
        }



        $maybe_instancenumber++;
    }
    die();
}







$nowtime = time();




//delete too old inprogress file
$oldest_acc_time = $nowtime - (72 * 3600);
informzz('find and delete too old inprogress files<br />');
$inprogressfiles = glob($working_dir . '/encodeinprog_*');
foreach ($inprogressfiles as $key => $value)
{
    informzz($value . '<br />');
    $exploded_byslashes = explode('/', $value);
    $totalchunks = count($exploded_byslashes);
    $lastchunk = $exploded_byslashes[$totalchunks - 1];
    $explode_by_undersc = explode('_', $lastchunk);
    $eltimio = abs($explode_by_undersc[1]);
    if ($eltimio < $oldest_acc_time)
    {
        informzz('will unlink ' . $value . '<br />');
        unlink($value);
    }
}








$infoforlogfile = time() . '===================================================
';

function informzz($info)
{
    echo $info;
    global $infoforlogfile;
    $infoforlogfile = $infoforlogfile . str_replace('<br />', '
', $info);
}

include('curl.php');


////exec('ps aux | grep meancoderzzz',$shoshit);
//exec('ps aux | grep ffmpest', $shoshit);
//informzz(implode('<br />', $shoshit)); informzz('<br />');
//
//$mencoder_running = 0;
//foreach ($shoshit as $key => $value)
//{
//    if (stripos($value, ' grep ') === false && stripos($value, ' sh -c ') === false)
//    { $mencoder_running++; }
//}
//informzz('$mencoder_running: ' . $mencoder_running . '<br />');
//if ($mencoder_running >= $how_many_encodings)
//{ die('there are already ' . $how_many_encodings . ' or more instances of mencoder running, dying...'); }
//$instance_number=$mencoder_running+1;
//$instance_number = $nowtime . '_' . rand(111, 999);
$numtodefine_instance = $argv[1];
$instance_number = $nowtime . '_' . $numtodefine_instance;
informzz('$instance_number: ' . $instance_number . '<br />');



//check if already encodeinprog_ file for this instance
$gotqueuealreadyinprogressio = 0;
$inprogressfiles = glob($working_dir . '/encodeinprog_*');
foreach ($inprogressfiles as $key => $value)
{
    informzz($value . '<br />');
    $exploded_byslashes = explode('/', $value);
    $totalchunks = count($exploded_byslashes);
    $lastchunk = $exploded_byslashes[$totalchunks - 1];
    $explode_by_undersc = explode('_', $lastchunk);
    $elinstancenumberio = abs($explode_by_undersc[2]);
    if ($elinstancenumberio == $numtodefine_instance)
    {
        $gotqueuealreadyinprogressio = 1;
        informzz($value . ' is a progress file that belongs to this instance, will try running this again<br />');
        //warn admin about failure
        $headers = "From: retry-intrptd-encdng@" . $servername . ".barbavid.com";
        mail($adminemail, 'retrying interrupted encoding', $value . ' is a progress file that belongs to this instance, will try running this again', $headers);

        $stuffin_inprogress_file = file_get_contents($value);
        informzz('contents of inprogress_ file: ' . htmlspecialchars($stuffin_inprogress_file) . '<br />');

        $exploded_stuffin_inprogress_file = explode(' ', $stuffin_inprogress_file);
        foreach ($exploded_stuffin_inprogress_file as $key2 => $value2)
        {
            if (substr($value2, 0, strlen('queuefile:')) == 'queuefile:')
            {
                $first_element = substr($value2, strlen('queuefile:'));
                $pathofqueuefileinprogfolder = $first_element;

                $filecontents = file_get_contents($first_element);
                $file_info = unserialize($filecontents);
                informzz(implode('<br />', $file_info)); informzz('<br />');
            }
        }

        $inprogress_filename = $value;


        break;
    }
}


if ($gotqueuealreadyinprogressio == 0)
{
    //die('stop here for testing, was gonna get new queue element...<br />');

    $stuff_in_encoding_queue = glob($working_dir . '/encoding_queue/*');
    print_r($stuff_in_encoding_queue);
    if (count($stuff_in_encoding_queue) == 0)
    { die('queue empty, dying...'); }




    $first_element = $stuff_in_encoding_queue[0];
    $filecontents = file_get_contents($first_element);
    $file_info = unserialize($filecontents);
    print_r($file_info);
    //die();
    informzz(implode('<br />', $file_info)); informzz('<br />');

    $pathofqueuefileinprogfolder = str_replace('/encoding_queue/', '/encoding_queue_inprogress/', $first_element);

//copy queue element in encoding_queue_inprogress
    $remove_queue_element = 'cp ' . escapeshellarg($first_element) . ' ' . escapeshellarg($pathofqueuefileinprogfolder);
    unset($outputz);
    exec($remove_queue_element . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }

//remove queue element from encoding_queue
    $remove_queue_element = 'rm ' . escapeshellarg($first_element);
    unset($outputz);
    exec($remove_queue_element . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }

    $inprogress_filename = $working_dir . '/encodeinprog_' . $instance_number;
}



if (!$file_info || $file_info['file_md5'] == '' || strlen($file_info['file_md5']) < 10)
{ die('error, invalid $file_info or $file_info[\'file_md5\'], cannot continue...'); }





//$completion_prct=0;
//file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
file_put_contents($inprogress_filename, $file_info['file_md5'] . ' startup queuefile:' . $pathofqueuefileinprogfolder);


//create md5 folder in videos vault
$firstchar = substr($file_info['file_md5'], 0, 1);
$secondchar = substr($file_info['file_md5'], 1, 1);
if (!file_exists($videos_vault_dir . '/' . $firstchar))
{ mkdir($videos_vault_dir . '/' . $firstchar); }
chmod($videos_vault_dir . '/' . $firstchar, 0777);
if (!file_exists($videos_vault_dir . '/' . $firstchar . '/' . $secondchar))
{ mkdir($videos_vault_dir . '/' . $firstchar . '/' . $secondchar); }
chmod($videos_vault_dir . '/' . $firstchar . '/' . $secondchar, 0777);
if (!file_exists($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5']))
{ mkdir($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5']); }
chmod($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'], 0777);

//if (!file_exists($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5']))
//{die('did not successfully create folder...');}
//else
//{echo 'folder successfully created!
//';}



$filetotreat = $upload_dir . '/' . $file_info['filename'];
$posoflastdot = strripos($filetotreat, '.');
$current_extensio = substr($filetotreat, $posoflastdot + 1);




$cdtoworkingdir = 'cd ' . $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'];



////make a copy of the file with ffmpeg for *sanitazation
//$reencoded_file_name=$videos_vault_dir.'/'.$firstchar.'/'.$secondchar.'/'.$file_info['file_md5'].'/reencoded.'.$current_extensio;
//$reencode='/var/www/barbavid/uploadserver1/ffmpest -v 0 -i '.escapeshellarg($filetotreat).' -vcodec copy -acodec copy  -f '.escapeshellarg($file_info['thinger']).' -y '.escapeshellarg($reencoded_file_name);
//informzz(htmlspecialchars('-----'.$reencode).'<br />'); flush();
//$op_starttime=microtime(true);
//unset($outputz);
//exec($cdtoworkingdir.'; nice -n 19 '.$reencode.' 2>&1',$outputz);
//foreach($outputz as $key => $value)
//{informzz($value.'<br />');}
//$op_endtime=microtime(true);
//$op_time=$op_endtime-$op_starttime;
//informzz('+++operation took '.$op_time.' seconds.<br />');
//safer way, encode original straight to h.264 then just work on my own h.264
$problem_withreencoded = 0;
$reencoded_file_name = $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/reencoded.mp4';

file_put_contents($inprogress_filename, $file_info['file_md5'] . ' pass1 ' . $file_info['time'] . ' ' . $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/pass1_process.txt queuefile:' . $pathofqueuefileinprogfolder);

//encode first pass of orichunk
//$pass1 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -an -pass 1 -vcodec libx264 -vpre ' . $pass1_preset . ' -b ' . $video_b . ' -threads 0 -f rawvideo -y /dev/null';
$pass1 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -an -pass 1 -vcodec libx264 -preset ' . $pass1_preset . ' -b:v ' . $video_b . ' -threads 0 -f rawvideo -y /dev/null';
$pass1 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -an -pass 1 -vcodec libx264 -preset ' . $pass1_preset . ' -b:v ' . $video_b . ' -threads 0 -f rawvideo -y /dev/null';
$pass1 = $cdtoworkingdir . '; nice -n 19 ' . $pass1 . ' 2> ' . $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/pass1_process.txt 1> /dev/null';
informzz('-----' . $pass1 . '<br />'); flush();
$op_starttime = microtime(true);
//unset($outputz);
//exec($cdtoworkingdir.'; nice -n 19 '.$pass1.' 2>&1',$outputz);
//foreach($outputz as $key => $value)
//{informzz($value.'<br />');}
//exec($pass1.' 2>&1');
passthru($pass1);
$op_endtime = microtime(true);
$op_time = $op_endtime - $op_starttime;
informzz('+++operation took ' . $op_time . ' seconds.<br />');

file_put_contents($inprogress_filename, $file_info['file_md5'] . ' pass2 ' . $file_info['time'] . ' ' . $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/pass2_process.txt queuefile:' . $pathofqueuefileinprogfolder);

//encode second pass of orichunk
//$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -acodec libfaac -ab ' . $audio_b . ' -pass 2 -vcodec libx264 -vpre ' . $pass2_preset . ' -b ' . $video_b . ' -threads 0 -y ' . escapeshellarg($reencoded_file_name);
//$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -acodec libfaac -ab ' . $audio_b . ' -pass 2 -vcodec libx264 -preset ' . $pass2_preset . ' -b:v ' . $video_b . ' -threads 0 -y ' . escapeshellarg($reencoded_file_name);
//codec libfaac wouldnt work anymore, found out that libfdk_aac is supposedly much better
//$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -acodec libfdk_aac -ab ' . $audio_b . ' -pass 2 -vcodec libx264 -preset ' . $pass2_preset . ' -b:v ' . $video_b . ' -threads 0 -y ' . escapeshellarg($reencoded_file_name);
//rotated videos (filmed from my phone) would not play in flash player, adding parameters to handle this
//$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -acodec libfdk_aac -ab ' . $audio_b . ' -pass 2 -vcodec libx264 -preset ' . $pass2_preset . ' -b:v ' . $video_b . ' -metadata:s:v:0 rotate=0 -threads 0 -y ' . escapeshellarg($reencoded_file_name);
//added -ac 2 to mix down surround audio to stereo (could help with volume problem of surround audio files)
//$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -ac 2 -acodec libfdk_aac -ab ' . $audio_b . ' -pass 2 -vcodec libx264 -preset ' . $pass2_preset . ' -b:v ' . $video_b . ' -metadata:s:v:0 rotate=0 -threads 0 -y ' . escapeshellarg($reencoded_file_name);
//changing audio codec from libfdk_aac to native aac for now (read: https://askubuntu.com/questions/544533/how-to-install-avconv-with-libfdk-aac-on-ubuntu-14-04)
$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -ac 2 -acodec aac -ab ' . $audio_b . ' -pass 2 -vcodec libx264 -preset ' . $pass2_preset . ' -b:v ' . $video_b . ' -metadata:s:v:0 rotate=0 -threads 0 -y ' . escapeshellarg($reencoded_file_name);
//I will now try adding some compression!! did not worked, caused weird crackly shitsound!!!
//$pass2 = $ffmpeg_path . ' -i ' . escapeshellarg($filetotreat) . ' -ac 2 -acodec libfdk_aac -ab ' . $audio_b . ' -af compand=".3|.3:1|1:-90/-60|-60/-40|-40/-30|-20/-20:6:0:-90:0.2" -pass 2 -vcodec libx264 -preset ' . $pass2_preset . ' -b:v ' . $video_b . ' -metadata:s:v:0 rotate=0 -threads 0 -y ' . escapeshellarg($reencoded_file_name);
$pass2 = $cdtoworkingdir . '; nice -n 19 ' . $pass2 . ' 2> ' . $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/pass2_process.txt 1> /dev/null';
informzz('-----' . $pass2 . '<br />'); flush();
$op_starttime = microtime(true);
//unset($outputz);
//exec($cdtoworkingdir.'; nice -n 19 '.$pass2.' 2>&1',$outputz);
//foreach($outputz as $key => $value)
//{informzz($value.'<br />');}
passthru($pass2);
$op_endtime = microtime(true);
$op_time = $op_endtime - $op_starttime;
informzz('+++operation took ' . $op_time . ' seconds.<br />');

file_put_contents($inprogress_filename, $file_info['file_md5'] . ' split queuefile:' . $pathofqueuefileinprogfolder);

$remove_passlogfile = 'rm ' . $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/*.log*';
informzz(htmlspecialchars($remove_passlogfile) . '<br />'); flush();
unset($outputz);
exec($cdtoworkingdir . '; nice -n 19 ' . $remove_passlogfile . ' 2>&1', $outputz);
foreach ($outputz as $key => $value)
{ informzz($value . '<br />'); }


//get time again from reencoded copy (and update in dat system if different)
$mycommand = $ffmpeg_path . ' -i ' . escapeshellarg($reencoded_file_name) . ' 2>&1';
$ffmpegout = $mycommand . '<br />';
unset($outputz);
exec($mycommand, $outputz);
foreach ($outputz as $key => $value)
{ $ffmpegout = $ffmpegout . $value . '<br />'; }
informzz($ffmpegout . '<br />');

$eltime = 'notime';
$posofinput0 = stripos($ffmpegout, 'Duration: ');
if ($posofinput0 !== false)
{
    $starofinputthing = $posofinput0 + strlen('Duration: ');
    $virguleafter = stripos($ffmpegout, ',', $starofinputthing);
    $eltime = substr($ffmpegout, $starofinputthing, $virguleafter - $starofinputthing);
    $exploded_time = explode('.', $eltime);
    $exploded_time = explode(':', $exploded_time[0]);
    $eltime = ($exploded_time[0] * 3600) + ($exploded_time[1] * 60) + $exploded_time[2];
}
informzz('$eltime: ' . $eltime . '<br />');
$set_server_time_setter = '';
if ($eltime != $file_info['time'])
{
    informzz('initial ffmpeg check said time was ' . $file_info['time'] . ' but on re-encoded time is ' . $eltime . ' will update time on dat system<br />');
    $file_info['time'] = $eltime;
    $set_server_time_setter = '&time=' . urlencode($eltime);
}

$elreso = 'noreso';
$find_reso = preg_match('/ ([0-9]*)x([0-9]*)( |,)/', $ffmpegout, $matches);
if ($find_reso != 0)
{
    print_r($matches); echo '<br />';
    $elreso = $matches[1] . 'x' . $matches[2];
}
informzz('$elreso: ' . $elreso . '<br />');
if ($elreso != $file_info['oreso'])
{
    informzz('initial ffmpeg check said reso was ' . $file_info['oreso'] . ' but on re-encoded reso is ' . $elreso . ' will update reso on dat system<br />');
    $newreso = $elreso;
} else
{ $newreso = $file_info['oreso']; }
//work on recopied file instead of original


$missing_chunks = 0;


//for safety, stop here if new re-grabbed time or reso isnt right
if ($eltime === 'notime' || $elreso === 'noreso')
{
    informzz('Error, re-encoded was not understood by ffmpeg: $eltime: ' . $eltime . ', $elreso: ' . $elreso . '.<br />');
    $problem_withreencoded = 1;
    $missing_chunks = 1;
}





$filetimeinminutes = ceil($file_info['time'] / 60);
$howmany_chunks = ceil($filetimeinminutes / $chunk_len);
$chunks_len = ceil($filetimeinminutes / $howmany_chunks);




$countchunks = 0;
$maxchunks = 5000;
$end_ofchunk_biggerthan_end_ofvid = 0;


$remember_chunks_array = array();


while ($end_ofchunk_biggerthan_end_ofvid == 0 && $countchunks < $maxchunks && $problem_withreencoded == 0)
{
    $countchunks++;
    $vidstart = ($countchunks - 1) * $chunks_len;
    $vidend = $countchunks * $chunks_len;
    $vidend4chunkpath = $vidend;
    $this_chunklen = $chunks_len;
    if ($vidend >= $filetimeinminutes)
    {
        $end_ofchunk_biggerthan_end_ofvid = 1;
        $vidend4chunkpath = $filetimeinminutes;
        $this_chunklen = $filetimeinminutes - $vidstart;
    }
    $vidlen = $vidend - $vidstart;



    //xvid
    //$chunk_name=sprintf("%010d",$vidstart).'_'.sprintf("%010d",$vidend4chunkpath).'.xvid';
    //h.264
    $chunk_basename = sprintf("%010d", $vidstart) . '_' . sprintf("%010d", $vidend4chunkpath);
    $chunk_name = $chunk_basename . '.mp4';
    $temp_chunk_name = $chunk_basename . '_temp.mp4';

    $remember_chunks_array[$countchunks] = $chunk_name;
    $chunk_path = $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/' . $chunk_name;
    $temp_chunk_path = $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/' . $temp_chunk_name;
    $wanted_size = ($this_chunklen * $M_per_minute) * 1000;

    $hoursforstarttime = floor($vidstart / 60);
    $minutesforstarttime = $vidstart % 60;
    if ($minutesforstarttime < 10)
    { $minutesforstarttime = '0' . $minutesforstarttime; }

    $hoursforendtime = floor($vidlen / 60);
    $minutesforendtime = $vidlen % 60;
    if ($minutesforendtime < 10)
    { $minutesforendtime = '0' . $minutesforendtime; }



//    //create orichunk from original
//    $orichunkname=$videos_vault_dir.'/'.$firstchar.'/'.$secondchar.'/'.$file_info['file_md5'].'/chunk_'.$countchunks.'.'.$current_extensio;
//    //$chunkit='/var/www/barbavid/uploadserver1/meancoderzzz '.escapeshellarg($filetotreat).' -quiet -ss '.$hoursforstarttime.':'.$minutesforstarttime.':00 -endpos '.$hoursforendtime.':'.$minutesforendtime.':00 -oac copy -ovc copy -o '.escapeshellarg($orichunkname);
//    //$chunkit='/var/www/barbavid/uploadserver1/ffmpest -v 0 -i '.escapeshellarg($filetotreat).' -ss '.$hoursforstarttime.':'.$minutesforstarttime.':00 -t '.$hoursforendtime.':'.$minutesforendtime.':00 -f '.escapeshellarg($file_info['thinger']).' -y '.escapeshellarg($orichunkname);
//    //$chunkit='/var/www/barbavid/uploadserver1/ffmpest -v 0 -i '.escapeshellarg($filetotreat).' -ss '.$hoursforstarttime.':'.$minutesforstarttime.':00 -t '.$hoursforendtime.':'.$minutesforendtime.':00 -y '.escapeshellarg($orichunkname);
//    $chunkit='/var/www/barbavid/uploadserver1/ffmpest -v 0 -i '.escapeshellarg($reencoded_file_name).' -vcodec copy -acodec copy -ss '.$hoursforstarttime.':'.$minutesforstarttime.':00 -t '.$hoursforendtime.':'.$minutesforendtime.':00 -f '.escapeshellarg($file_info['thinger']).' -y '.escapeshellarg($orichunkname);
//    informzz(htmlspecialchars('-----'.$chunkit).'<br />'); flush();
//    $op_starttime=microtime(true);
//    unset($outputz);
//    exec($cdtoworkingdir.'; nice -n 19 '.$chunkit.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}
//    $op_endtime=microtime(true);
//    $op_time=$op_endtime-$op_starttime;
//    informzz('+++operation took '.$op_time.' seconds.<br />');
//    $pct_value_of_completion=(100/$howmany_chunks)*0.05;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
//    //verify if orichunk was created
//    if(!file_exists($orichunkname))
//    {
//        informzz($orichunkname.' does not exist, marking as missing chunk and exiting.<br />');
//        $missing_chunks=1;
//        break;
//    }
    //create orichunk from reencoded
    if ($countchunks == 1 && $howmany_chunks == 1)
    {
        $chunkit = 'mv ' . escapeshellarg($reencoded_file_name) . ' ' . escapeshellarg($temp_chunk_path);
        informzz(htmlspecialchars('-----' . $chunkit) . '<br />'); flush();
        $op_starttime = microtime(true);
        unset($outputz);
        exec($cdtoworkingdir . '; nice -n 19 ' . $chunkit . ' 2>&1', $outputz);
        foreach ($outputz as $key => $value)
        { informzz($value . '<br />'); }
        $op_endtime = microtime(true);
        $op_time = $op_endtime - $op_starttime;
        informzz('+++operation took ' . $op_time . ' seconds.<br />');
    } else
    {
        //$chunkit = $ffmpeg_path . ' -v 0 -ss ' . $hoursforstarttime . ':' . $minutesforstarttime . ':00 -t ' . $hoursforendtime . ':' . $minutesforendtime . ':15 -i ' . escapeshellarg($reencoded_file_name) . ' -g 1 -sameq -vcodec copy -acodec copy -y ' . escapeshellarg($temp_chunk_path);
		//no more -sameq
		//$chunkit = $ffmpeg_path . ' -v 0 -ss ' . $hoursforstarttime . ':' . $minutesforstarttime . ':00 -t ' . $hoursforendtime . ':' . $minutesforendtime . ':15 -i ' . escapeshellarg($reencoded_file_name) . ' -g 1 -vcodec copy -acodec copy -y ' . escapeshellarg($temp_chunk_path);
        //this command was working on the older server but now the chunks dont make sense on the new server!
        //I am not yet sure why but I am starting to think that it may be because the -ss and -t are before the -i and they should rather be after..
        //it was exactly that!!! -ss and -t need to be moved after -i in the newer versions otherwise weird stuff happens!! see: https://trac.ffmpeg.org/wiki/Seeking%20with%20FFmpeg
        //$chunkit = $ffmpeg_path . ' -v 0 -i ' . escapeshellarg($reencoded_file_name) . ' -ss ' . $hoursforstarttime . ':' . $minutesforstarttime . ':00 -t ' . $hoursforendtime . ':' . $minutesforendtime . ':15 -g 1 -vcodec copy -acodec copy -y ' . escapeshellarg($temp_chunk_path);
        //now this is working but Im facing audio out of sync issues on certain players only, this link talks about it: http://superuser.com/questions/499380/accurate-cutting-of-video-audio-with-ffmpeg
        //http://ubuntuforums.org/showthread.php?t=1824250 talks about it too
        //will test with -ss before -i but -t after...
        $chunkit = $ffmpeg_path . ' -v 0 -ss ' . $hoursforstarttime . ':' . $minutesforstarttime . ':00 -i ' . escapeshellarg($reencoded_file_name) . ' -t ' . $hoursforendtime . ':' . $minutesforendtime . ':15 -g 1 -vcodec copy -acodec copy -y ' . escapeshellarg($temp_chunk_path);
        informzz(htmlspecialchars('-----' . $chunkit) . '<br />'); flush();
        $op_starttime = microtime(true);
        unset($outputz);
        exec($cdtoworkingdir . '; nice -n 19 ' . $chunkit . ' 2>&1', $outputz);
        foreach ($outputz as $key => $value)
        { informzz($value . '<br />'); }
        $op_endtime = microtime(true);
        $op_time = $op_endtime - $op_starttime;
        informzz('+++operation took ' . $op_time . ' seconds.<br />');
    }


    //verify if orichunk from reencoded was created
    if (!file_exists($temp_chunk_path))
    {
        informzz($temp_chunk_path . ' does not exist, marking as missing chunk and exiting.<br />');
        $missing_chunks = 1;
        break;
    }




//    //create fullsize screenshot of chunk
//    if($vidlen==1)
//    {$thumbpos='10';}
//    else
//    {$thumbpos=($vidlen*60)/2;}
//    $temp_thumb_name=$videos_vault_dir.'/'.$firstchar.'/'.$secondchar.'/'.$file_info['file_md5'].'/'.$chunk_basename.'_temp.jpg';
//    $thumb='/var/www/barbavid/uploadserver1/ffmpest -v 0 -ss '.$thumbpos.' -i '.escapeshellarg($orichunkname).' -f image2 -vframes 1 -y '.escapeshellarg($temp_thumb_name);
//    informzz(htmlspecialchars('-----'.$thumb).'<br />'); flush();
//    $op_starttime=microtime(true);
//    unset($outputz);
//    exec($cdtoworkingdir.'; nice -n 19 '.$thumb.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}
//    $op_endtime=microtime(true);
//    $op_time=$op_endtime-$op_starttime;
//    informzz('+++operation took '.$op_time.' seconds.<br />');
    //create fullsize screenshot from original!
    if ($vidlen == 1)
    { $thumbpos = ($vidstart * 60) + 10; } else
    { $thumbpos = ($vidstart * 60) + ($vidlen * 60) / 2; }
    $temp_thumb_name = $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/' . $chunk_basename . '_temp.jpg';
    $thumb = $ffmpeg_path . ' -v 0 -ss ' . $thumbpos . ' -i ' . escapeshellarg($filetotreat) . ' -f image2 -vframes 1 -y ' . escapeshellarg($temp_thumb_name);
    informzz(htmlspecialchars('-----' . $thumb) . '<br />'); flush();
    $op_starttime = microtime(true);
    unset($outputz);
    exec($cdtoworkingdir . '; nice -n 19 ' . $thumb . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }
    $op_endtime = microtime(true);
    $op_time = $op_endtime - $op_starttime;
    informzz('+++operation took ' . $op_time . ' seconds.<br />');




    //make pics from screenshot with GD cos fucking imagemagick is bugged
    $size = getimagesize($temp_thumb_name);
    //$thumbwidth = 120;
    //$thumbheight = 50;
    //$largewidth = 728;
    //$largeheight = 305;
    //this is not compatible with 1920*1080 it would be better if it was!!
    $thumbwidth = 128; //1920/15=128
    $thumbheight = 72; //1080/15=72
    $largewidth = 960; //1920/3=960
    $largeheight = 540; //1080/3=540

    //make thumb
    $width_pourun = $size[0] / $thumbwidth;
    $height_pourun = $size[1] / $thumbheight;
    if ($width_pourun > $height_pourun)
    {
        $optimal_width = $thumbwidth;
        $optimal_height = ($thumbwidth * $size[1]) / $size[0];
        $dst_x = 0;
        $dst_y = ($thumbheight - $optimal_height) / 2;
    } else
    {
        $optimal_width = ($thumbheight * $size[0]) / $size[1];
        $optimal_height = $thumbheight;
        $dst_x = ($thumbwidth - $optimal_width) / 2;
        $dst_y = 0;
    }
    $img = imagecreatetruecolor($thumbwidth, $thumbheight);
    $bg = @imagecreate($thumbwidth, $thumbheight);
    $background_color = imagecolorallocate($bg, 0, 0, 0);
    imagecopy($img, $bg, 0, 0, 0, 0, $thumbwidth, $thumbheight);
    $src_img = imagecreatefromjpeg($temp_thumb_name);
    //imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
    imagecopyresampled($img, $src_img, $dst_x, $dst_y, 0, 0, $optimal_width, $optimal_height, $size[0], $size[1]);
    $thumb_name = $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/' . $chunk_basename . '_thumb.png';
    //imagejpeg($img,$thumb_name);
    imagepng($img, $thumb_name);
    imagedestroy($img);


    //make large
    $width_pourun = $size[0] / $largewidth;
    $height_pourun = $size[1] / $largeheight;
    if ($width_pourun > $height_pourun)
    {
        $optimal_width = $largewidth;
        $optimal_height = ($largewidth * $size[1]) / $size[0];
        $dst_x = 0;
        $dst_y = ($largeheight - $optimal_height) / 2;
    } else
    {
        $optimal_width = ($largeheight * $size[0]) / $size[1];
        $optimal_height = $largeheight;
        $dst_x = ($largewidth - $optimal_width) / 2;
        $dst_y = 0;
    }
    $img = imagecreatetruecolor($largewidth, $largeheight);
    $bg = @imagecreate($largewidth, $largeheight);
    $background_color = imagecolorallocate($bg, 0, 0, 0);
    imagecopy($img, $bg, 0, 0, 0, 0, $largewidth, $largeheight);
    $src_img = imagecreatefromjpeg($temp_thumb_name);
    imagecopyresampled($img, $src_img, $dst_x, $dst_y, 0, 0, $optimal_width, $optimal_height, $size[0], $size[1]);
    $thumb_name = $videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/' . $chunk_basename . '_large.png';
    //imagejpeg($img,$thumb_name);
    imagepng($img, $thumb_name);
    imagedestroy($img);


    //drop temp thumb
    $remove_tempthumb = 'rm ' . escapeshellarg($temp_thumb_name);
    informzz(htmlspecialchars($remove_tempthumb) . '<br />'); flush();
    unset($outputz);
    exec($cdtoworkingdir . '; nice -n 19 ' . $remove_tempthumb . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }




//    $pct_value_of_completion=(100/$howmany_chunks)*0.01;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
    //==========XVID ENCODING
//    //encode first pass of orichunk
//    $passlogfile=$upload_dir.'/pass_'.md5($filetotreat).'_'.$countchunks.'.log';
//    $pass1='/var/www/barbavid/uploadserver1/meancoderzzz '.escapeshellarg($orichunkname).' -nosound -ovc xvid -xvidencopts pass=1:autoaspect -passlogfile '.escapeshellarg($passlogfile).' -o /dev/null';
//    echo htmlspecialchars($pass1).'<br />'; flush();
//    exec('nice -n 19 '.$pass1.' > /dev/null');
//    $pct_value_of_completion=(100/$howmany_chunks)*0.25;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
//    //encode second pass of orichunk
//    $pass2='/var/www/barbavid/uploadserver1/meancoderzzz '.escapeshellarg($orichunkname).' -ofps 23.976 -oac mp3lame -lameopts preset='.$audio_br.' -passlogfile '.escapeshellarg($passlogfile).'  -ovc xvid -xvidencopts pass=2:autoaspect:bitrate=-'.$wanted_size.' -o '.escapeshellarg($chunk_path);
//    echo htmlspecialchars($pass2).'<br />'; flush();
//    exec('nice -n 19 '.$pass2.' > /dev/null');
//    $pct_value_of_completion=(100/$howmany_chunks)*0.7;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
    //==========XVID ENCODING
    //=========H.264 ENCODING
//    //encode first pass of orichunk
//    $pass1='/var/www/barbavid/uploadserver1/ffmpest -v 0 -i '.escapeshellarg($orichunkname).' -an -pass 1 -vcodec libx264 -vpre '.$pass1_preset.' -b '.$video_b.' -f rawvideo -y /dev/null';
//    informzz(htmlspecialchars('-----'.$pass1).'<br />'); flush();
//    $op_starttime=microtime(true);
//    unset($outputz);
//    exec($cdtoworkingdir.'; nice -n 19 '.$pass1.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}
//    $op_endtime=microtime(true);
//    $op_time=$op_endtime-$op_starttime;
//    informzz('+++operation took '.$op_time.' seconds.<br />');
//    $pct_value_of_completion=(100/$howmany_chunks)*0.25;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
//    //encode second pass of orichunk
//    $pass2='/var/www/barbavid/uploadserver1/ffmpest -v 0 -i '.escapeshellarg($orichunkname).' -acodec libfaac -ab '.$audio_b.' -pass 2 -vcodec libx264 -vpre '.$pass2_preset.' -b '.$video_b.' -y '.escapeshellarg($temp_chunk_path);
//    informzz(htmlspecialchars('-----'.$pass2).'<br />'); flush();
//    $op_starttime=microtime(true);
//    unset($outputz);
//    exec($cdtoworkingdir.'; nice -n 19 '.$pass2.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}
//    $op_endtime=microtime(true);
//    $op_time=$op_endtime-$op_starttime;
//    informzz('+++operation took '.$op_time.' seconds.<br />');
//    $pct_value_of_completion=(100/$howmany_chunks)*0.68;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
    //move moov atom and drop tempchunk
    //$moveatom = '/usr/local/bin/qt-faststart ' . escapeshellarg($temp_chunk_path) . ' ' . escapeshellarg($chunk_path);
    $moveatom = $qt_faststart_path.' ' . escapeshellarg($temp_chunk_path) . ' ' . escapeshellarg($chunk_path);
    informzz(htmlspecialchars('-----' . $moveatom) . '<br />'); flush();
    $op_starttime = microtime(true);
    unset($outputz);
    exec($cdtoworkingdir . '; nice -n 19 ' . $moveatom . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }
    $op_endtime = microtime(true);
    $op_time = $op_endtime - $op_starttime;
    informzz('+++operation took ' . $op_time . ' seconds.<br />');
    $droptempchunk = 'rm ' . escapeshellarg($temp_chunk_path);
    informzz(htmlspecialchars($droptempchunk) . '<br />'); flush();
    unset($outputz);
    exec($cdtoworkingdir . '; nice -n 19 ' . $droptempchunk . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }
//    $pct_value_of_completion=(100/$howmany_chunks)*0.01;
//    $completion_prct=$completion_prct+$pct_value_of_completion;
//    file_put_contents($working_dir.'/encodeinprog_'.$instance_number,$file_info['file_md5'].' '.$completion_prct);
    //=========H.264 ENCODING
    //remove orichunk
//    $remove_orichunk='rm '.escapeshellarg($orichunkname);
//    informzz(htmlspecialchars($remove_orichunk).'<br />'); flush();
//    unset($outputz);
//    exec($cdtoworkingdir.'; nice -n 19 '.$remove_orichunk.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}
    //remove passlogfile
    //==========XVID
//    $remove_passlogfile='rm '.escapeshellarg($passlogfile);
    //==========H.264
//    $remove_passlogfile='rm '.$videos_vault_dir.'/'.$firstchar.'/'.$secondchar.'/'.$file_info['file_md5'].'/*.log*';
//    informzz(htmlspecialchars($remove_passlogfile).'<br />'); flush();
//    unset($outputz);
//    exec($cdtoworkingdir.'; nice -n 19 '.$remove_passlogfile.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}
    //verify if vidx chunk was created
    if (file_exists($chunk_path))
    { chmod($chunk_path, 0777); } else
    {
        informzz($chunk_path . ' does not exist, marking as missing chunk and exiting.<br />');
        $missing_chunks = 1;
        break;
    }
}

//$remove_passlogfile='rm '.escapeshellarg($passlogfile);
//exec($remove_passlogfile);


$sethost = '';
if ($missing_chunks == 0)
{
    $countturns = 0;
    $maxturns = 15;
    while ($sethost != 'ok' && $countturns < $maxturns)
    {
        $countturns++;
        $sethost_url = $path_to_main_server . 'set_server_on_video.php?server=' . $videohostname . '&video=' . urlencode($file_info['file_md5']) . '&chunks=' . urlencode(serialize($remember_chunks_array)) . '&reso=' . urlencode($newreso) . $set_server_time_setter;
        $sethost = get_content_of_url($sethost_url);
        informzz($sethost_url . ': ' . htmlspecialchars($sethost));
    }
}

if ($sethost == 'ok')
{
    //only if cross server videos library update was successful, remove original
    $remove_original = 'rm ' . escapeshellarg($filetotreat);
    unset($outputz);
    exec($remove_original . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }

    //remove reencoded copy
    $remove_reencoded = 'rm ' . escapeshellarg($reencoded_file_name);
    unset($outputz);
    exec($remove_reencoded . ' 2>&1', $outputz);
    foreach ($outputz as $key => $value)
    { informzz($value . '<br />'); }

    //write down info about encoding in logfile
    file_put_contents($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/encodings.log', $infoforlogfile, FILE_APPEND);
} else
{
    //delete everything in folder (encodings.log is not in there yet, it will be put after)
//    $remove_reencoded='rm '.$videos_vault_dir.'/'.$firstchar.'/'.$secondchar.'/'.$file_info['file_md5'].'/*';
//    unset($outputz);
//    exec($remove_reencoded.' 2>&1',$outputz);
//    foreach($outputz as $key => $value)
//    {informzz($value.'<br />');}

    informzz('will now attemps to erase all files in folder except .txt<br />');
    $stuffinfolder = glob($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/*');
    foreach ($stuffinfolder as $key => $value)
    {
        informzz($value . '<br />');
        $exploded_by_dots = explode('.', $value);
        $totalchunks = count($exploded_by_dots);
        $lastchunk = $exploded_by_dots[$totalchunks - 1];
        informzz('ext: ' . $lastchunk . '<br />');
        if ($lastchunk != 'txt')
        {
            informzz('will unlink ' . $value . '<br />');
            unlink($value);
        }
    }

    //put queue file back (in encoding_queue_errors)
    file_put_contents(str_replace('/encoding_queue/', '/encoding_queue_errors/', $first_element), $filecontents);

    //move uploaded file in encoding_queue_errors too
    rename($filetotreat, str_replace($upload_dir, $working_dir . '/encoding_queue_errors/', $filetotreat));






    //set server to failed on video
    $countturns = 0;
    $maxturns = 15;
    while ($sethost != 'ok' && $countturns < $maxturns)
    {
        $countturns++;
        $sethost_url = $path_to_main_server . 'set_server_on_video.php?server=failedencoding_' . $servername . '&video=' . urlencode($file_info['file_md5']);
        $sethost = get_content_of_url($sethost_url);
        informzz(htmlspecialchars($sethost_url . ': ' . $sethost));
    }

    //warn admin about failure
    $headers = "From: failedencoding@" . $servername . ".barbavid.com";
    mail($adminemail, 'failed encoding ' . $file_info['file_md5'], 'Encoding of video ' . $file_info['file_md5'] . ' failed at ' . $servername, $headers);


    //write down info about encoding in logfile
    file_put_contents($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5'] . '/encodings.log', $infoforlogfile, FILE_APPEND);



    if (!$file_info || $file_info['file_md5'] == '' || strlen($file_info['file_md5']) < 10)
    { die('error, invalid $file_info or $file_info[\'file_md5\'], cannot continue...'); }

    //move md5 folder from video server too (and cleanup if it leaves unused folders)
    $move_md5folder = 'mv ' . escapeshellarg($videos_vault_dir . '/' . $firstchar . '/' . $secondchar . '/' . $file_info['file_md5']) . ' ' . escapeshellarg($working_dir . '/encoding_queue_errors/');
    echo $move_md5folder.'
';
    exec($move_md5folder);

    //clean containing folders if necessary:
    $stuffleftinfoldarr = glob($videos_vault_dir . '/' . $firstchar . '/' . $secondchar);
    $howmanyisthat = count($stuffleftinfoldarr);
    if ($howmanyisthat == 0)
    { rmdir($videos_vault_dir . '/' . $firstchar . '/' . $secondchar); }
    $stuffleftinfoldarr = glob($videos_vault_dir . '/' . $firstchar);
    $howmanyisthat = count($stuffleftinfoldarr);
    if ($howmanyisthat == 0)
    { rmdir($videos_vault_dir . '/' . $firstchar); }
}




//remove queue element from encoding_queue_inprogress
$remove_queue_element = 'rm ' . escapeshellarg($pathofqueuefileinprogfolder);
unset($outputz);
exec($remove_queue_element . ' 2>&1', $outputz);
foreach ($outputz as $key => $value)
{ informzz($value . '<br />'); }





unlink($inprogress_filename);
?>
