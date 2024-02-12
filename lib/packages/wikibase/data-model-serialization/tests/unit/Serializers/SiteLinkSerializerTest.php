<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Serializers\SiteLinkSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SiteLinkSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer(): DispatchableSerializer {
		return new SiteLinkSerializer();
	}

	public function serializableProvider(): array {
		return [
			[
				new SiteLink( 'enwiki', 'Nyan Cat' ),
			],
			[
				new SiteLink( 'enwiki', 'Nyan Cat', [
					new ItemId( 'Q42' ),
				] ),
			],
		];
	}

	public function nonSerializableProvider(): array {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				new ItemId( 'Q42' ),
			],
		];
	}

	public function serializationProvider(): array {
		return [
			[
				[
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => [],
				],
				new SiteLink( 'enwiki', 'Nyan Cat' ),
			],
			[
				[
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => [ 'Q42' ],
				],
				new SiteLink( 'enwiki', 'Nyan Cat', [
					new ItemId( 'Q42' ),
				] ),
			],
			[
				[
					'site' => 'frwikisource',
					'title' => 'Nyan Cat',
					'badges' => [ 'Q42', 'Q43' ],
				],
				new SiteLink( 'frwikisource', 'Nyan Cat', [
					new ItemId( 'Q42' ),
					new ItemId( 'q43' ),
				] ),
			],
		];
	}

}
