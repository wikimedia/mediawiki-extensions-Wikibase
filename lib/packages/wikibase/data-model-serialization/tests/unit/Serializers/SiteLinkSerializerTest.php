<?php

namespace Tests\Wikibase\DataModel\Serializers;

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

	protected function buildSerializer() {
		return new SiteLinkSerializer();
	}

	public function serializableProvider() {
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

	public function nonSerializableProvider() {
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

	public function serializationProvider() {
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
