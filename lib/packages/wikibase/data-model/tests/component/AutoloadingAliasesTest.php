<?php

namespace Tests\Wikibase\DataModel;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AutoloadingAliasesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider oldClassNameProvider
	 */
	public function testClassExists( $className ) {
		$this->assertTrue(
			class_exists( $className ),
			'Class name "' . $className . '" should still exist as alias'
		);
	}

	public function oldClassNameProvider() {
		return array_map(
			function( $className ) {
				return array( $className );
			},
			array(
				'Wikibase\EntityId',
				'Wikibase\ItemObject',
				'Wikibase\ReferenceObject',
				'Wikibase\StatementObject',
				'Wikibase\ClaimObject',
			)
		);

	}

}
