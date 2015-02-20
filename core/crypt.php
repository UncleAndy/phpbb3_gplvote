<?php
/**
*
* @package phpBB Extension - GPLVote SignDoc
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

namespace gplvote\signdoc\core;

class Crypt
{
      public function is_valid_sign($data, $b64_sign, $b64_pub_key) {
        $sign = base64_decode($b64_sign);
      
        error_log("DBG: check sign for data: ".$data."\n", 3, "/tmp/phpbb_gplvote.log");
      
        return openssl_verify($data, $sign, $this->pem_public_key($b64_pub_key), "SHA256");
      }
      
      public function get_public_key_id($b64_pub_key) {
        $public_key = base64_decode($b64_pub_key);
        
        $raw_pub_key_id = hash('SHA256', $public_key, true);
        $public_key_id = base64_encode($raw_pub_key_id);
        $public_key_id = preg_replace('/\=*$/', '', $public_key_id);
        return($public_key_id);
      }
      
      private function pem_public_key($b64_pub_key) {
        return "-----BEGIN PUBLIC KEY-----\n".$this->split_base64($b64_pub_key)."\n-----END PUBLIC KEY-----";
      }
      
      private function split_base64($text) {
        $res = '';
        while ($text != '') {
          if (strlen($text) > 72) {
            $res .= substr($text, 0, 72)."\n";
            $text = substr($text, 72, strlen($text) - 72);
          } else {
            $res .= $text;
            $text = '';
          };
        };

        return $res;
      }
}
