<?php


include('allowed_ips.php');
//echo $_SERVER['REMOTE_ADDR'].'<br />';
if(array_search($_SERVER['REMOTE_ADDR'],$allowed_ips)===false)
{die('unauthorized');}

//echo '$_GET[\'video\']: '.$_GET['video'].'<br />';


$inprocess_files=glob('encodeinprog_*');
$in_progress=0;
foreach($inprocess_files as $key => $value)
{
    $filecontents=file_get_contents($value);
    $exploded_contents=explode(' ',$filecontents);
    if($exploded_contents[0]==$_GET['video'])
    {
        echo 'inprogress '.$exploded_contents[1];
        $in_progress=1;
        
        if($exploded_contents[1]=='pass1' || $exploded_contents[1]=='pass2')
        {
            echo ' '.$exploded_contents[2];
            $current_encoding_time=0;
            $file = fopen($exploded_contents[3], "r");
            while(!feof($file))
            {
                $thisline=fgets($file);
                //echo '$thisline: '.$thisline.'<br />';
                $istimeline=strripos($thisline,' time=');
                if($istimeline!==false)
                {
                    echo '';
                    $timestart=$istimeline+6;
                    $timeend=stripos($thisline,' ',$timestart);
                    $timelen=$timeend-$timestart;
                    $current_encoding_time=substr($thisline,$timestart,$timelen);
                    
                    if(stripos($current_encoding_time,':'))
                    {
                        $xploded_current_encoding_time=explode(':',$current_encoding_time);
                        $current_encoding_time=($xploded_current_encoding_time[0]*3600)+($xploded_current_encoding_time[1]*60)+$xploded_current_encoding_time[2];
                    }
                }
            }
            fclose($file);
            echo ' '.$current_encoding_time.' ';
        }


    }
}

if($in_progress==0)
{
    $stuff_in_encoding_queue=glob('encoding_queue/*');


    $count=0;
    $pos=0;
    foreach($stuff_in_encoding_queue as $key => $value)
    {
        //echo '$value: '.$value.'<br />';
        $count++;
        $lastslash=strripos($value,'/');
        $stuffafter=substr($value,$lastslash+1);
        $lastdot=strripos($stuffafter,'.');
        $stuffbeforelastdot=substr($stuffafter,0,$lastdot);
        $explode_at_undersc=explode('_',$stuffbeforelastdot);
        if($explode_at_undersc[1]==$_GET['video'])
        {
            $pos=$count;
            break;
        }
    }

    if($pos==0)
    {echo 'not found';}
    else
    {echo $pos.'/'.count($stuff_in_encoding_queue);}
}


if($pos==0 && $in_progress==0)
{
    $stuff_in_error_encoding_queue=glob('encoding_queue_errors/*');
    foreach($stuff_in_error_encoding_queue as $key => $value)
    {
        //echo '$value: '.$value.'<br />';
        $lastslash=strripos($value,'/');
        $stuffafter=substr($value,$lastslash+1);
        $lastdot=strripos($stuffafter,'.');
        $stuffbeforelastdot=substr($stuffafter,0,$lastdot);
        $explode_at_undersc=explode('_',$stuffbeforelastdot);
        if($explode_at_undersc[1]==$_GET['video'])
        {
            echo '-error queue';
            break;
        }
    }
}

?>