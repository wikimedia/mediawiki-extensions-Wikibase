<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Term\Fingerprint
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FingerprintTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorSetsValues() {
		$labels = $this->getMockBuilder( 'Wikibase\DataModel\Term\LabelList' )
			->disableOriginalConstructor()->getMock();

		$descriptions = $this->getMockBuilder( 'Wikibase\DataModel\Term\DescriptionList' )
			->disableOriginalConstructor()->getMock();

		$aliases = $this->getMockBuilder( 'Wikibase\DataModel\Term\AliasGroupList' )
			->disableOriginalConstructor()->getMock();

		$fingerprint = new Fingerprint( $labels, $descriptions, $aliases );

		$this->assertEquals( $labels, $fingerprint->getLabels() );
		$this->assertEquals( $descriptions, $fingerprint->getDescriptions() );
		$this->assertEquals( $aliases, $fingerprint->getAliases() );
	}

}
