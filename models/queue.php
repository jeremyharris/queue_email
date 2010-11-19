<?php
/**
 * Queue model
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Jeremy Harris
 * @link          http://42pixels.com
 * @package       queue_email
 * @subpackage    queue_email.models
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Queue
 *
 * @package       queue_email
 * @subpackage    queue_email.models
 */
class Queue extends QueueEmailAppModel {
	
	var $name = 'Queue';
	
	var $validate = array(
		'to' => array(
			'rule' => 'notempty',
			'required' => true
		),
		'from' => array(
			'rule' => 'notempty',
			'required' => true
		),
	);
}
?>