<?php
/**
 * SiteSettingFixture
 *
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('SiteSettingFixture', 'SiteManager.Test/Fixture');

/**
 * Summary for SiteSettingFixture
 */
class SiteSettingForMailFixture extends SiteSettingFixture {

/**
 * Full Table Name
 *
 * @var string
 */
	public $table = 'site_settings';

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '1',
			'language_id' => '0',
			'key' => 'Mail.from',
			'value' => 'from@dummy.com',
			'label' => 'Mail.from',
			'weight' => '1',
		),
	);

}
