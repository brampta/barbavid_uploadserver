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

if (isset($_GET['jsonp'])) {
    if ($nospace == "nospace") {
        echo 'stopUpload("' . $text[46] . '");
            interrupt_upload();
            ';
    } else {
		if (function_exists("uploadprogress_get_info")) {
           //echo 'function exists';
       } else {
           //echo 'function NOT exist';
       }
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
            echo 'show_upload_progress(' . json_encode($shorezu) . ');
            ';
        }
    }
} else {
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
            window.top.window.stopUpload("' . $text[46] . '");
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