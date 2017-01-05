<?php

namespace Wikibase\DataModel\Tests;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AutoloadingAliasesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider oldNameProvider
	 */
	public function testAliasExists( /* $className */ ) {
		$this->markTestSkipped( 'No class aliases at the moment' );

		$this->assertTrue(
			class_exists( $className ) || interface_exists( $className ),
			'Class name "' . $className . '" should still exist as alias'
		);
	}

	public function oldNameProvider() {
		return [
			// Full qualified aliases go here.
		];
	}

}
