<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_OC_Connector_Sabre_QuotaPlugin extends PHPUnit_Framework_TestCase {

	/**
	 * @var Sabre_DAV_Server
	 */
	private $server;

	/**
	 * @var OC_Connector_Sabre_QuotaPlugin
	 */
	private $plugin;

	public function setUp() {
		$this->server = new Sabre_DAV_Server();
		$this->plugin = new OC_Connector_Sabre_QuotaPlugin();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength($expected, $headers)
	{
		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuota($quota, $headers)
	{
		$this->plugin->fileView = $this->buildFileViewMock($quota);

		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$result = $this->plugin->checkQuota('');
		$this->assertTrue($result);
	}

	/**
	 * @expectedException Sabre_DAV_Exception_InsufficientStorage
	 * @dataProvider quotaExceededProvider
	 */
	public function testCheckExceededQuota($quota, $headers)
	{
		$this->plugin->fileView = $this->buildFileViewMock($quota);

		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$this->plugin->checkQuota('');
	}

	public function quotaOkayProvider() {
		return array(
			array(1024, array()),
			array(1024, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array(1024, array('HTTP_CONTENT_LENGTH' => '512')),
			array(1024, array('HTTP_OC_TOTAL_LENGTH' => '1024', 'HTTP_CONTENT_LENGTH' => '512')),
			// OC\Files\FREE_SPACE_UNKNOWN = -2
			array(-2, array()),
			array(-2, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array(-2, array('HTTP_CONTENT_LENGTH' => '512')),
			array(-2, array('HTTP_OC_TOTAL_LENGTH' => '1024', 'HTTP_CONTENT_LENGTH' => '512')),
		);
	}

	public function quotaExceededProvider() {
		return array(
			array(1023, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array(511, array('HTTP_CONTENT_LENGTH' => '512')),
			array(2047, array('HTTP_OC_TOTAL_LENGTH' => '2048', 'HTTP_CONTENT_LENGTH' => '1024')),
		);
	}

	public function lengthProvider() {
		return array(
			array(null, array()),
			array(1024, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array(512, array('HTTP_CONTENT_LENGTH' => '512')),
			array(2048, array('HTTP_OC_TOTAL_LENGTH' => '2048', 'HTTP_CONTENT_LENGTH' => '1024')),
			array(4096, array('HTTP_OC_TOTAL_LENGTH' => '2048', 'HTTP_X_EXPECTED_ENTITY_LENGTH' => '4096')),
		);
	}

	private function buildFileViewMock($quota) {
		// mock filesysten
		$view = $this->getMock('\OC\Files\View', array('free_space'), array(), '', FALSE);
		$view->expects($this->any())->method('free_space')->withAnyParameters()->will($this->returnValue($quota));

		return $view;
	}

}
