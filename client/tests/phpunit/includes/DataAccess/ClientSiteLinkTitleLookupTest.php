<?php

namespace Wikibase\Client\Tests\DataAccess;

use Wikibase\Client\DataAccess\ClientSiteLinkTitleLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Client\DataAccess\ClientSiteLinkTitleLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class ClientSiteLinkTitleLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getTitleForIdProvider
	 */
	public function testGetTitleForId(
		EntityId $id,
		$clientSiteId,
		$expected
	) {
		$siteLinkLookup = $this->getMock( SiteLinkLookup::class );
		$siteLinkLookup->expects( $this->any() )
			->method( 'getLinks' )
			->with( [ $id->getNumericId() ], [ $clientSiteId ] )
			->will( $this->returnCallback( function ( array $numericIds, array $siteIds ) {
				// TODO: SiteLinkLookup::getLinks does have a bad, bad interface.
				return $siteIds === [ 'dewiki' ] ? [ [ 1 => 'Berlin' ] ] : null;
			} ) );

		$lookup = new ClientSiteLinkTitleLookup( $siteLinkLookup, $clientSiteId );
		$title = $lookup->getTitleForId( $id );

		if ( $expected === null ) {
			$this->assertNull( $title );
		} else {
			$this->assertSame( $expected, $title->getPrefixedText() );
		}
	}

	public function getTitleForIdProvider() {
		return [
			[ new PropertyId( 'P1' ), 'enwiki', null ],
			[ new ItemId( 'Q1' ), 'enwiki', null ],
			[ new ItemId( 'Q2' ), 'enwiki', null ],
			[ new ItemId( 'Q2' ), 'dewiki', 'Berlin' ],
		];
	}

}
