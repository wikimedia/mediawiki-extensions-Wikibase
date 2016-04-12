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
	public function testAliasExists( $className ) {
		$this->assertTrue(
			class_exists( $className ) || interface_exists( $className ),
			'Class name "' . $className . '" should still exist as alias'
		);
	}

	public function oldNameProvider() {
		return array(
			// Full qualified aliases go here.
			array( 'Wikibase\DataModel\Claim\Claim' ),
			array( 'Wikibase\DataModel\Claim\ClaimGuid' ),
			array( 'Wikibase\DataModel\StatementListProvider' ),
		);
	}

}
