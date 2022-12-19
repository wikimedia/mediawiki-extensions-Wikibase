<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\SiteLink;

/**
 * @covers DataValues\Deserializers\DataValueDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SiteLinkSerializationRoundtripTest extends TestCase {

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
		return [
			[
				new SiteLink( 'enwiki', 'Nyan Cat' ),
			],
			[
				new SiteLink( 'enwiki', 'Nyan Cat', [
					new ItemId( 'Q42' ),
				] ),
			],
			[
				new SiteLink( 'frwikisource', 'Nyan Cat', [
					new ItemId( 'Q42' ),
					new ItemId( 'q43' ),
				] ),
			],
		];
	}

}
