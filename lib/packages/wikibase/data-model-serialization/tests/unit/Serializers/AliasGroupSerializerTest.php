<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Serializers\AliasGroupSerializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Serializers\AliasGroupSerializer
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupSerializerTest extends TestCase {

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$serializer = new AliasGroupSerializer();

		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( $nonSerializable );
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
				new AliasGroupList(),
			],
		];
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $serialization, $object ) {
		$serializer = new AliasGroupSerializer();
		$this->assertSame( $serialization, $serializer->serialize( $object ) );
	}

	public function serializationProvider() {
		return [
			[
				[],
				new AliasGroup( 'en', [] ),
			],
			[
				[
					[ 'language' => 'en', 'value' => 'One' ],
				],
				new AliasGroup( 'en', [ 'One' ] ),
			],
			[
				[
					[ 'language' => 'en', 'value' => 'One' ],
					[ 'language' => 'en', 'value' => 'Pony' ],
				],
				new AliasGroup( 'en', [ 'One', 'Pony' ] ),
			],
			[
				[
					[ 'language' => 'de', 'value' => 'One', 'source' => 'fr' ],
					[ 'language' => 'de', 'value' => 'Pony', 'source' => 'fr' ],
				],
				new AliasGroupFallback( 'en', [ 'One', 'Pony' ], 'de', 'fr' ),
			],
		];
	}

}
