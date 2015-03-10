<?php
require_once dirname(__FILE__).'/../vendor/autoload.php';

class NormalizeReferenceTest extends PHPUnit_Framework_TestCase
{

	public function data()
	{
		return array(
				array('00001/9', '1.9', 1, '1', '9'),
				array('01234/5', '1234-5', 4, '1234', '5'),
				array('00234/6', '0234,6', 4, '234', '6'),
				array('00078/1', '078 1', 3, '078', '1'),
				array('10012/4', '10012/4', 5, '10012', '4'),
			);
	}

	/**
	 * @dataProvider data
	 * @param  string $expected
	 * @param  string $reference
	 * @param  string $prefixExpected
	 * @param  string $sufixExpected
	 */
	public function testNormalizeReference($expected, $reference, $expectedSeparatorPos, $prefixExpected, $sufixExpected)
	{
		$ref = preg_replace('/[^0-9]/', '/', $reference);
		$pos = strpos($ref, '/');
		$this->assertEquals($expectedSeparatorPos, $pos);

		list($prefix, $sufix) = explode('/', $ref);
		$this->assertEquals($prefixExpected, $prefix);
		$this->assertEquals($sufixExpected, $sufix);

		$prefix = str_pad($prefix, 5, '0', STR_PAD_LEFT);
		$this->assertEquals(5, strlen($prefix));

		$ref = implode('/', array($prefix, $sufix));
		$this->assertEquals($expected, $ref);
	}
}