<?php
App::import('Core', 'Controller');
App::import('Component', 'QueueEmail.QueueEmail');

Mock::generatePartial('Controller', 'MockController', array('stop', 'header'));
Mock::generatePartial('QueueEmailComponent', 'MockQueueEmailComponent', array('__smtp', '__mail'));

class QueueEmailComponentTestCase extends CakeTestCase {

	var $fixtures = array(
		'plugin.queue_email.queue'
	);

	function startTest() {
		$this->Controller = new MockController();
		$this->QueueEmail = new MockQueueEmailComponent();
		$this->QueueEmail->initialize($this->Controller);
		$this->QueueEmail->setReturnValue('__mail', true);
		$this->QueueEmail->setReturnValue('__smtp', true);
	}

	function endTest() {
		unset($this->Controller);
		unset($this->QueueEmail);
	}

	function testSend() {
		$this->QueueEmail->expectNever('__mail');
		$this->QueueEmail->expectNever('__smtp');

		$this->QueueEmail->to = 'test@test.com';
		$this->QueueEmail->from = 'test@test.com';
		$this->QueueEmail->subject = 'A queued Email';
		$this->QueueEmail->send();
		$queue = $this->QueueEmail->Model->read();
		$this->assertTrue($queue['Queue']['subject'], 'A queued Email');

		$this->QueueEmail->reset();
		$this->QueueEmail->to = 'test@test.com';
		$this->QueueEmail->from = 'testfrom@test.com';
		$this->QueueEmail->subject = 'A queued Email';
		$this->QueueEmail->delivery = 'smtp';
		$this->QueueEmail->smtpOptions = array(
			'port' => 25,
			'host' => 'example.smtp.server',
			'username' => 'username',
			'password' => 'password'
		);
		$this->QueueEmail->send();
		$queue = $this->QueueEmail->Model->read();
		$this->assertTrue($queue['Queue']['smtp_options'], serialize(array(
			'port' => 25,
			'host' => 'example.smtp.server',
			'username' => 'username',
			'password' => 'password'
		)));

		$result = unserialize($queue['Queue']['header']);
		unset($result[3]);
		unset($result[4]);
		$result = array_values($result);
		$expected = array(
			'To: test@test.com',
			'From: testfrom@test.com',
			'Subject: A queued Email',
			'X-Mailer: CakePHP Email Component',
			'Content-Type: text/plain; charset=UTF-8',
			'Content-Transfer-Encoding: 7bit'
		);
		$this->assertEqual($result, $expected);

		$this->QueueEmail->reset();
		$this->QueueEmail->to = 'test@test.com';
		$this->QueueEmail->from = 'test@test.com';
		$this->QueueEmail->subject = 'A queued Email';
		$this->QueueEmail->send('This is a test');
		$queue = $this->QueueEmail->Model->read();

		$result = unserialize($queue['Queue']['message']);
		$expected = array(
			'This is a test',
			'',
			''
		);
		$this->assertEqual($result, $expected);

		$result = unserialize($queue['Queue']['header']);
		$expected = 'To: test@test.com';
		$this->assertEqual($result[0], $expected);
	}

	function testInterpret() {
		$result = $this->QueueEmail->interpret($this->QueueEmail->Model->read(null, 1));
		$results = $result['Queue']['smtp_options'];
		$this->assertEqual($results, null);

		$result = $this->QueueEmail->interpret($this->QueueEmail->Model->read(null, 2));
		$results = $result['Queue']['smtp_options'];
		$expected = array(
			'port' => 25,
			'host' => 'example.smtp.server',
			'username' => 'username',
			'password' => 'password',
		);
		$this->assertEqual($results, $expected);
		
		$result = $this->QueueEmail->interpret($this->QueueEmail->Model->read(null, 3));
		$results = implode(PHP_EOL, $result['Queue']['message']);
		$expected = <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<body>
<p>This is a test <strong>with</strong> html.</p>
</body>
</html>
HTML;
		$this->assertEqual($results, $expected);
		
		$myAlias = new Model(array(
			'name' => 'QueueEmail.Queue',
			'alias' => 'MyAlias',
			'table' => 'queues',
			'ds' => 'test_suite'
		));
		
		$result = $this->QueueEmail->interpret($myAlias->read(null, 2));
		$results = $result['MyAlias']['smtp_options'];
		$expected = 'a:4:{s:4:"port";i:25;s:4:"host";s:19:"example.smtp.server";s:8:"username";s:8:"username";s:8:"password";s:8:"password";}';
		$this->assertEqual($results, $expected);
		
		$result = $this->QueueEmail->interpret($myAlias->read(null, 2), 'MyAlias');
		$results = $result['MyAlias']['smtp_options'];
		$expected = array(
			'port' => 25,
			'host' => 'example.smtp.server',
			'username' => 'username',
			'password' => 'password',
		);
		$this->assertEqual($results, $expected);
		
		$data = $this->QueueEmail->Model->read(null, 2);
		$data['ExtraModel'] = array(
			'id' => 1
		);
		
		$email = $this->QueueEmail->interpret($data);
		$this->assertTrue(isset($email['ExtraModel']));
		
		$result = $email['Queue']['smtp_options'];
		$this->assertIsA($result, 'Array');
		
		$email = $this->QueueEmail->interpret($data['Queue']);
		$result = $email['smtp_options'];
		$this->assertIsA($result, 'Array');
	}

	function testSendWithoutQueue() {
		$this->QueueEmail->expectOnce('__mail');
		$this->QueueEmail->queue = false;
		$this->assertTrue($this->QueueEmail->send());
	}

}

?>