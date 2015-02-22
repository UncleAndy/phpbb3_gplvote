<?php

/**
*
* @package phpBB Extension - GPLVote SignDoc
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

namespace gplvote\signdoc\auth\provider;
use phpbb\request\request_interface;

class gplvote extends \phpbb\auth\provider\base
{
        protected $db;
        protected $config;
        protected $request;
        protected $user;
        protected $phpbb_root_path;
        protected $php_ext;
        protected $settings = array();
        public function __construct(
                \phpbb\db\driver\driver_interface $db,
                \phpbb\config\config $config,
                \phpbb\request\request $request,
                \phpbb\user $user,
                $phpbb_root_path,
                $php_ext,
                $table_prefix
        )
        {
                $this->db = $db;
                $this->config = $config;
                $this->request = $request;
                $this->user = $user;
                $this->phpbb_root_path = $phpbb_root_path;
                $this->php_ext = $php_ext;

                define(__NAMESPACE__ . '\LOGIN_SIGNS', $table_prefix . 'login_signs');
                define(__NAMESPACE__ . '\USERS_PUB_KEYS', $table_prefix . 'users_public_keys');
                
                $this->user->add_lang_ext('gplvote/signdoc', 'gplvote_lng');
        }
        
        /**
         * {@inheritdoc}
         * - called when login form is submitted
         */
        public function login($username = null, $password = null)
        {
            // В $username - одноразовый код, $password - пустой
            
            error_log("DBG: login with: ".$username." / ".$password."\n", 3, "/tmp/phpbb_gplvote.log");
            
            // Проверяем подписан-ли документ для логина по данному коду
            $sql = sprintf('SELECT * FROM %1$s WHERE code = \'%2$s\'', LOGIN_SIGNS, $this->db->sql_escape($password));
            $result = $this->db->sql_query($sql);
            $sign_row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            
            if ($sign_row && ($sign_row['public_key_id'] != null) && ($sign_row['public_key_id'] != '')) {
                  error_log("DBG: code found with public_key_id = ".$sign_row['public_key_id']."\n", 3, "/tmp/phpbb_gplvote.log");
            
                  // Определяем соответствующего юзера по public_key_id в поле пароля
                  $sql = sprintf('SELECT user_id, username, user_password, user_passchg, user_email, user_type FROM %1$s WHERE user_password = \'%2$s\'', USERS_TABLE, $sign_row['public_key_id']);
                  $result = $this->db->sql_query($sql);
                  $user_row = $this->db->sql_fetchrow($result);
                  $this->db->sql_freeresult($result);
                  
                  if ($user_row) {
                        error_log("DBG: user for this public_key exists\n", 3, "/tmp/phpbb_gplvote.log");
                        
                        // check for inactive users
                        if($user_row['user_type'] == USER_INACTIVE || $user_row['user_type'] == USER_IGNORE)
                        {
                                return array(
                                        'status'        => LOGIN_ERROR_ACTIVE,
                                        'error_msg'     => 'ACTIVE_ERROR',
                                        'user_row'      => $user_row,
                                );
                        }
                        // success
                        return array(
                                'status'                => LOGIN_SUCCESS,
                                'error_msg'             => false,
                                'user_row'              => $user_row,
                        );
                  } else {
                        error_log("DBG: user for this public_key not exists - autocreate\n", 3, "/tmp/phpbb_gplvote.log");
                        
                        // first login, create new user
                        // TODO: Сделать что-бы совместно с автосозданием юзера происходил логин
                        $user_row = $this->newUserRow($username, $sign_row['public_key_id']);
                        return array(
                                'status'                => LOGIN_SUCCESS_CREATE_PROFILE,
                                'error_msg'             => false,
                                'user_row'              => $user_row,
                        );                        
                  }
            };
                  
            // Fallback, not logged in
            return array(
                    'status'        => LOGIN_ERROR_EXTERNAL_AUTH,
                    'error_msg'     => 'LOGIN_ERROR_EXTERNAL_AUTH_GPLVOTE',
                    'user_row'      => array('user_id' => ANONYMOUS),
            );
        }
        
        /**
         * {@inheritdoc}
         - called when new session is created
         */
         /*
        public function autologin()
        {
                $shib_user = htmlspecialchars_decode($this->request->server($this->settings['user']));
                // check if Shibboleth user is empty or AUTH_TYPE is not Shibboleth, jump to fallback case (not logged in)
                if(
                        !empty($shib_user)
                        && $this->request->server('AUTH_TYPE') === 'Shibboleth'
                )
                {
                        set_var($shib_user, $shib_user, 'string', true);
                        $sql = sprintf('SELECT * FROM %1$s WHERE username = \'%2$s\'', USERS_TABLE, $this->db->sql_escape($shib_user));
                        $result = $this->db->sql_query($sql);
                        $row = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);
                        // user exists
                        if($row)
                        {
                                // check for inactive users
                                if($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
                                {
                                        return array();
                                }
                                // success
                                return $row;
                        }
                        // user does not exist atm, we'll fix that
                        if(!function_exists('user_add'))
                        {
                                include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
                        }
                        user_add($this->newUserRow($shib_user));
                        // get the newly created user row
                        // $sql already defined some lines before
                        $result = $this->db->sql_query($sql);
                        $row = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);
                        if($row)
                        {
                                return $row;
                        }
                }
                return array();
        }
        */
        
        /**
         * {@inheritdoc}
         * - called on every request when session is active
         */
         /*
        public function validate_session($user)
        {
                // Check if Shibboleth user is set, AUTH_TYPE is Shibboleth and the usernames are the same, then all is fine
                if(
                        $this->request->is_set($this->settings['user'], request_interface::SERVER)
                        && $this->request->server('AUTH_TYPE') === 'Shibboleth'
                        && $user['username'] === htmlspecialchars_decode($this->request->server($this->settings['user']))
                )
                {
                        return true;
                }
                // if the user is Shibboleth auth'd but first case did not fire, he isn't logged in to phpBB - invalidate his session so autologin() is called ;)
                if($this->request->server('AUTH_TYPE') === 'Shibboleth')
                {
                        return false;
                }
                // if the user type is ignore, then it's probably an anonymous user or a bot
                if($user['user_type'] == USER_IGNORE)
                {
                        return true;
                }
                // no case matched, shouldn't occur...
                return false;
        }
        */
        
        /**
         * {@inheritdoc}
         * - called when user logs out
         */
         /*
        public function logout($data, $new_session)
        {
                // the SP's login handler
                $shib_sp_url = sprintf('%s%s', $this->settings['handler_base'], $this->settings['logout_handler']);
                redirect($shib_sp_url, false, true);
        }
        */
        
        /**
         * {@inheritdoc}
         * - should return custom configuration options
         */
         /*
        public function acp()
        {
                // these are fields in the config for this auth provider
                return array(
                        'shibboleth_user_attribute',
                        'shibboleth_handler_base',
                        'shibboleth_login_handler',
                        'shibboleth_logout_handler',
                );
        }
        */
        
        /**
         * {@inheritdoc}
         * - should return configuration options template
         */
         /*
        public function get_acp_template($new_config)
        {
                return array(
                        'TEMPLATE_FILE' => '@gplvote/auth_provider_gplvote.html',
                        'VARS'  => array(
                          'test_var' => 'test'
                        ),
                );
        }
        */
        /**
        * {@inheritdoc}
        * - should return additional template data for login form
        */
        public function get_login_data()
        {
                $session_data = $this->restoreSessionData();
                $login_code = '';
                $document_id = '';
                if ($session_data == null) {
                        // Генерируем новый документ на регистрацию
                        $doc = $this->newLoginDocument();
                        
                        $login_code = $doc['code'];
                        $document_id = $doc['doc_id'];
                        
                        $this->saveSessionData(array(
                                'code'          => $login_code,
                                'doc_id'        => $document_id,
                        ));
                } else {
                        $login_code = $session_data['code'];
                        $document_id = $session_data['doc_id'];
                };
        
                $qrcode_url = generate_board_url().'/sd/qr/'.$document_id;
                
                $board_url = str_replace(['http://', 'https://'], '', generate_board_url());
                $getdoc_url = 'signdoc://'.$board_url.'/sd/getdoc/'.$document_id;
        
                return array(
                        'TEMPLATE_FILE' => '@gplvote_signdoc/login_body.html',
                        'VARS' => array(
                                'GPLVOTE_CODE'          => $login_code,
                                'GPLVOTE_DOC_ID'        => $document_id,
                                'GPLVOTE_QRCODE_IMG'    => $qrcode_url,
                                'GPLVOTE_GETDOC_URL'    => $getdoc_url,
                                'GPLVOTE_NEW_USERNAME'  => $this->randomUsername(),
                        ),
                );
        }
        
        /**
         * This function generates an array which can be passed to the user_add function in order to create a user
         *
         * @param       string  $username       The username of the new user.
         * @param       string  $password       The password of the new user, may be empty
         * @return      array                           Contains data that can be passed directly to the user_add function.
         */
        private function newUserRow($username, $public_key_id)
        {
                // first retrieve default group id
                $sql = sprintf('SELECT group_id FROM %1$s WHERE group_name = \'%2$s\' AND group_type = \'%3$s\'', GROUPS_TABLE, $this->db->sql_escape('REGISTERED'), GROUP_SPECIAL);
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                if(!$row)
                {
                        trigger_error('NO_GROUP');
                }
                // generate user account data
                return array(
                        'username'              => $username,
                        'user_password'         => $public_key_id,
                        'user_email'            => '',
                        'group_id'              => (int)$row['group_id'],
                        'user_type'             => USER_NORMAL,
                        'user_ip'               => $this->user->ip,
                        'user_new'              => ($this->config['new_member_post_limit']) ? 1 : 0,
                );
        }

        private function randomUserName() {
                $username = '';
                do {
                        $username = 'user'.$this->randomString(6, '0123456789');
                        
                        $sql = sprintf('SELECT * FROM %1$s WHERE username = \'%2$s\'', USERS_TABLE, $username);
                        $result = $this->db->sql_query($sql);
                        $user_row = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);
                } while ($user_row);
                
                return $username;
        }
        
        private function newLoginDocument() {
                $code = $this->randomCode();
                $doc_id = 'in:'.$this->randomDocId();
                $sql = sprintf('INSERT INTO %1$s (id, code, created_at) VALUES (\'%2$s\', \'%3$s\', %4$u)', LOGIN_SIGNS, $doc_id, $code, time());
                $result = $this->db->sql_query($sql);
                $this->db->sql_freeresult($result);
                
                return array(
                        'code'          => $code,
                        'doc_id'        => $doc_id,
                );
        }
        
        private function randomCode() {
                $code = '';
                do {
                        $code = $this->randomString(6);
                        
                        $sql = sprintf('SELECT * FROM %1$s WHERE code = \'%2$s\'', LOGIN_SIGNS, $code);
                        $result = $this->db->sql_query($sql);
                        $sign_row = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);
                } while ($sign_row);
                
                return $code;
        }
        
        private function randomDocId() {
                $doc_id = '';
                do {
                        $doc_id = $this->randomString(16);
                        
                        $sql = sprintf('SELECT * FROM %1$s WHERE id = \'%2$s\'', LOGIN_SIGNS, $doc_id);
                        $result = $this->db->sql_query($sql);
                        $sign_row = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);
                } while ($sign_row);
                
                return $doc_id;
        }
        
        private function randomString($length = 10, $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") {
                return substr(str_shuffle($chars), 0, $length);
        }
        
        private function restoreSessionData() {
                return null;
        }
        
        private function saveSessionData($data) {
        }
}
