<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\SiteLinkListSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$siteLink1 = new SiteLink(
			'dewiki',
			'Kartoffel',
			[],
			'https://de.wikipedia.org/wiki/Kartoffel'
		);
		$siteLink2 = new SiteLink(
			'enwiki',
			'potato',
			[ new ItemId( 'Q42' ) ],
			'https://en.wikipedia.org/wiki/Potato'
		);

		$serializer = new SiteLinkListSerializer();

		$this->assertEquals(
			new ArrayObject( [
				'dewiki' => [ 'title' => 'Kartoffel', 'badges' => [] ],
				'enwiki' => [ 'title' => 'potato', 'badges' => [ 'Q42' ] ],
			] ),
			$serializer->serialize( new SiteLinks( $siteLink1, $siteLink2 ) )
		);
	}

	public function testSerializeEmptyList(): void {
		$serializer = new SiteLinkListSerializer();
		$this->assertEquals(
			new ArrayObject(),
			$serializer->serialize( new SiteLinks() )
		);
	}

}
