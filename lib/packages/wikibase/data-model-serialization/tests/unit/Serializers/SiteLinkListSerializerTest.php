<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Serializers\SiteLinkListSerializer;
use Wikibase\DataModel\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers \Wikibase\DataModel\Serializers\SiteLinkListSerializer
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$siteLink1 = new SiteLink( 'foo', 'bar' );
		$siteLink2 = new SiteLink( 'omg', 'bbq' );

		$siteLinkSerializer = $this->createMock( SiteLinkSerializer::class );
		$siteLinkSerializer->expects( $this->exactly( 2 ) )
			->method( 'serialize' )
			->withConsecutive( [ $siteLink1 ], [ $siteLink2 ] )
			->willReturnCallback( function ( SiteLink $siteLink ) {
				return $siteLink->getPageName();
			} );

		$serializer = new SiteLinkListSerializer( $siteLinkSerializer, false );

		$this->assertEquals(
			[
				$siteLink1->getSiteId() => $siteLink1->getPageName(),
				$siteLink2->getSiteId() => $siteLink2->getPageName(),
			],
			$serializer->serialize( new SiteLinkList( [ $siteLink1, $siteLink2 ] ) )
		);
	}

	public function testSerializeAndUseObjects(): void {
		$serializer = new SiteLinkListSerializer( $this->createStub( SiteLinkSerializer::class ), true );
		$this->assertEquals(
			(object)[],
			$serializer->serialize( new SiteLinkList() )
		);
	}

}
