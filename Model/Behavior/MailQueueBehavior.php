<?php
/**
 * メールキュー Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsMail', 'Mails.Utility');

/**
 * メールキュー Behavior
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Mails\Model\Behavior
 */
class MailQueueBehavior extends ModelBehavior {

/**
 * @var string 承認機能の種類：使用しない
 */
	const MAIL_QUEUE_WORKFLOW_TYPE_NONE = 'none';

/**
 * @var string 承認機能の種類：ワークフロー
 */
	const MAIL_QUEUE_WORKFLOW_TYPE_WORKFLOW = 'workflow';

/**
 * @var string 承認機能の種類：コンテンツコメント
 */
	const MAIL_QUEUE_WORKFLOW_TYPE_COMMENT = 'contentComment';

/**
 * @var bool 削除済みか
 */
	private $__isDeleted = null;

/**
 * @var NetCommonsMail
 */
	private $__netCommonsMail = null;

/**
 * setup
 *
 * @param Model $model モデル
 * @param array $settings 設定値
 * @return void
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::setup
 */
	public function setup(Model $model, $settings = array()) {
		$this->settings[$model->alias] = $settings;

		// --- 設定ないパラメータの処理
		$workflowType = Hash::get($this->settings, $model->alias . '.workflowType');
		if ($workflowType === null) {
			// --- ワークフローのstatusによって送信内容を変える
			if ($model->Behaviors->loaded('Workflow.Workflow')) {

			} else {

			}
			$this->settings[$model->alias]['workflowType'] = self::MAIL_QUEUE_WORKFLOW_TYPE_WORKFLOW;
		}

		$this->__isDeleted = false;
		$this->__netCommonsMail = new NetCommonsMail();
	}

/**
 * afterSave is called after a model is saved.
 *
 * @param Model $model Model using this behavior
 * @param bool $created True if this save created a new record
 * @param array $options Options passed from Model::save().
 * @return bool
 * @see Model::save()
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::afterSave
 */
	public function afterSave(Model $model, $created, $options = array()) {
		// --- メールを送るか
		if (! self::isMailSend($model)) {
			return true;
		}

		// --- 定型文をセット
		//$mail = new NetCommonsMail();
		$languageId = Current::read('Language.id');
		//$mail->initPlugin($languageId);
		$this->__netCommonsMail->__setMailSettingPlugin($languageId);
		//$mail->assignTags($this->tags);

		// --- 定型文の変換タグをセット
		$this->settings[$model->alias];
		$embedTags = Hash::get($this->settings, $model->alias . '.embedTags');
		foreach ($embedTags as $embedTag => $dataKey) {
			$dataValue = Hash::get($model->data, $dataKey);
			$this->__netCommonsMail->assignTag($embedTag, $dataValue);
		}

		$contentKey = $model->data[$model->alias]['key'];

		// fullpassのURL
		$url = NetCommonsUrl::actionUrl(array(
			'controller' => Current::read('Plugin.key'),
			'action' => 'view',
			'block_id' => Current::read('Block.id'),
			'frame_id' => Current::read('Frame.id'),
			'key' => $contentKey
		));
		$url = NetCommonsUrl::url($url, true);
		$this->__netCommonsMail->assignTag('X-URL', $url);

		// --- ワークフローのstatusによって送信内容を変える
		if ($model->Behaviors->loaded('Workflow.Workflow')) {
			// 各プラグインが承認機能を使うかどうかは、気にしなくてＯＫ。承認機能を使わないなら status=公開が飛んでくるため。

			$MailQueue = ClassRegistry::init('Mails.MailQueue');

			$workflowComment = Hash::get($model->data, 'WorkflowComment.comment');
			$this->__netCommonsMail->assignTag('X-APPROVAL_COMMENT', $workflowComment);

			// タグ変換：メール定型文をタグ変換して、生文に変換する
			$this->__netCommonsMail->assignTagReplace();

			$status = $model->data[$model->alias]['status'];
			$workflowType = Hash::get($this->settings, $model->alias . '.workflowType');

			if ($workflowType == self::MAIL_QUEUE_WORKFLOW_TYPE_WORKFLOW) {

				// 暫定対応：現時点では、承認機能=ON, OFFでも投稿者に承認完了通知メールを送る。今後見直し予定
				if ($status == WorkflowComponent::STATUS_PUBLISHED) {
					// 公開
					// dataの準備
					$data = $this->__readyData($mail, $contentKey, $languageId, $roomId, $userId, $toAddress, $sendTime);

					/** @see MailQueue::saveQueueByUserId() */
					/** @see MailQueue::saveQueueByRoomId() */
					$MailQueue->saveQueueByUserId($contentKey, '');
					$MailQueue->saveQueueByRoomId($contentKey);

				} elseif ($status == WorkflowComponent::STATUS_APPROVED) {
					// 承認依頼
				} elseif ($status == WorkflowComponent::STATUS_DISAPPROVED) {
					// 差戻し
				}


			} elseif ($workflowType == self::MAIL_QUEUE_WORKFLOW_TYPE_COMMENT) {
				// --- ここにコンテンツコメントの承認時の処理、書く
			}
		} else {
			// --- ここにワークフローの機能自体、使ってないプラグインの処理を書く
		}

//CakeLog::debug(print_r($model->data, true));
//CakeLog::debug(print_r($this->settings, true));
		// --- 送信者データ取得
		// --- メールキューSave

		return true;
	}

/**
 * dataの準備
 *
 * @param NetCommonsMail $mail NetCommonsメール
 * @param string $contentKey コンテンツキー
 * @param int $languageId 言語ID
 * @param int $roomId ルームID
 * @param int $userId ユーザーID
 * @param string $toAddress 送信先メールアドレス
 * @param date $sendTime 送信日時
 * @return array data
 */
	private function __readyData(NetCommonsMail $mail, $contentKey, $languageId, $roomId = null, $userId = null, $toAddress = null, $sendTime = null) {
		if ($sendTime === null) {
			$sendTime = NetCommonsTime::getNowDatetime();
		}

		$blockKey = Current::read('Block.key');
		$pluginKey = Current::read('Plugin.key');
		//$languageId = Current::read('Language.id');
		$replyTo = key($mail->replyTo());
		//$replyTo = empty($this->replyTo()) ? $this->replyTo() : null;

		$data = array(
			'MailQueue' => array(
				'language_id' => $languageId,
				'plugin_key' => $pluginKey,
				'block_key' => $blockKey,
				'content_key' => $contentKey,
				'replay_to' => $replyTo,
				'mail_subject' => $mail->subject,
				'mail_body' => $mail->body,
				'send_time' => $sendTime,
			),
			'MailQueueUser' => array(
				'plugin_key' => $pluginKey,
				'block_key' => $blockKey,
				'content_key' => $contentKey,
				'user_id' => $userId,
				'room_id' => $roomId,
				'to_address' => $toAddress,
			)
		);

		return $data;
	}

/**
 * beforeDelete
 * コンテンツが削除されたら、キューに残っているメールも削除
 *
 * @param Model $model Model using this behavior
 * @param bool $cascade If true records that depend on this record will also be deleted
 * @return mixed False if the operation should abort. Any other result will continue.
 * @throws InternalErrorException
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::beforedelete
 * @link http://book.cakephp.org/2.0/ja/models/callback-methods.html#beforedelete
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function beforeDelete(Model $model, $cascade = true) {
		// 多言語のコンテンツを key を使って、Model::deleteAll() で削除した場合を想定
		// 削除済みなら、もう処理をしない
		if ($this->__isDeleted) {
			return;
		}

		// コンテンツ取得
		$content = $model->find('first', array(
			'conditions' => array($model->alias . '.id' => $model->id)
		));

		$model->loadModels([
			'MailQueue' => 'Mails.MailQueue',
			'MailQueueUser' => 'Mails.MailQueueUser',
		]);

		// キューの配信先 削除
		if (! $model->MailQueueUser->deleteAll(array($model->MailQueueUser->alias . '.content_key' => $content[$model->alias]['key']), false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		// キュー 削除
		if (! $model->MailQueue->deleteAll(array($model->MailQueue->alias . '.content_key' => $content[$model->alias]['key']), false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		$this->__isDeleted = true;
		return true;
	}

/**
 * メールを送るか
 *
 * @param Model $model Model using this behavior
 * @param date $sendTime 送信日時
 * @return bool
 */
	public function isMailSend(Model $model, $sendTime = null) {
		/** @see MailSetting::getMailSettingPlugin() */
		$MailSetting = ClassRegistry::init('Mails.MailSetting');
		$mailSetting = $MailSetting->getMailSettingPlugin();
		$isMailSend = Hash::get($mailSetting, 'MailSetting.is_mail_send');

		if (! $isMailSend) {
			return false;
		}

		if (isset($sendTime)) {
			// ここに、クーロン設定なし：未来日メール送信しない 処理を記述
		}

		if (! $model->Behaviors->loaded('Workflow.Workflow')) {
			return true;
		}

		$status = Hash::get($model->data, $model->alias . '.status');
		// 一時保存はメール送らない
		if ($status == WorkflowComponent::STATUS_IN_DRAFT) {
			return false;
		}

		return true;
	}
}
