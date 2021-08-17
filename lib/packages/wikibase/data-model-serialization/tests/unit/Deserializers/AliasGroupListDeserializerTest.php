<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\AliasGroupListDeserializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Deserializers\AliasGroupListDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListDeserializerTest extends TestCase {

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = new AliasGroupListDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return [
			'must be an array' => [ new \stdClass() ],
			'must be an array of arrays' => [ [
				'en' => new \stdClass(),
			] ],
			'array key must match' => [ [
				'en' => [ [ 'language' => 'de', 'value' => 'Evil language' ] ],
			] ],
			'must be an array of arrays of arrays' => [ [
				'en' => [ 'A' ],
				'de' => [ 'B' ],
			] ],
			'must contain language' => [ [
				'en' => [ [ 'value' => 'foo' ] ],
			] ],
			'must contain value' => [ [
				'en' => [ [ 'language' => 'en' ] ],
			] ],
			'must not contain source' => [ [
				'en' => [
					[ 'language' => 'en', 'value' => 'Evil language', 'source' => 'fallback' ],
				],
			] ],
		];
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$deserializer = new AliasGroupListDeserializer();
		$this->assertEquals( $object, $deserializer->deserialize( $serialization ) );
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	public function deserializationProvider() {
		return [
			[
				new AliasGroupList( [ new AliasGroup( 'en', [] ) ] ),
				[ 'en' => [] ],
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [ 'A' ] ) ] ),
				[ 'en' => [
					[ 'language' => 'en', 'value' => 'A' ],
				] ],
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [ 'A', 'B' ] ) ] ),
				[ 'en' => [
					[ 'language' => 'en', 'value' => 'A' ],
					[ 'language' => 'en', 'value' => 'B' ],
				] ],
			],
			'Matching numeric keys' => [
				new AliasGroupList( [ new AliasGroup( '8', [ 'Eight' ] ) ] ),
				[ '8' => [ [ 'language' => '8', 'value' => 'Eight' ] ] ],
			],
		];
	}

}
