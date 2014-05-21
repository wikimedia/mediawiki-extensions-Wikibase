<?php

namespace Wikibase\Lib\Test\Serializers;

use Tests\Wikibase\DataModel\SnaksSerializationRoundtripTest;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ByPropertyListUnserializerTest extends \MediaWikiTestCase {

	public function test() {
		$mainSnak = new PropertyNoValueSnak( new PropertyId( 'P1' ) );
		$qualifiers = new SnakList();
		$claim = new Claim( $mainSnak, $qualifiers );

		$snakSerializer = new \Wikibase\Lib\Serializers\SnakSerializer();
		$claimSerializer = new \Wikibase\Lib\Serializers\ClaimSerializer( $snakSerializer );
		$serialization = $claimSerializer->getSerialized( $claim );

		$newClaimDeserializer = new \Wikibase\DataModel\Deserializers\ClaimDeserializer();

		$this->assertTrue( true );
	}

}
