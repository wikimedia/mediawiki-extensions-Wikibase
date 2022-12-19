<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\SiteLinkListSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$siteLink1 = new SiteLink( 'foo', 'bar' );
		$siteLink2 = new SiteLink( 'omg', 'bbq', [ new ItemId( 'Q42' ) ] );

		$serializer = new SiteLinkListSerializer();

		$this->assertEquals(
			new ArrayObject( [
				'foo' => [ 'title' => 'bar', 'badges' => [] ],
				'omg' => [ 'title' => 'bbq', 'badges' => [ 'Q42' ] ],
			] ),
			$serializer->serialize( new SiteLinkList( [ $siteLink1, $siteLink2 ] ) )
		);
	}

	public function testSerializeEmptyList(): void {
		$serializer = new SiteLinkListSerializer();
		$this->assertEquals(
			new ArrayObject(),
			$serializer->serialize( new SiteLinkList() )
		);
	}

}
