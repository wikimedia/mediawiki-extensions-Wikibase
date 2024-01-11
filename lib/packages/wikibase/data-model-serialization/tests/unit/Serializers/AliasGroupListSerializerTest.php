<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use stdClass;
use Wikibase\DataModel\Serializers\AliasGroupListSerializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Serializers\AliasGroupListSerializer
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListSerializerTest extends TestCase {

	private function buildSerializer() {
		$aliasGroupSerializer = $this->createMock( Serializer::class );
		$aliasGroupSerializer->expects( $this->any() )
			->method( 'serialize' )
			->willReturnCallback( static function ( AliasGroup $aliasGroup ) {
				return $aliasGroup->getAliases();
			} );

		return new AliasGroupListSerializer( $aliasGroupSerializer );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( AliasGroupList $input, $expected ) {
		$serializer = $this->buildSerializer();

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public static function serializationProvider() {
		return [
			[
				new AliasGroupList( [ new AliasGroup( 'en', [] ) ] ),
				[],
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [ 'One' ] ) ] ),
				[ 'en' => [ 'One' ] ],
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [ 'One', 'Pony' ] ) ] ),
				[ 'en' => [ 'One', 'Pony' ] ],
			],
			[
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'One', 'Pony' ] ),
					new AliasGroup( 'de', [ 'foo', 'bar' ] ),
				] ),
				[
					'en' => [ 'One', 'Pony' ],
					'de' => [ 'foo', 'bar' ],
				],
			],
		];
	}

	public function testWithUnsupportedObject() {
		$serializer = $this->buildSerializer();

		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( new stdClass() );
	}

	public function testAliasGroupListSerializerReturnsArrayByDefault() {
		$serializer = $this->buildSerializer( false );

		$this->assertEquals( [], $serializer->serialize( new AliasGroupList() ) );
	}
}
