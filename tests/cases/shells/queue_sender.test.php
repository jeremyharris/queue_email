<?php
App::import('Shell', 'Shell', false);
App::import('Core', 'Controller');
App::import('Component', 'Email');
App::import('Shell', 'QueueEmail.QueueSender');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

Mock::generatePartial('ShellDispatcher', 'QueueSenderMockShellDispatcher',array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment'));
Mock::generatePartial('QueueSenderShell', 'MockQueueSenderShell',array('in', 'hr', 'out', 'err', '_stop'));
Mock::generatePartial('EmailComponent', 'MockEmailComponent', array('_mail', '_smtp'));
Mock::generatePartial('Controller', 'MockController', array('header', 'beforeFilter'. 'beforeRender'));

class QueueSenderShellTestCase extends CakeTestCase {

	var $fixtures = array(
		'plugin.queue_email.queue'
	);

	function startTest() {
		$this->_oldConfig = Configure::read('QueueEmail');
		Configure::delete('QueueEmail');

		$this->Dispatcher =& new QueueSenderMockShellDispatcher();
		$this->Shell =& new MockQueueSenderShell($this->Dispatcher);
		$this->Shell->Dispatch =& $this->Dispatcher;
		$this->Shell->Dispatch->shellPaths = Configure::read('shellPaths');

		$this->Shell->startup();
		$this->Shell->Controller = new MockController();
		$this->Shell->Email = new MockEmailComponent();
		$this->Shell->Email->initialize($this->Task->Controller);
		$this->Shell->Email->setReturnValue('_mail', true);
		$this->Shell->Email->setReturnValue('_smtp', true);
	}

	function endTest() {
		Configure::write('QueueEmail', $this->_oldConfig);
		unset($this->Shell, $this->Dispatcher);
		ClassRegistry::flush();
	}

	function testSendNoParams() {
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 0);
	}

	function testSendShellParams() {
		$this->Shell->params['batchSize'] = 2;
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 2);
	}

	function testSendConfigParams() {		
		Configure::write('QueueEmail.batchSize', 1);

		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 3);
	}

	function testSendBothParams() {
		Configure::write('QueueEmail.batchSize', 1);
		$this->Shell->params['batchSize'] = 2;
		$this->Shell->params['maxAttempts'] = 1;
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 2);
		
		$this->Shell->params['batchSize'] = 2;
		$this->Shell->params['maxAttempts'] = 1;
		$this->Shell->args[0] = 4;
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 2);
	}

	function testSendIndividualEmail() {
		$this->Shell->args[0] = 3;
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 3);
		$this->assertFalse($this->Shell->Queue->read(null, 3));
	}

	function testSetSmtpOptions() {
		$this->Shell->args[0] = 2;
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertFalse($this->Shell->Queue->read(null, 2));

		$this->assertEqual($this->Shell->Email->smtpOptions, array(
			'port' => '25',
			'host' => 'example.smtp.server',
			'username' => 'username',
			'password' => 'password'
		));
	}
	
	function testCountAttempts() {
		$this->Shell->Email = new MockEmailComponent();
		$this->Shell->Email->initialize($this->Task->Controller);
		
		$this->Shell->Email->setReturnValueAt(0, '_mail', false);
		$this->Shell->Email->setReturnValueAt(0, '_smtp', true);
		$this->Shell->Email->setReturnValueAt(1, '_mail', true);
		$this->Shell->Email->setReturnValueAt(2, '_mail', false);
		
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 2);
		
		$this->Shell->Queue->id = 1;
		$results = $this->Shell->Queue->field('attempts');
		$this->assertEqual($results, 1);
		
		$this->Shell->Queue->id = 4;
		$results = $this->Shell->Queue->field('attempts');
		$this->assertEqual($results, 2);
	}
	
	function testSaveEmails() {
		Configure::write('QueueEmail.deleteAfter', false);
		
		$this->Shell->args[0] = 3;
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 4);
		
		$this->Shell->Queue->id = 3;
		$queue = $this->Shell->Queue->read();
		$result = $queue['Queue']['status'];
		$this->assertEqual($result, 1);
		$result = $queue['Queue']['attempts'];
		$this->assertEqual($result, 1);
		
		$this->Shell->Email->expectNever('_mail');
		$this->Shell->Email->expectNever('_smtp');
		$this->Shell->args[0] = 3;
		$this->Shell->send();
	}
	
	function testPreventFindOverlap() {
		$this->Shell->Queue->id = 1;
		$this->Shell->Queue->saveField('status', 2);
		
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 1);
		
		$this->Shell->Queue->id = 1;
		$this->Shell->Queue->saveField('status', 0);
		
		$this->Shell->send();
		$count = $this->Shell->Queue->find('count');
		$this->assertEqual($count, 0);
	}

}

?>
