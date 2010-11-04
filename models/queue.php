<?php
/**
 * Queue model
 *
 * @copyright     Copyright 2010, Jeremy Harris
 * @link          http://42pixels.com
 * @package       queue_email
 * @subpackage    queue_email.models
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