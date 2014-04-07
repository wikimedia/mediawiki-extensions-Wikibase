<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Label;
use Wikibase\DataModel\Term\LabelList;

/**
 * @covers Wikibase\DataModel\Term\LabelList
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LabelListTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNonDescriptions_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new LabelList( array( $this->getMock( 'Wikibase\DataModel\Term\Term' ) ) );
	}

	public function testGivenDescriptions_descriptionsAreSet() {
		$descriptions = array(
			'foo' => new Label( 'foo', 'bar' )
		);

		$list = new LabelList( $descriptions );

		$this->assertEquals( $descriptions, iterator_to_array( $list ) );
	}

}
