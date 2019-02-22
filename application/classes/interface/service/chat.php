<?php defined('SYSPATH') or die('No direct script access.');

interface Interface_Service_Chat{
    function getInstanceBySocketId($socketId);
    function isRegisteredSocketId($socketId);
}
