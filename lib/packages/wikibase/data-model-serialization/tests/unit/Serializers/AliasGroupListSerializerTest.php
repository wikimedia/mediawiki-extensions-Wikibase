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

	/**
	 * @param bool $useObjectsForMaps
	 *
	 * @return AliasGroupListSerializer
	 */
	private function buildSerializer( $useObjectsForMaps = false ) {
		$aliasGroupSerializer = $this->createMock( Serializer::class );
		$aliasGroupSerializer->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( static function( AliasGroup $aliasGroup ) {
				return $aliasGroup->getAliases();
			} ) );

		return new AliasGroupListSerializer( $aliasGroupSerializer, $useObjectsForMaps );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( AliasGroupList $input, $useObjectsForMaps, $expected ) {
		$serializer = $this->buildSerializer( $useObjectsForMaps );

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return [
			[
				new AliasGroupList( [ new AliasGroup( 'en', [] ) ] ),
				false,
				[],
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [] ) ] ),
				true,
				new \stdClass(),
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [ 'One' ] ) ] ),
				false,
				[ 'en' => [ 'One' ] ],
			],
			[
				new AliasGroupList( [ new AliasGroup( 'en', [ 'One', 'Pony' ] ) ] ),
				false,
				[ 'en' => [ 'One', 'Pony' ] ],
			],
			[
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'One', 'Pony' ] ),
					new AliasGroup( 'de', [ 'foo', 'bar' ] ),
				] ),
				false,
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

	public function testAliasGroupListSerializerWithOptionObjectsForMaps() {
		$serializer = $this->buildSerializer( true );

		$aliases = new AliasGroupList( [ new AliasGroup( 'en', [ 'foo', 'bar' ] ) ] );

		$serial = new \stdClass();
		$serial->en = [ 'foo', 'bar' ];

		$this->assertEquals( $serial, $serializer->serialize( $aliases ) );
	}

}
