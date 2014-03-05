<?php

namespace Wikibase\DataModel\Test;

use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers Wikibase\DataModel\SiteLinkList
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider notSiteLinksProvider
	 */
	public function testGivenNonSiteLinks_constructorThrowsException( array $notSiteLinks ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLinkList( $notSiteLinks );
	}

	public function notSiteLinksProvider() {
		return array(
			array(
				array(
					null
				)
			),

			array(
				array(
					42
				)
			),

			array(
				array(
					new SiteLink( 'foo', 'bar' ),
					42,
					new SiteLink( 'baz', 'bah' ),
				)
			),
		);
	}

	public function testEmptyCollectionHasZeroSize() {
		$list = new SiteLinkList( array() );
		$this->assertCount( 0, $list );
	}

}
