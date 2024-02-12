<?php

declare( strict_types = 1 );

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

	private SiteLink $siteLink1;
	private SiteLink $siteLink2;

	public function setUp(): void {
		$this->siteLink1 = new SiteLink( 'foo', 'bar' );
		$this->siteLink2 = new SiteLink( 'omg', 'bbq' );
	}

	private function createSiteLinkSerializer(): SiteLinkSerializer {
		$siteLinkSerializer = $this->createMock( SiteLinkSerializer::class );
		$siteLinkSerializer->expects( $this->atMost( 2 ) )
			->method( 'serialize' )
			->willReturnMap( [
				[ $this->siteLink1, $this->siteLink1->getPageName() ],
				[ $this->siteLink2, $this->siteLink2->getPageName() ],
			] );

		return $siteLinkSerializer;
	}

	public function testSerialize(): void {
		$serializer = new SiteLinkListSerializer( $this->createSiteLinkSerializer(), false );

		$this->assertEquals(
			[
				$this->siteLink1->getSiteId() => $this->siteLink1->getPageName(),
				$this->siteLink2->getSiteId() => $this->siteLink2->getPageName(),
			],
			$serializer->serialize( new SiteLinkList( [ $this->siteLink1, $this->siteLink2 ] ) )
		);
	}

	public function testSerializeEmptyListUsesArrayByDefault(): void {
		$serializer = new SiteLinkListSerializer( $this->createSiteLinkSerializer(), false );
		$this->assertEquals(
			[],
			$serializer->serialize( new SiteLinkList() )
		);
	}

	public function testSerializeEmptyListUsesObjectWhenEmptyMapsFlagIsSet(): void {
		$serializer = new SiteLinkListSerializer( $this->createSiteLinkSerializer(), true );
		$this->assertEquals(
			(object)[],
			$serializer->serialize( new SiteLinkList() )
		);
	}
}
