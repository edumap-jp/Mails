<?php
/**
 * メール送信する・しない Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('ModelBehavior', 'Model');
App::uses('MailQueueBehavior', 'Mails.Model/Behavior');

/**
 * メール送信する・しない Behavior
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Mails\Model\Behavior
 */
class IsMailSendBehavior extends ModelBehavior {

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

		$model->MailSetting = ClassRegistry::init('Mails.MailSetting', true);
		$model->MailQueueUser = ClassRegistry::init('Mails.MailQueueUser', true);
		$model->SiteSetting = ClassRegistry::init('SiteManager.SiteSetting', true);
	}

/**
 * 通常の投稿メールを送るかどうか
 *
 * @param Model $model モデル
 * @param string $typeKey メールの種類
 * @param string $contentKey コンテンツキー
 * @param string $sendTimePublish 開するメール送信日時
 * @param string $settingPluginKey 設定を取得するプラグインキー
 * @return bool
 */
	public function isMailSend(Model $model,
								$typeKey = MailSettingFixedPhrase::DEFAULT_TYPE,
								$contentKey = null,
								$sendTimePublish = null,
								$settingPluginKey = null) {
		if (! $this->isMailSendCommon($model, $typeKey, $settingPluginKey)) {
			return false;
		}

		// 投稿メールOFFなら、メール送らない
		$isMailSendPostKey = MailQueueBehavior::MAIL_QUEUE_SETTING_IS_MAIL_SEND_POST;
		$isMailSendPost = $this->settings[$model->alias][$isMailSendPostKey];
		if (isset($isMailSendPost) && $isMailSendPost == '0') {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		// 公開許可あり（承認者、承認OFF時の一般）の編集 and 投稿メールフラグが未設定の場合、メール送らない
		// 公開記事 編集フラグ
		$isPublishableEdit = $this->__isPublishableEdit($model, $contentKey);
		if ($isPublishableEdit && $isMailSendPost === null) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		return true;
	}

/**
 * リマインダーメールを送るかどうか
 *
 * @param Model $model モデル
 * @param string $typeKey メールの種類
 * @param string $settingPluginKey 設定を取得するプラグインキー
 * @return bool
 */
	public function isMailSendReminder(Model $model,
										$typeKey = MailSettingFixedPhrase::DEFAULT_TYPE,
										$settingPluginKey = null) {
		$useReminder = $this->settings[$model->alias]['reminder']['useReminder'];
		if (! $useReminder) {
			return false;
		}

		if (! $this->isMailSendCommon($model, $typeKey, $settingPluginKey)) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		// リマインダーの公開以外はメール送らない
		$status = Hash::get($model->data, $model->alias . '.status');
		if ($status != WorkflowComponent::STATUS_PUBLISHED) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		// リマインダーが複数日あって、全て日時が過ぎてたら、メール送らない
		$isMailSendReminder = false;
		$sendTimeReminders = $this->settings[$model->alias]['reminder']['sendTimes'];
		foreach ($sendTimeReminders as $sendTime) {
			if ($this->isMailSendTime($model, $sendTime)) {
				$isMailSendReminder = true;
			}
		}
		if (! $isMailSendReminder) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		return true;
	}

/**
 * 公開許可あり（承認者、承認OFF時の一般）の編集か ゲット
 *
 * @param Model $model モデル
 * @param string $contentKey コンテンツキー
 * @return bool
 */
	private function __isPublishableEdit(Model $model, $contentKey) {
		if (!Current::permission('content_comment_publishable')) {
			// 公開権限なし
			return false;
		}

		$workflowType = Hash::get($this->settings, $model->alias . '.workflowType');
		// --- コンテンツコメント
		if ($workflowType == MailQueueBehavior::MAIL_QUEUE_WORKFLOW_TYPE_COMMENT) {
			// 登録日時
			$created = Hash::get($model->data, $model->alias . '.created');
			$isApproveAction = Hash::get($this->settings, $model->alias . '.isCommentApproveAction');

			if (isset($created)) {
				// 新規登録
				return false;
			}
			if ($isApproveAction) {
				// 承認時
				return false;
			}
			return true;
		}

		// --- 通常
		//$contentKey = $this->__getContentKey($model);
		$keyField = $this->settings[$model->alias]['keyField'];
		$conditions = array($model->alias . '.' . $keyField => $contentKey);
		$data = $model->find('all', array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array($model->alias . '.modified DESC'),
			'callbacks' => false,
		));

		if (count($data) <= 1) {
			// 新規登録
			return false;
		}

		// keyに対して2件以上記事がある = 編集
		// 1つ前のコンテンツのステータス
		$beforeStatus = $data[1][$model->alias]['status'];
		$status = $data[0][$model->alias]['status'];

		// 承認ONでもOFFでも、公開中の記事を編集して、公開だったら、公開の編集
		// ・承認ONで、承認者が公開中の記事を編集しても、公開許可ありの編集で、メール送らない
		// ・承認OFFで、公開中の記事を編集しても、公開許可ありの編集で、メール送らない
		// ・・公開中の記事（１つ前の記事のstatus=1）
		// ・・編集した記事が公開（status=1）
		// ※承認ONで公開中の記事を編集して、編集した記事が公開なのは、承認者だけ
		if ($beforeStatus == WorkflowComponent::STATUS_PUBLISHED &&
			$status == WorkflowComponent::STATUS_PUBLISHED) {
			// 公開の編集
			return true;
		}

		// 公開以外の編集
		return false;
	}

/**
 * メールを送るかどうか - 共通処理
 *
 * @param Model $model モデル
 * @param string $typeKey メールの種類
 * @param string $settingPluginKey 設定を取得するプラグインキー
 * @return bool
 */
	public function isMailSendCommon(Model $model,
										$typeKey = MailSettingFixedPhrase::DEFAULT_TYPE,
										$settingPluginKey = null) {
		if ($settingPluginKey === null) {
			$settingPluginKey = Current::read('Plugin.key');
		}

		$from = SiteSettingUtil::read('Mail.from');

		// Fromが空ならメール未設定のため、メール送らない
		if (empty($from)) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		//$settingPluginKey = $this->__getSettingPluginKey($model);
		/** @see MailSetting::getMailSettingPlugin() */
		$mailSettingPlugin = $model->MailSetting->getMailSettingPlugin(null, $typeKey, $settingPluginKey);
		$isMailSend = Hash::get($mailSettingPlugin, 'MailSetting.is_mail_send');

		// プラグイン設定でメール通知を使わないなら、メール送らない
		if (! $isMailSend) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		$status = Hash::get($model->data, $model->alias . '.status');

		// 一時保存はメール送らない
		if ($status == WorkflowComponent::STATUS_IN_DRAFT) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		return true;
	}

/**
 * メール送信日時で送るかどうか
 *
 * @param Model $model モデル
 * @param date $sendTime メール送信日時
 * @return bool
 */
	public function isMailSendTime(Model $model, $sendTime) {
		if ($sendTime === null) {
			return true;
		}

		// SiteSettingからメール設定を取得する
		$useCron = SiteSettingUtil::read('Mail.use_cron');
		$now = NetCommonsTime::getNowDatetime();

		// クーロンが使えなくて未来日なら、未来日メールなので送らない
		if (empty($useCron) && strtotime($now) < strtotime($sendTime)) {
			return false;
		}

		$useReminder = $this->settings[$model->alias]['reminder']['useReminder'];
		if (! $useReminder) {
			return true;
		}

		// リマインダーで日時が過ぎてたら、メール送らない
		if (strtotime($now) > strtotime($sendTime)) {
			return false;
		}

		return true;
	}

/**
 * 通知メールを送るかどうか
 *
 * @param Model $model モデル
 * @param strig $useWorkflow ワークフローの種類
 * @param int $createdUserId 登録ユーザID
 * @return bool
 */
	public function isSendMailQueueNotice(Model $model, $useWorkflow, $createdUserId) {
		$workflowType = Hash::get($this->settings, $model->alias . '.workflowType');
		if ($workflowType == MailQueueBehavior::MAIL_QUEUE_WORKFLOW_TYPE_WORKFLOW) {
			// --- ワークフロー
			// 承認しないなら、通知メール送らない
			//$useWorkflow = $this->__getUseWorkflow($model);
			if (! $useWorkflow) {
				CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
				return false;
			}

		} elseif ($workflowType == MailQueueBehavior::MAIL_QUEUE_WORKFLOW_TYPE_COMMENT) {
			// --- コンテンツコメント
			// コメント承認しないなら、通知メール送らない
			$key = Hash::get($this->settings, $model->alias . '.useCommentApproval');
			$useCommentApproval = Hash::get($model->data, $key);
			if (! $useCommentApproval) {
				CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
				return false;
			}
		}

		$permissionKey = $this->settings[$model->alias]['publishablePermissionKey'];

		// 投稿者がルーム内の承認者だったら、通知メール送らない
		/** @see MailQueueUser::getRolesRoomsUsersByPermission() */
		$rolesRoomsUsers = $model->MailQueueUser->getRolesRoomsUsersByPermission($permissionKey);
		$rolesRoomsUserIds = Hash::extract($rolesRoomsUsers, '{n}.RolesRoomsUser.user_id');
		//$createdUserId = $this->__getCreatedUserId($model);
		if (in_array($createdUserId, $rolesRoomsUserIds)) {
			CakeLog::debug('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ . ')');
			return false;
		}

		return true;
	}
}
