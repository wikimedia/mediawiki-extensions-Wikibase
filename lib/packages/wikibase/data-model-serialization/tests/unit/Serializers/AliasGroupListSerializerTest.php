<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use stdClass;
use Wikibase\DataModel\Serializers\AliasGroupListSerializer;
use Wikibase\DataModel\Serializers\AliasGroupSerializer;
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

	private function buildSerializer( bool $useObjectsForEmptyMaps ): AliasGroupListSerializer {
		$aliasGroupSerializer = $this->createMock( AliasGroupSerializer::class );
		$aliasGroupSerializer->expects( $this->any() )
			->method( 'serialize' )
			->willReturnCallback( static function ( AliasGroup $aliasGroup ) {
				return $aliasGroup->getAliases();
			} );

		return new AliasGroupListSerializer( $aliasGroupSerializer, $useObjectsForEmptyMaps );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( AliasGroupList $input, $expected ) {
		$serializer = $this->buildSerializer( false );

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
		$serializer = $this->buildSerializer( false );

		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( new stdClass() );
	}

	public function testAliasGroupListSerializerReturnsObjectWhenUseObjectsForEmptyMapsSet() {
		$serializer = $this->buildSerializer( true );
		$this->assertEquals( (object)[], $serializer->serialize( new AliasGroupList() ) );
	}
}
