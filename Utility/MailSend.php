<?php
/**
 * メール送信 Utility
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * メール送信 Utility
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Mails\Utility
 */
class MailSend {

/**
 * メール送信呼び出し
 *
 * @return void
 */
	public static function send() {
		// バックグラウンドでメール送信
		// logrotate問題対応 http://dqn.sakusakutto.jp/2012/08/php_exec_nohup_background.html
		// コマンド例) cake Mails.mailSend
		exec('nohup ' . APP . 'Console' . DS . 'cake Mails.mailSend > /dev/null &');
	}
}