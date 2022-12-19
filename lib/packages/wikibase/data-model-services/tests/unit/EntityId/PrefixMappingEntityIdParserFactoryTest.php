<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikimedia\Assert\ParameterAssertionException;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory
 *
 * @license GPL-2.0-or-later
 */
class PrefixMappingEntityIdParserFactoryTest extends TestCase {

	public function testGetIdParser_repositoryWithKnownMapping() {
		$dummyParser = new ItemIdParser();
		$idPrefixMapping = [
			'foo' => [ 'd' => 'de', 'e' => 'en' ],
		];
		$factory = new PrefixMappingEntityIdParserFactory( $dummyParser, $idPrefixMapping );
		$this->assertEquals(
			new PrefixMappingEntityIdParser( [ ''  => 'foo', 'd' => 'de', 'e' => 'en' ], $dummyParser ),
			$factory->getIdParser( 'foo' )
		);
	}

	public function testGetIdParser_repositoryWithoutKnownMapping() {
		$dummyParser = new ItemIdParser();
		$idPrefixMapping = [
			'foo' => [ 'd' => 'de', 'e' => 'en' ],
		];
		$factory = new PrefixMappingEntityIdParserFactory( $dummyParser, $idPrefixMapping );
		$this->assertEquals(
			new PrefixMappingEntityIdParser( [ '' => 'bar' ], $dummyParser ),
			$factory->getIdParser( 'bar' )
		);
	}

	public function testGetIdParser_noIdPrefixMappings() {
		$dummyParser = new ItemIdParser();
		$factory = new PrefixMappingEntityIdParserFactory( $dummyParser, [] );
		$this->assertEquals(
			new PrefixMappingEntityIdParser( [ '' => 'foo' ], $dummyParser ),
			$factory->getIdParser( 'foo' )
		);
	}

	public function testGivenNonStringRepository_exceptionIsThrown() {
		$factory = new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] );
		$this->expectException( ParameterTypeException::class );
		$factory->getIdParser( 111 );
	}

	public function testGivenRepositoryIncludingColon_exceptionIsThrown() {
		$factory = new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] );
		$this->expectException( ParameterAssertionException::class );
		$factory->getIdParser( 'en:' );
	}

	/**
	 * @dataProvider provideInvalidIdPrefixMapping
	 */
	public function testGivenInvalidIdPrefixMapping_exceptionIsThrown( array $mapping ) {
		$this->expectException( ParameterAssertionException::class );
		new PrefixMappingEntityIdParserFactory( new ItemIdParser(), $mapping );
	}

	public function provideInvalidIdPrefixMapping() {
		return [
			'id prefix mapping values are not arrays' => [ [ 'foo' => 'bar' ] ],
			'non-string keys in id prefix mapping' => [ [ 0 => [ 'd' => 'wd' ] ] ],
			'non-string values in inner mapping' => [ [ 'foo' => [ 'd' => 123 ] ] ],
			'non-string keys in inner mapping' => [ [ 'foo' => [ 0 => 'wd' ] ] ],
			'keys containing colons in id prefix mapping' => [ [ 'fo:o' => [ 'd' => 'wd' ] ] ],
			'default prefix mapping differs from repository name' => [ [ 'foo' => [ '' => 'bar' ] ] ],
		];
	}

	public function testGetIdParserReusesTheInstanceOverMultitpleCalls() {
		$factory = new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] );

		$parserOne = $factory->getIdParser( 'foo' );
		$parserTwo = $factory->getIdParser( 'foo' );

		$this->assertSame( $parserOne, $parserTwo );
	}

}
