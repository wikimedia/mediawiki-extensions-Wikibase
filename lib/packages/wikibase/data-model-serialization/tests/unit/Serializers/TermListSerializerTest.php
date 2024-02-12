<?php

declare( strict_types = 1 );

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

	private function buildSerializer( bool $useObjectsForEmptyMaps ): TermListSerializer {
		return new TermListSerializer( new TermSerializer(), $useObjectsForEmptyMaps );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( TermList $input, $expected ): void {
		$serializer = $this->buildSerializer( false );

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public static function serializationProvider(): array {
		return [
			[
				new TermList( [] ),
				[],
			],
			[
				new TermList( [
					new Term( 'en', 'Water' ),
					new Term( 'it', 'Lama' ),
					new TermFallback( 'pt', 'Lama', 'de', 'zh' ),
				] ),
				[
					'en' => [ 'language' => 'en', 'value' => 'Water' ],
					'it' => [ 'language' => 'it', 'value' => 'Lama' ],
					'pt' => [ 'language' => 'de', 'value' => 'Lama', 'source' => 'zh' ],
				],
			],
		];
	}

	public function testWithUnsupportedObject(): void {
		$serializer = $this->buildSerializer( false );

		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( new \stdClass() );
	}

	public function testTermListSerializerSerializesTermLists(): void {
		$serializer = $this->buildSerializer( false );

		$terms = new TermList( [ new Term( 'en', 'foo' ) ] );

		$serial = [];
		$serial['en'] = [
			'language' => 'en',
			'value' => 'foo',
		];

		$this->assertEquals( $serial, $serializer->serialize( $terms ) );
		$this->assertEquals( [], $serializer->serialize( new TermList() ) );
	}

	public function testTermListSerializerUsesObjectsForEmptyLists(): void {
		$serializer = $this->buildSerializer( true );
		$this->assertEquals( (object)[], $serializer->serialize( new TermList() ) );
	}
}
