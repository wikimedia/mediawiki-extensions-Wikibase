<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\SiteLinkDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Deserializers\SiteLinkDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SiteLinkDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		return new SiteLinkDeserializer( new BasicEntityIdParser() );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'site' => 'test',
					'title' => 'Nyan Cat'
				)
			),
			array(
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array()
				)
			),
			array(
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42' )
				)
			),
			array(
				array(
					'site' => 'frwikisource',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42', 'Q43' )
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
			),
			array(
				array()
			),
			array(
				array(
					'id' => 'P10'
				)
			),
			array(
				array(
					'site' => '42value'
				)
			),
			array(
				array(
					'title' => '42value'
				)
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new SiteLink( 'enwiki', 'Nyan Cat' ),
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array()
				)
			),
			array(
				new SiteLink( 'enwiki', 'Nyan Cat', array(
					new ItemId( 'Q42' )
				) ),
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42' )
				)
			),
			array(
				new SiteLink( 'frwikisource', 'Nyan Cat', array(
					new ItemId( 'Q42' ),
					new ItemId( 'q43' )
				) ),
				array(
					'site' => 'frwikisource',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42', 'Q43' )
				)
			),
		);
	}

	public function testParseItemIdCatchesEntityIdParsingException() {
		$this->setExpectedException( '\Deserializers\Exceptions\InvalidAttributeException' );
		$this->buildDeserializer()->deserialize( array(
			'site' => 'frwikisource',
			'title' => 'Nyan Cat',
			'badges' => array( 'd43' )
		) );
	}
}