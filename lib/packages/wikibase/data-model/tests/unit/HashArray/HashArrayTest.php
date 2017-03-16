<?php

namespace Wikibase\DataModel\Tests\HashArray;

/**
 * @covers Wikibase\DataModel\HashArray
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group HashArray
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class HashArrayTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param array $elements
	 *
	 * @return array[]
	 */
	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return [ $element ];
			},
			$elements
		);
	}

}
