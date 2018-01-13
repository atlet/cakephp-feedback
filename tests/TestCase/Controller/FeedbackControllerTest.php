<?php

namespace Feedback\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestCase;
use Feedback\Store\FilesystemStore;

class FeedbackControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Feedback', [
			'configuration' => [
				FilesystemStore::NAME => [
					'location' => TMP,
				],
			],
		]);

		$savepath = Configure::read('Feedback.configuration.Filesystem.location');
		$files = glob($savepath . '*.*') ?: [];
		foreach ($files as $file) {
			unlink($file);
		}
	}

	/**
	 * @expectedException \Cake\Network\Exception\MethodNotAllowedException
	 * @return void
	 */
	public function testSaveInvalid() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'Feedback', 'controller' => 'Feedback', 'action' => 'save']);
	}

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->get(['plugin' => 'Feedback', 'controller' => 'Feedback', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();
	}

	/**
	 * @return void
	 */
	public function testView() {
		$file = time() . '-' . session_id() . '.feedback';
		$savepath = Configure::read('Feedback.configuration.Filesystem.location');
		$data = [
			'screenshot' => '123',
		];
		file_put_contents($savepath . $file, serialize($data));
		$this->assertFileExists($savepath . $file);

		$this->get(['plugin' => 'Feedback', 'controller' => 'Feedback', 'action' => 'viewimage', $file]);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();

		unlink($savepath . $file);
	}

	/**
	 * @return void
	 */
	public function testSave() {
		$data = [];
		$this->post(['plugin' => 'Feedback', 'controller' => 'Feedback', 'action' => 'save'], $data);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();

		$expected = __d('feedback', 'Thank you. Your feedback was saved.');
		$this->assertResponseEquals($expected);
	}

	/**
	 * @return void
	 */
	public function testSaveScreenshot() {
		$data = [
			'screenshot' => 123,
		];
		$this->post(['plugin' => 'Feedback', 'controller' => 'Feedback', 'action' => 'save'], $data);

		$this->assertResponseCode(200);
		$this->assertNoRedirect();

		$savepath = Configure::read('Feedback.configuration.Filesystem.location');

		$feedbacks = [];
		//Loop through files
		foreach (glob($savepath . '*-' . session_id() . '.feedback') as $feedbackfile) {
			$feedbackObject = unserialize(file_get_contents($feedbackfile));
			$feedbacks[$feedbackObject['time']] = $feedbackObject;
		}

		//Sort by time
		krsort($feedbacks);

		$last = array_shift($feedbacks);
		$this->assertSame('123', $last['screenshot']);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		$savepath = Configure::read('Feedback.configuration.Filesystem.location');
		foreach (glob($savepath . '*-' . session_id() . '.feedback') as $feedbackfile) {
			unlink($feedbackfile);
		}
	}

}
