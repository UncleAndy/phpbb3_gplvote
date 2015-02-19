<?php
/**
*
* @package phpBB Extension - GPLVote SignDoc
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

namespace gplvote\signdoc\migrations;

class migration_20150218170400_first extends \phpbb\db\migration\migration
{
        public function update_schema()
        {
                return array(
                        'add_tables'            => array(
                                $this->table_prefix . 'login_signs' => array(
                                        'COLUMNS'               => array(
                                                'id'                    => array('VCHAR:64', ''),
                                                'code'                  => array('VCHAR:32', ''),
                                                'sign'                  => array('TEXT', ''),
                                                'public_key'            => array('TEXT', ''),
                                                'public_key_id'         => array('VCHAR:64', ''),
                                                'created_at'            => array('TIMESTAMP', null),
                                                'signed_at'             => array('TIMESTAMP', null),
                                        ),
                                        'PRIMARY_KEY'   => 'id',
                                        'KEYS'          => array(
                                            'code'                     => array('INDEX', 'code'),
                                            'key_id'            => array('INDEX', 'public_key_id'),
                                        ),
                                ),
                                $this->table_prefix . 'users_public_keys' => array(
                                        'COLUMNS'               => array(
                                                'id'                    => array('UINT', null, 'auto_increment'),
                                                'user_id'               => array('UINT', null),
                                                'public_key'            => array('TEXT', ''),
                                                'public_key_id'         => array('VCHAR:64', ''),
                                                'created_at'            => array('TIMESTAMP', null),
                                        ),
                                        'PRIMARY_KEY'   => 'id',
                                        'KEYS'          => array(
                                            'user_id'                       => array('INDEX', 'user_id'),
                                            'pkey_id'                 => array('INDEX', 'public_key_id'),
                                        ),
                                ),
                        ),
                );
        }

        public function revert_schema()
        {
                return array(
                        'drop_tables'           => array(
                                $this->table_prefix . 'login_signs',
                                $this->table_prefix . 'users_public_keys',
                        ),
                );
        }
}
