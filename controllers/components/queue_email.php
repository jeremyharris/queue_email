<?php
/**
 * Queues controller class.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Jeremy Harris
 * @link          http://42pixels.com
 * @package       queue_email
 * @subpackage    queue_email.controllers.components
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Includes
 */
App::import('Component', 'Email');
App::import('Model', 'QueueEmail.Queue');

/**
 * Queues Controller
 *
 * Stores emails in the database rather than sending them. Supports all
 * EmailComponent settings
 *
 * @package       queue_email
 * @subpackage    queue_email.controllers.components
 */
class QueueEmailComponent extends EmailComponent { 

/**
 * The original delivery setting
 *
 * @param string
 * @access protected
 */
	var $_oldDelivery = 'mail';

/**
 * Set to false if you don't want to queue an email
 *
 * @var boolean
 */
	var $queue = true;

/**
 * The Queue model
 *
 * @var Queue
 */
	var $Model = null;

/**
 * The initialize function
 *
 * Saves an instance of the Queue model for use
 *
 * @param Controller $controller
 * @param array $settings
 * @see Component::initialize()
 */
	function initialize(&$controller, $settings = array()) {
		$this->Model = ClassRegistry::init('Queue');
		parent::initialize($controller, $settings);
	}

/**
 * Stores emails in the database instead of sending them immediately
 *
 * @return boolean Success
 */
	function _db() {
		$this->Model->create();
		return $this->Model->save(array(
			'to' => serialize($this->to),
			'cc' => serialize($this->cc),
			'bcc' => serialize($this->bcc),
			'from' => $this->from,
			'subject' => $this->subject,
			'delivery' => $this->delivery,
			'smtp_options' => serialize($this->smtpOptions),
			'message' => serialize($this->__message),
			'header' => serialize($this->__header),
		));
	}

/**
 * Interprets a saved Queue as an associative array by unserializing data
 *
 * @param array $message The message
 * @return array
 */
	function interpret($message = array()) {
		if (empty($message)) {
			return false;
		}
		if (isset($message['Queue'])) {
			$message = $message['Queue'];
		}
		foreach ($message as $field => &$value) {
			if (@unserialize($value) !== false) {
				$value = unserialize($value);
			}
		}
		return array(
			'Queue' => $message
		);
	}

/**
 * Override EmailComponent::_mail() to queue the email instead
 *
 * @return boolean Success
 */
	function _mail() {
		if ($this->queue === true) {
			return $this->_db();
		}
		return $this->__mail();
	}


/**
 * Override EmailComponent::_smtp() to queue the email instead
 *
 * @return boolean Success
 */
	function _smtp() {
		if ($this->queue === true) {
			return $this->_db();
		}
		return $this->__smtp();
	}

/**
 * Intercepting function to allow for testing
 *
 * @return boolean Success
 */
	function __smtp() {
		return parent::_smtp();
	}

/**
 * Intercepting function to allow for testing
 *
 * @return boolean Success
 */
	function __mail() {
		return parent::_mail();
	}
	
} 

?>