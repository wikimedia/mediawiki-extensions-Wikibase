<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Serialization\SiteLinkSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SiteLinkSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkSerializerTest extends TestCase {

	public function testSerialize(): void {
		$this->assertEquals(
			[
				'title' => 'Kartoffel',
				'badges' => [],
				'url' => 'https://de.wikipedia.org/wiki/Kartoffel',
			],
			( new SiteLinkSerializer() )->serialize(
				new SiteLink(
					'dewiki',
					'Kartoffel',
					[],
					'https://de.wikipedia.org/wiki/Kartoffel'
				)
			)
		);
	}

}
