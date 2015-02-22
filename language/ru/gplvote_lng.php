<?php
/**
*
* @package phpBB Extension - GPLVote SignDoc
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

if (!defined('IN_PHPBB'))
{
    exit;
}
if(empty($lang) || !is_array($lang))
{
        $lang = array();
}

$lang = array_merge($lang, array(
        'LOGIN_ERROR_EXTERNAL_AUTH_GPLVOTE' => 'Для входа необходимо сосканировать код через <a href="https://play.google.com/store/apps/details?id=org.gplvote.signdoc" target="_blank">наше приложение</a>.',
));
