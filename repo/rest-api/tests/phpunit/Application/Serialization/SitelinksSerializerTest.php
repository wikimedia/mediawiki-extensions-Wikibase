<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinksSerializerTest extends TestCase {

	public function testSerialize(): void {
		$deSitelink = new Sitelink(
			'dewiki',
			'Kartoffel',
			[],
			'https://de.wikipedia.org/wiki/Kartoffel'
		);
		$enSitelink = new Sitelink(
			'enwiki',
			'potato',
			[ new ItemId( 'Q42' ) ],
			'https://en.wikipedia.org/wiki/Potato'
		);

		$serializer = new SitelinksSerializer( new SitelinkSerializer() );

		$this->assertEquals(
			new ArrayObject( [
				'dewiki' => [
					'title' => 'Kartoffel',
					'badges' => [],
					'url' => 'https://de.wikipedia.org/wiki/Kartoffel',
				],
				'enwiki' => [
					'title' => 'potato',
					'badges' => [ 'Q42' ],
					'url' => 'https://en.wikipedia.org/wiki/Potato',
				],
			] ),
			$serializer->serialize( new Sitelinks( $deSitelink, $enSitelink ) )
		);
	}

	public function testSerializeEmptyList(): void {
		$serializer = new SitelinksSerializer( new SitelinkSerializer() );
		$this->assertEquals(
			new ArrayObject(),
			$serializer->serialize( new Sitelinks() )
		);
	}

}
