<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\WittyPantsConfig
*/
class WittyPantsConfigTest extends Test
{
	/**
	* @test
	*/
	public function tagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('WittyPants', null, array('tagName' => 'XYZ'));

		$this->assertArrayMatches(
			array('tagName' => 'XYZ'),
			$this->cb->WittyPants->getConfig()
		);
	}

	/**
	* @test
	*/
	public function attrName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('WittyPants', null, array('attrName' => 'xyz'));

		$this->assertArrayMatches(
			array('attrName' => 'xyz'),
			$this->cb->WittyPants->getConfig()
		);
	}

	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('WittyPants');
		unset($this->cb->WittyPants);
		$this->cb->loadPlugin('WittyPants');
	}

	/**
	* @test
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../../src/TextFormatter/Plugins/WittyPantsParser.js',
			$this->cb->WittyPants->getJSParser()
		);
	}

	/**
	* @test
	*/
	public function getJSConfig_returns_no_regexp_with_lookbehind_assertions()
	{
		$this->assertNotContains(
			'(?<',
			serialize($this->cb->WittyPants->getJSConfig())
		);
	}
}