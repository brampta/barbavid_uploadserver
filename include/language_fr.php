<?php

function make_text1($maxfilesize,$yourfilesize)
{return 'Le fichier est trop gros. Grosseur maximale accpetée: '.$maxfilesize.'MB, Grosseur de votre fichier: '.$yourfilesize.'MB.';}

$text[2]='Erreur lors de la mise en ligne du vidéo: ';
$text[3]='La mise en ligne du vidéo a réussi mais ffmpeg n\'a pas été capable de comprendre le fichier vidéo que vous avez envoyé. ffmpeg est capable de comprendre les formats suivants:';
$text[4]='La mise en ligne du vidéo a réussi mais selon ffmpeg la longeur de ce vidéo est de moins de 3 secondes. Cela veut dire que soit le fichier que vous avez envoyé n\'était pas un vidéo ou soit c\'était un vidéo très très court. De toute façon, seulement les vidéos de 3 secondes ou plus sont acceptés.';
$text[5]='Votre vidéo a été mis en ligne avec succès et peut être regardé à l\'address suivante: ';
$text[6]='Notez que malgré le fait que votre vidéo ait été envoyé avec succès, il ne pourra pas être regardé tant que le proçessus de l\'encodage ne sera pas terminé.';
$text[7]='Le vidéo ne peut pas être reçu parceque le serveur vidéo est plein, réessayez plus tard.';


$text[40]='octets mis en ligne';
$text[41]='vitesse du transfert';
$text[42]='temps écoulé';
$text[43]='estimation du temps restant';
$text[44]='moyenne';
$text[45]='Ko/s';
$text[46]='Le vidéo ne peut pas être reçu parceque le serveur vidéo est plein, réessayez plus tard.';
