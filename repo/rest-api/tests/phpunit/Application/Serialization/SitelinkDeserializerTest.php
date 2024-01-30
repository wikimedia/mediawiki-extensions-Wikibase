<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializerTest extends TestCase {

	public function testDeserialize(): void {
		$siteId = 'testwiki';
		$serialization = [
			'title' => 'Test Title',
			'badges' => [ 'Q123' ],
		];

		$this->assertEquals(
			new SiteLink( 'testwiki', 'Test Title', [ new ItemId( 'Q123' ) ] ),
			( new SitelinkDeserializer() )->deserialize( $siteId, $serialization )
		);
	}

}
