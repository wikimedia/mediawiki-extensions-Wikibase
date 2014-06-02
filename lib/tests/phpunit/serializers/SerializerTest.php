<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\ByPropertyListSerializer;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * Tests for the Wikibase\Serializer implementing classes.
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerTest extends \MediaWikiTestCase {

	public function apiSerializerProvider() {
		$serializers = array();

		$serializers[] = new SnakSerializer();
		$serializers[] = new ClaimSerializer( new SnakSerializer() );

		$snakSetailizer = new SnakSerializer();
		$serializers[] = new ByPropertyListSerializer( 'test', $snakSetailizer );

		return $this->arrayWrap( $serializers );
	}

	/**
	 * @dataProvider apiSerializerProvider
	 * @param Serializer $serializer
	 */
	public function testSetOptions( Serializer $serializer ) {
		$serializer->setOptions( new SerializationOptions() );
		$this->assertTrue( true );
	}

}
