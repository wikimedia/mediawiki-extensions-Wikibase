<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelink;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkSerializerTest extends TestCase {

	public function testSerialize(): void {
		$this->assertEquals(
			[
				'title' => 'Kartoffel',
				'badges' => [],
				'url' => 'https://de.wikipedia.org/wiki/Kartoffel',
			],
			( new SitelinkSerializer() )->serialize(
				new Sitelink(
					'dewiki',
					'Kartoffel',
					[],
					'https://de.wikipedia.org/wiki/Kartoffel'
				)
			)
		);
	}

}
