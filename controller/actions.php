<?php
/**
*
* @package phpBB Extension - My test
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

namespace gplvote\signdoc\controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class actions
{
  protected $config;
  protected $db;
  protected $auth;
  protected $template;
  protected $user;
  protected $helper;
  protected $phpbb_root_path;
  protected $php_ext;

  public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\pagination $pagination, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, $phpbb_root_path, $php_ext, $table_prefix)
  {
    $this->config = $config;
    $this->request = $request;
    $this->pagination = $pagination;
    $this->db = $db;
    $this->auth = $auth;
    $this->template = $template;
    $this->user = $user;
    $this->helper = $helper;
    $this->phpbb_root_path = $phpbb_root_path;
    $this->php_ext = $php_ext;
    $this->table_prefix = $table_prefix;
  }

  public function getdoc() {
      return new Response('TEST getdoc', 200, array('Content-Type' => 'application/json'));
  }

  public function sign() {
      return new Response('TEST sign', 200, array('Content-Type' => 'application/json'));
  }

  public function register() {
      return new Response('TEST register', 200, array('Content-Type' => 'application/json'));
  }
  
  public function doc_qrcode($doc_id) {
      $board_url = str_replace(['http://', 'https://'], '', generate_board_url());
      $signdoc_url = 'signdoc://'.$board_url.'/sd/getdoc?id='.$doc_id;
      
      return new RedirectResponse('http://chart.apis.google.com/chart?cht=qr&chs=150x150&chl='.$signdoc_url, 301);
  }
}
