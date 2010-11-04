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
		$expected = array(
			'Queue' => array(
				'id' => 1,
				'to' => 'test@test.com',
				'cc' => null,
				'bcc' => null,
				'from' => 'test@test.com',
				'subject' => 'Mail',
				'delivery' => null,
				'smtp_options' => null,
				'message' => null,
				'header' => null,
				'created' => '2010-09-20 00:00:01',
				'modified' => '2010-09-20 00:00:01',
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->QueueEmail->interpret($this->QueueEmail->Model->read(null, 2));
		$expected = array(
			'Queue' => array(
				'id' => 2,
				'to' => 'test@test.com',
				'cc' => null,
				'bcc' => null,
				'from' => 'test@test.com',
				'subject' => 'Mail',
				'delivery' => 'smtp',
				'smtp_options' => array(
					'port' => 25,
					'host' => 'example.smtp.server',
					'username' => 'username',
					'password' => 'password',
				),
				'message' => null,
				'header' => null,
				'created' => '2010-09-20 00:00:01',
				'modified' => '2010-09-20 00:00:01',
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testSendWithoutQueue() {
		$this->QueueEmail->expectOnce('__mail');
		$this->QueueEmail->queue = false;
		$this->assertTrue($this->QueueEmail->send());
	}

}

?>