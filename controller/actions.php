<?php

/**
*
* @package phpBB Extension - GPLVote SignDoc
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

namespace gplvote\signdoc\controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use gplvote\signdoc\core\crypt;

/*
*  Идентификатор документа: "<тип>:<random>"
*  Где <тип>:
*    "in" - логин
*    "p" - подпись поста
*    "c" - подпись коммента
*    "v" - подпись голоса
*/

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
    
    define(__NAMESPACE__ . '\LOGIN_SIGNS', $this->table_prefix . 'login_signs');
  }

  public function getdoc($doc_id) {
      $doc = array();
      $doc['type'] = 'EMPTY';

      $table = null;
      if (preg_match('/^in\:/', $doc_id)) {
        $sql = 'SELECT * FROM '.LOGIN_SIGNS.' WHERE id = \''.$this->db->sql_escape($doc_id).'\'';
        $c = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($c);
        $this->db->sql_freeresult($c);
        
        if ($c != null) {
          $doc['type'] = 'SIGN_REQUEST';
          $doc['site'] = generate_board_url();
          $doc['doc_id'] = $doc_id;
          $doc['template'] = "LIST\nКод на экране:";
          $doc['dec_data'] = '["'.$row['code'].'"]';
          $doc['sign_url'] = generate_board_url().'/sd/sign';
        };
      };
  
      return new Response(json_encode($doc, JSON_UNESCAPED_UNICODE), 200, array('Content-Type' => 'application/json; charset=utf-8'));
  }

  public function sign() {
#      error_log("DBG: sign\n", 3, "/tmp/phpbb_gplvote.log");
      // Извлекаем тело подписи из POST контента
      $json_doc = file_get_contents("php://input");
      $doc = json_decode($json_doc);
  
      // Проверяем подпись
      
      $sign_data = '';
      if (preg_match('/^in\:/', $doc->{'doc_id'})) {
        // Подпись для логина
        // Извлекаем документ из БД
        $sql = 'SELECT * FROM '.LOGIN_SIGNS.' WHERE id = \''.$this->db->sql_escape($doc->{'doc_id'}).'\'';
        error_log("DBG: sql for get doc: ".$sql."\n", 3, "/tmp/phpbb_gplvote.log");
        $c = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($c);
        $this->db->sql_freeresult($c);
        
        $sign_data = $doc->{'site'}.":".$doc->{'doc_id'}.':["'.$row['code'].'"]:'."LIST\nКод на экране:";
        
        $crypt = new Crypt();
    
        if ($crypt->is_valid_sign($sign_data, $doc->{'sign'}, $doc->{'public_key'})) {
          $sql = 'UPDATE '.LOGIN_SIGNS.' 
                    SET 
                      sign = \''.$this->db->sql_escape($doc->{'sign'}).'\', 
                      public_key = \''.$this->db->sql_escape($doc->{'public_key'}).'\',
                      public_key_id = \''.$crypt->get_public_key_id($doc->{'public_key'}).'\',
                      signed_at = '.time().'
                  WHERE id = \''.$this->db->sql_escape($doc->{'doc_id'}).'\'';
          $c = $this->db->sql_query($sql);
          $this->db->sql_freeresult($c);
          return new Response('{"status":0}', 200, array('Content-Type' => 'application/json; charset=utf-8'));
        } else {
          return new Response('{"status":1}', 200, array('Content-Type' => 'application/json; charset=utf-8'));
        };
      };
  }
  
  public function doc_qrcode($doc_id) {
      $board_url = str_replace(['http://', 'https://'], '', generate_board_url());
      $signdoc_url = 'signdoc://'.$board_url.'/sd/getdoc/'.$doc_id;
      
      return new RedirectResponse('http://chart.apis.google.com/chart?cht=qr&chs=250x250&chl='.$signdoc_url, 301);
  }
}
