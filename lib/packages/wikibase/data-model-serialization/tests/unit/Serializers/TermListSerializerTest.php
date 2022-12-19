<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\TermListSerializer
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermListSerializerTest extends TestCase {

	/**
	 * @param bool $useObjectsForMaps
	 *
	 * @return TermListSerializer
	 */
	private function buildSerializer( $useObjectsForMaps = false ) {
		return new TermListSerializer( new TermSerializer(), $useObjectsForMaps );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( TermList $input, $useObjectsForMaps, $expected ) {
		$serializer = $this->buildSerializer( $useObjectsForMaps );

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return [
			[
				new TermList( [] ),
				false,
				[],
			],
			[
				new TermList( [] ),
				true,
				new \stdClass(),
			],
			[
				new TermList( [
					new Term( 'en', 'Water' ),
					new Term( 'it', 'Lama' ),
					new TermFallback( 'pt', 'Lama', 'de', 'zh' ),
				] ),
				false,
				[
					'en' => [ 'language' => 'en', 'value' => 'Water' ],
					'it' => [ 'language' => 'it', 'value' => 'Lama' ],
					'pt' => [ 'language' => 'de', 'value' => 'Lama', 'source' => 'zh' ],
				],
			],
		];
	}

	public function testWithUnsupportedObject() {
		$serializer = $this->buildSerializer();

		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( new \stdClass() );
	}

	public function testTermListSerializerWithOptionObjectsForMaps() {
		$serializer = $this->buildSerializer( true );

		$terms = new TermList( [ new Term( 'en', 'foo' ) ] );

		$serial = new \stdClass();
		$serial->en = [
			'language' => 'en',
			'value' => 'foo',
		];

		$this->assertEquals( $serial, $serializer->serialize( $terms ) );
	}

}
