<?php
/**
 * Queues table schema
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Jeremy Harris
 * @link          http://42pixels.com
 * @package       queue_email
 * @subpackage    queue_email.config.schema
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Queue Schema
 *
 * @package       queue_email
 * @subpackage    queue_email.config.schema
 */
class QueueSchema extends CakeSchema {

	var $name = 'Queue';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $queues = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'to' => array('type' => 'text', 'default' => NULL),
		'cc' => array('type' => 'text', 'default' => NULL),
		'bcc' => array('type' => 'text', 'default' => NULL),
		'from' => array('type' => 'string', 'default' => NULL),
		'subject' => array('type' => 'string', 'default' => NULL),
		'delivery' => array('type' => 'string', 'default' => NULL, 'length' => 4),
		'smtp_options' => array('type' => 'text', 'default' => NULL),
		'message' => array('type' => 'text', 'text' => NULL),
		'header' => array('type' => 'text', 'text' => NULL),
		'attempts' => array('type' => 'text', 'text' => NULL),
		'status' => array('type' => 'integer', 'length' => 2),
		'created' => array('type' => 'datetime', 'default' => NULL),
		'modified' => array('type' => 'datetime', 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

}

?>