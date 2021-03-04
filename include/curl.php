<?php


function get_content_of_url($url){
    echo 'i curl url '.$url.'<br />';
    $ohyeah = curl_init();
    curl_setopt($ohyeah, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ohyeah, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ohyeah, CURLOPT_TIMEOUT, 30);
    curl_setopt($ohyeah, CURLOPT_URL, $url);
    curl_setopt($ohyeah, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ohyeah, CURLOPT_USERAGENT, "Curl Barbavid");
    
    $dataz = curl_exec($ohyeah);
    $error = curl_error($ohyeah);
    echo $error;
    echo 'rezu: '.htmlspecialchars($dataz).'<br />';
    curl_close($ohyeah);
    return $dataz;
}

function curl_post($url,$data){

    echo 'i curl url '.$url.' with data '.$data.'<br />';
    $ohyeah = curl_init();
    curl_setopt($ohyeah, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ohyeah, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ohyeah, CURLOPT_TIMEOUT, 30);
    curl_setopt($ohyeah, CURLOPT_URL, $url);
    curl_setopt($ohyeah, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ohyeah, CURLOPT_USERAGENT, "Curl Barbavid");

    curl_setopt($ohyeah, CURLOPT_POST, TRUE);
    curl_setopt($ohyeah, CURLOPT_POSTFIELDS, $data);

    $dataz = curl_exec($ohyeah);
    echo 'rezu: '.htmlspecialchars($dataz).'<br />';
    curl_close($ohyeah);
    return $dataz;
}

?>