<?php
/**
 * MailQueueUserFixture
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * MailQueueUserFixture
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Mails\Test\Fixture
 */
class MailQueueUserFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'key' => 'primary', 'comment' => 'ID'),
		'plugin_key' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'index', 'collate' => 'utf8_general_ci', 'comment' => 'プラグインKey', 'charset' => 'utf8'),
		'block_key' => array('type' => 'string', 'null' => true, 'default' => null, 'key' => 'index', 'collate' => 'utf8_general_ci', 'comment' => 'ブロックKey', 'charset' => 'utf8'),
		'content_key' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '各プラグインのコンテンツKey', 'charset' => 'utf8'),
		'mail_queue_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false, 'comment' => '個別送信パターン用（user_id,to_address）'),
		'user_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false, 'key' => 'index', 'comment' => 'ユーザに送信, 個別送信パターン1'),
		'room_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false, 'key' => 'index', 'comment' => 'ルームに所属しているユーザに送信, 複数人パターン'),
		'to_address' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => 'メールアドレスで送信, 個別送信パターン2', 'charset' => 'utf8'),
		'send_room_permission' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => 'ルーム送信時のパーミッション', 'charset' => 'utf8'),
		'not_send_room_user_ids' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => 'ルーム送信時に送らない複数のユーザ', 'charset' => 'utf8'),
		'created_user' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false, 'comment' => '作成者'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => '作成日時'),
		'modified_user' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false, 'comment' => '更新者'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => '更新日時'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'fk_mail_queue_users_plugins1_idx' => array('column' => 'plugin_key', 'unique' => 0, 'length' => array('plugin_key' => '191')),
			'fk_mail_queue_users_blocks1_idx' => array('column' => 'block_key', 'unique' => 0, 'length' => array('block_key' => '191')),
			'fk_mail_queue_users_users1_idx' => array('column' => 'user_id', 'unique' => 0),
			'fk_mail_queue_users_rooms1_idx' => array('column' => 'room_id', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		// user_id送信パターン
		array(
			'id' => 1,
			'plugin_key' => 'Lorem ipsum dolor sit amet',
			'block_key' => 'block_1',
			'content_key' => 'content_1',
			'mail_queue_id' => '1',
			'user_id' => '1',
			'room_id' => null,
			'to_address' => null,
			'send_room_permission' => null,
			'not_send_room_user_ids' => null,
		),
		// メールアドレス送信パターン
		array(
			'id' => 2,
			'plugin_key' => 'Lorem ipsum dolor sit amet',
			'block_key' => 'block_1',
			'content_key' => 'content_1',
			'mail_queue_id' => 1,
			'user_id' => null,
			'room_id' => null,
			'to_address' => 'to@dummp.com',
			'send_room_permission' => null,
			'not_send_room_user_ids' => null,
		),
		// room_id送信パターン
		array(
			'id' => 3,
			'plugin_key' => 'Lorem ipsum dolor sit amet',
			'block_key' => 'block_1',
			'content_key' => 'content_1',
			'mail_queue_id' => 1,
			'user_id' => null,
			'room_id' => '2',
			'to_address' => null,
			'send_room_permission' => 'mail_content_receivable',
			'not_send_room_user_ids' => '1|2',
		),
		array(
			'id' => 4,
			'plugin_key' => 'Lorem ipsum dolor sit amet',
			'block_key' => 'block_1',
			'content_key' => 'content_1',
			'mail_queue_id' => 1,
			'user_id' => null,
			'room_id' => '2',
			'to_address' => null,
			'send_room_permission' => 'mail_answer_receivable',
			'not_send_room_user_ids' => '1|2',
		),
		// 送信パターン全部null
		array(
			'id' => 5,
			'plugin_key' => 'Lorem ipsum dolor sit amet',
			'block_key' => 'block_1',
			'content_key' => 'content_1',
			'mail_queue_id' => 1,
			'user_id' => null,
			'room_id' => null,
			'to_address' => null,
			'send_room_permission' => null,
			'not_send_room_user_ids' => null,
		),
		// mail_queue_id=2
		array(
			'id' => 6,
			'plugin_key' => 'Lorem ipsum dolor sit amet',
			'block_key' => 'block_999',
			'content_key' => 'content_2',
			'mail_queue_id' => 2,
			'user_id' => null,
			'room_id' => null,
			'to_address' => 'to@dummp.com',
			'send_room_permission' => null,
			'not_send_room_user_ids' => null,
		),
	);

}
