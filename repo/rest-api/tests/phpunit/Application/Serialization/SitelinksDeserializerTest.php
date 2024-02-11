<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinksDeserializerTest extends TestCase {

	public function testDeserialize(): void {
		$siteId = 'testWiki1';
		$anotherSiteId = 'testWiki2';

		$serialization = [
			'title' => 'Test Title',
			'badges' => [ 'Q123' ],
		];

		$this->assertEquals(
			new SiteLinkList( [
				new SiteLink( 'testWiki1', 'Test Title', [ new ItemId( 'Q123' ) ] ),
				new SiteLink( 'testWiki2', 'Test Title', [ new ItemId( 'Q123' ) ] ),
			] ),
			( new SitelinksDeserializer( new SitelinkDeserializer() ) )->deserialize( [
				$siteId => $serialization,
				$anotherSiteId => $serialization,
			] )
		);
	}

}
