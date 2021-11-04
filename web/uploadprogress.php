<?php
//show all errors:
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);


include(dirname(dirname(__FILE__)).'/settings.php');
ini_set('session.cookie_domain', '.'.$main_domain);
session_start();

//print_r($_SESSION);

//===========get language
if (isset($_GET['language'])) {
    $language = $_GET['language'];
} else if (isset($_COOKIE['language'])) {
    $language = $_COOKIE['language'];
} else {
    $language = 'en';
}
setcookie("language", $language, time() + (4 * 365 * 24 * 3600), '/', '.'.$main_domain);
if (!@include(dirname(dirname(__FILE__)).'/include/language_' . urlencode($language) . '.php')) {
    die('incorrect language');
}

//===========get language



function show_sec_as_hms($seconds) {
    $hours = floor($seconds / 3600);
    $leftseconds = $seconds % 3600;
    $minutes = floor($leftseconds / 60);
    if ($minutes < 10) {
        $minutes = '0' . $minutes;
    }
    $leftseconds = $leftseconds % 60;
    if ($leftseconds < 10) {
        $leftseconds = '0' . $leftseconds;
    }
    $rezu = $hours . ':' . $minutes . ':' . $leftseconds;
    return $rezu;
}

$nospace = '';
if (isset($_GET['space'])) {
    //$minimumspaceinGigs = 30;
    $freespacebytes = disk_free_space($videos_vault_dir);
    $freespaceK = $freespacebytes / 1024;
    $freespaceM = $freespaceK / 1024;
    $freespaceinG = $freespaceM / 1024;
    if ($freespaceinG < $minimumspaceinGigs) {
        $nospace = 'nospace';
    }
}

$remembersuccess = false;
$stopUpload = false;
if (isset($_GET['jsonp'])) {
    if ($nospace == "nospace") {
        echo 'stopUpload("' . $text[46] . '","from: ' . $servername . '.' . $main_domain . '/uploadprogress.php (1 nospace)");
            interrupt_upload();
            ';
    } else {
        if($_GET['mode']=='url'){
            //url upload

            if($_GET['id']=='new_file_upload'){
                $shorezu = '<p>'.$text[51].'</p>';
            }else{
                $upload_progress_info_file = $upload_dir . '/urlupload_progress_'.$_GET['id'].'.txt';
                $upload_progress_info = json_decode(file_get_contents($upload_progress_info_file));
                //var_dump($upload_progress_info);

                if($upload_progress_info->status=='downloading'){
                    if(!isset($upload_progress_info->downloading_info->download_size) || $upload_progress_info->downloading_info->download_size<=0){
                        $percent_downloaded = 0;
                    }else{
                        $percent_downloaded = ($upload_progress_info->downloading_info->downloaded*100)/$upload_progress_info->downloading_info->download_size;
                    }
                    $percent_downloaded = number_format((float)$percent_downloaded, 2, '.', '');
                    $shorezu = $text[52].' ('.$percent_downloaded.'%)';
                }else if($upload_progress_info->status=='downloaded'){
                    $shorezu = $text[53];
                }else if($upload_progress_info->status=='error'){//die(var_dump($upload_progress_info));
                    $shorezu = $upload_progress_info->error_info->message;
                    $stopUpload = true;
                }else if($upload_progress_info->status=='success'){
                    $shorezu = '<div>'.$text[5].'<a href="https://'.$main_domain.'/video/'.$upload_progress_info->upload_info->hash.'" target="_blank">https://'.$main_domain.'/video/'.$upload_progress_info->upload_info->hash.'</a></div>';
                    $remembersuccess = $upload_progress_info->upload_info->hash;
                    $stopUpload = true;
                }
            }

        }else{
            //normal upload from local file

            if (function_exists("uploadprogress_get_info")) {
               //echo 'function exists';

            $progress_data_array = uploadprogress_get_info($_GET['id']);
            //print_r($progress_data_array);

            if ($progress_data_array != false) {
                if ($progress_data_array['bytes_total'] == 0) {
                    $pct = 0;
                } else {
                    $pct = (100 * $progress_data_array['bytes_uploaded']) / $progress_data_array['bytes_total'];
                }
                $pct = round($pct * 100) / 100;
                $show_speed_last = round(($progress_data_array['speed_last'] / 1024) * 100) / 100;
                $show_speed_average = round(($progress_data_array['speed_average'] / 1024) * 100) / 100;
                $tt = $progress_data_array['time_last'] - $progress_data_array['time_start'];

                $shorezu = '<table class="uprogress">' .
                        '<tr><td class="upstatlabel">' . $text[40] . ':</td><td class="upstat">' . $progress_data_array['bytes_uploaded'] . '/' . $progress_data_array['bytes_total'] . ' (' . $pct . '%)</td></tr>' .
                        '<tr><td class="upstatlabel">' . $text[41] . ':</td><td class="upstat">' . $show_speed_last . $text[45] . ' (' . $text[44] . ': ' . $show_speed_average . $text[45] . ')</td></tr>' .
                        '<tr><td class="upstatlabel">' . $text[42] . ':</td><td class="upstat">' . show_sec_as_hms($tt) . '</td></tr>' .
                        '<tr><td class="upstatlabel">' . $text[43] . ':</td><td class="upstat">' . show_sec_as_hms($progress_data_array['est_sec']) . '</td></tr>' .
                        '</table>';

            }

            } else {
               $shorezu = 'uploadprogress_get_info function NOT exist (install PHP uploadprogress)';
           }
        }

        if($stopUpload){
            echo 'stopUpload(' . json_encode($shorezu) . ',"uploadprogress.php stopUpload = true");
';
        }else{
            echo 'show_upload_progress(' . json_encode($shorezu) . ');
';
        }

        if($remembersuccess){
            echo 'remembersuccess('.json_encode('rememeber_title').','.json_encode($remembersuccess).');
';
        }

    }
} else {

    //I think this second mode, html(?) is not used anymore..... to remove.......
    echo '<html>
    <head>
        <title>Barbavid Upload Progress</title>
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    </head>
    <body>
    test1_' . time() . '<br />
        <script language="javascript" type="text/javascript">
            document.domain = "barbavid.com";
            ';

    if ($nospace == "nospace") {
        echo 'window.top.window.interrupt_upload();
            window.top.window.stopUpload("' . $text[46] . '","from: ' . $servername . '.' . $main_domain . '/uploadprogress.php(2, nospace)");
            ';
    } else {
        $progress_data_array = uploadprogress_get_info($_GET['id']);
        //print_r($progress_data_array);
        //echo 'test2';
        if ($progress_data_array != false) {
            if ($progress_data_array['bytes_total'] == 0) {
                $pct = 0;
            } else {
                $pct = (100 * $progress_data_array['bytes_uploaded']) / $progress_data_array['bytes_total'];
            }
            $pct = round($pct * 100) / 100;
            $show_speed_last = round(($progress_data_array['speed_last'] / 1024) * 100) / 100;
            $show_speed_average = round(($progress_data_array['speed_average'] / 1024) * 100) / 100;
            $tt = $progress_data_array['time_last'] - $progress_data_array['time_start'];

            $shorezu = '<table class="uprogress">' .
                    '<tr><td>' . $text[40] . ':</td><td>' . $progress_data_array['bytes_uploaded'] . '/' . $progress_data_array['bytes_total'] . ' (' . $pct . '%)</td></tr>' .
                    '<tr><td>' . $text[41] . ':</td><td>' . $show_speed_last . $text[45] . ' (' . $text[44] . ': ' . $show_speed_average . $text[45] . ')</td></tr>' .
                    '<tr><td>' . $text[42] . ':</td><td>' . show_sec_as_hms($tt) . '</td></tr>' .
                    '<tr><td>' . $text[43] . ':</td><td>' . show_sec_as_hms($progress_data_array['est_sec']) . '</td></tr>' .
                    '</table>';
            echo 'window.top.window.show_upload_progress(' . json_encode($shorezu) . ');
            ';
        }
    }


    echo '</script>
    </body>
</html>';
}
?>