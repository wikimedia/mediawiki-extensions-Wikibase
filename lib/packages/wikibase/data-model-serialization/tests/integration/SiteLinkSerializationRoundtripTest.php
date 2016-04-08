<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class SiteLinkSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider siteLinkProvider
	 */
	public function testSiteLinkSerializationRoundtrips( SiteLink $siteLink ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newSiteLinkSerializer()->serialize( $siteLink );
		$newSiteLink = $deserializerFactory->newSiteLinkDeserializer()->deserialize( $serialization );
		$this->assertEquals( $siteLink, $newSiteLink );
	}

	public function siteLinkProvider() {
		return array(
			array(
				new SiteLink( 'enwiki', 'Nyan Cat' )
			),
			array(
				new SiteLink( 'enwiki', 'Nyan Cat', array(
					new ItemId( 'Q42' )
				) )
			),
			array(
				new SiteLink( 'frwikisource', 'Nyan Cat', array(
					new ItemId( 'Q42' ),
					new ItemId( 'q43' )
				) )
			)
		);
	}

}
