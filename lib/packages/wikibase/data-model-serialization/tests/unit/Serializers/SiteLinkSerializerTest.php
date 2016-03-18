<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Serializers\SiteLinkSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SiteLinkSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		return new SiteLinkSerializer();
	}

	public function serializableProvider() {
		return array(
			array(
				new SiteLink( 'enwiki', 'Nyan Cat' )
			),
			array(
				new SiteLink( 'enwiki', 'Nyan Cat', array(
					new ItemId( 'Q42' )
				) )
			),
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				new ItemId( 'Q42' )
			),
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array()
				),
				new SiteLink( 'enwiki', 'Nyan Cat' )
			),
			array(
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42' )
				),
				new SiteLink( 'enwiki', 'Nyan Cat', array(
					new ItemId( 'Q42' )
				) )
			),
			array(
				array(
					'site' => 'frwikisource',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42', 'Q43' )
				),
				new SiteLink( 'frwikisource', 'Nyan Cat', array(
					new ItemId( 'Q42' ),
					new ItemId( 'q43' )
				) )
			),
		);
	}

}
