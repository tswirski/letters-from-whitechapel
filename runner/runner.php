<?php
if ( $_GET['command'] )
{
    echo exec('lfw_runner '.$_GET['command']);
}
?>
