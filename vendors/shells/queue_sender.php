<?php
/**
 * Queue sending shell.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Jeremy Harris
 * @link          http://42pixels.com
 * @package       queue_email
 * @subpackage    queue_email.vendors.shells
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Includes
 */
App::import('Core', 'Controller');
App::import('Component', 'Email');
App::import('Model', 'Queue');

/**
 * Queue Sender Shell
 *
 * Sends emails from the queue
 *
 * @package       queue_email
 * @subpackage    queue_email.vendors.shells
 */
class QueueSenderShell extends Shell { 

/**
 * Default batch size. Customize this by setting 
 * {{{
 * Configure::write('QueueEmail.batchSize', 25);
 * }}}
 * 
 * Or by passing `-batchSize <number>` to the shell
 * 
 * @var integer
 */
	var $limit = 50;
	
/**
 * Default maximum attempts. Customize this by setting
 * {{{
 * Configure::write('QueueEmail.maxAttempts', 10);
 * }}}
 * 
 * Or by passing `-maxAttempts <number>` to the shell
 * 
 * @var type 
 */
	var $maxAttempts = 5;
	
/**
 * By default, emails are removed from the queue table when they send successfully.
 * You can override this by setting the Configure setting
 * {{{
 * Configure::write('QueueEmail.deleteAfter', false);
 * }}}
 * 
 * Or by passing `-deleteAfter false` to the shell
 * 
 * @var type 
 */	
	var $deleteAfter = true;

/**
 * Startup function
 */
	function startup() {
		$this->Controller = new Controller();
		$this->Email =& new EmailComponent();
		$this->Email->initialize($this->Controller);
		$this->Queue =& ClassRegistry::init('Queue');
	}

/**
 * Override main() for help message hook
 *
 * @access public
 */
	function main() {
		$out  = "Available QueueEmail commands:"."\n";
		$out .= "\t - send \$id -batchSize \$size -maxAttempts \$attempts -deleteAfter \$delete\n";
		$out .= "\t - help\n\n";
		$out .= "For help, run the 'help' command.  For help on a specific command, run 'help <command>'";
		$this->out($out);
	}

/**
 * Shows help for the shell commands
 *
 * @access public
 */
	function help() {
		$out  = "Usage: cake queue_email <command>"."\n";
		$out .= "-----------------------------------------------\n";

		$command = $this->args[0];

		if (empty($command)) {
			$out  = "Available QueueEmail commands:"."\n";
			$out .= "\t - send \$id -batchSize \$size -maxAttempts \$attempts -deleteAfter \$delete\n";
			$out .= "\t - help\n\n";
			$out .= "For help, run the 'help' command.  For help on a specific command, run 'help <command>'";
			$this->out($out);
		} else {
			switch ($command) {
				case 'send':
				$out .= "\tcake queue_email send \$id -batchSize \$size -maxAttempts \$attempts -deleteAfter \$delete\n";
				$out .= "\t\tSends a batch of emails as defined by Configure::read('QueueEmail.batchSize').\n";
				$out .= "\t\tYou may also overwrite that size by passing a number to the switch -batchSize.\n";
				$out .= "\t\tIf \$id is passed, it will send that single email instead.\n";
				break;
				default:
				$out .= "$command does not exist. Run 'help' for a list of commands.\n";
				break;
			}

			$this->out($out);
		}
	}

/**
 * Sends a batch of emails or a single email
 *
 * ### Args
 * - 0: A specific email's id to send (leave empty to send a batch)
 *
 * ### Params
 * - `batchSize` Overrides default and configured batch size 
 * - `maxAttempts` Overrides default and configured maximum attempts
 */
	function send() {
		if (!empty($this->args[0])) {
			$conditions = array(
				'id' => $this->args[0]
			);
		}

		$defaults = array(
			'batchSize' => $this->limit,
			'maxAttempts' => $this->maxAttempts,
			'deleteAfter' => $this->deleteAfter
		);
		$config = Configure::read('QueueEmail');
		$config = array_merge($defaults, (array)$config, (array)$this->params);
		
		$conditions['attempts <'] = $config['maxAttempts'];
		$conditions['status'] = 0;
		
		// get batch
		$emails = $this->Queue->find('all', array(
			'limit' => $config['batchSize'],
			'conditions' => $conditions
		));
		
		// update the status so they don't get pulled by another job
		$this->Queue->updateAll(
			array('Queue.status' => 2),
			array('Queue.id' => Set::extract('/Queue/id', $emails))
		);

		foreach ($emails as $email) {
			$this->Queue->id = $email['Queue']['id'];
			if ($this->_send($email)) {
				$this->out('Sent email #'.$email['Queue']['id'].' successfully!');
				if ($config['deleteAfter']) {
					$this->Queue->delete();
				} else {
					$this->Queue->saveField('attempts', (int)$email['Queue']['attempts']+1);
					$this->Queue->saveField('status', 1);
				}
			} else {
				$this->Queue->id = $email['Queue']['id'];
				$this->Queue->saveField('attempts', (int)$email['Queue']['attempts']+1);
				$this->Queue->saveField('status', 0);
				$this->out('Error sending email #'.$email['Queue']['id'].'!');
			}
		}
	}

/**
 * Sends an email based on the results
 *
 * @param array $results
 * @return boolean Success
 * @access protected
 */
	function _send($results) {
		$this->_reset();

		// attach non-null values to their EmailComponent variable counterpart
		foreach ($results['Queue'] as $var => $val) {
			if ($val !== null) {
				if (@unserialize($val) !== false) {
					$val = unserialize($val);
				}
				$varName = Inflector::camelize($var);
				$varName = strtolower(substr($varName, 0, 1)) . substr($varName, 1);
				if (in_array($varName, array('header', 'message'))) {
					$varName = '__'.$varName;
				}
				$this->Email->{$varName} = $val;
			}
		}
		return $this->Email->{'_'.$this->Email->delivery}();
	}

/**
 * Resets all EmailComponent properties
 *
 * @access protected
 */
	function _reset() {
		$this->Email->reset();
		$this->Email->delivery = 'mail';
		$this->Email->layout = 'default';
		$this->Email->sendAs = 'text';
	}
} 

?>