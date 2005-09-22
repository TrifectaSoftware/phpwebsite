<?php

if (!defined('PHPWS_SOURCE_DIR')) {
    include '../../config/core/404.html';
    exit();
}

PHPWS_Core::initModClass('access', 'Access.php');

if (Current_User::authorized('access')) {
    Access::main();
} else {
    Current_User::disallow();
    exit();
}


?>