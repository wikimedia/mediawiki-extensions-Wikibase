<?php

namespace Wikibase\Lib\Tests\Store;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 */
class RepositorySpecificEntityRevisionLookupFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityIdParser
	 */
	private function getEntityIdParser() {
		return $this->getMock( EntityIdParser::class );
	}

	/**
	 * @return PrefixMappingEntityIdParserFactory
	 */
	private function getPrefixMappingEntityIdParserFactory() {
		$factory = $this->getMockBuilder( PrefixMappingEntityIdParserFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$factory->expects( $this->any() )
			->method( 'getIdParser' )
			->will( $this->returnValue( $this->getEntityIdParser() ) );
		return $factory;
	}

	/**
	 * @return Serializer
	 */
	private function getEntitySerializer() {
		return $this->getMock( Serializer::class );
	}

	/**
	 * @return RepositorySpecificDataValueDeserializerFactory
	 */
	private function getDataValueDeserializerFactory() {
		$factory = $this->getMockBuilder( RepositorySpecificDataValueDeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$factory->expects( $this->any() )
			->method( 'getDeserializer' )
			->willReturn( $this->getMock( DataValueDeserializer::class ) );
		return $factory;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		return new EntityNamespaceLookup( [ 'item' => 100 ] );
	}

	public function testGivenLocalRepositoryAndDatabase_getLookupReturnsInstanceOfWikiPageEntityRevisionLookup() {
		$factory = new RepositorySpecificEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializerFactory(),
			$this->getEntityNamespaceLookup(),
			0,
			[ '' => '' ]
		);

		$this->assertInstanceOf( WikiPageEntityRevisionLookup::class, $factory->getLookup( '' ) );
	}

	public function testGivenKnownRepository_getLookupReturnsInstanceOfWikiPageEntityRevisionLookup() {
		$factory = new RepositorySpecificEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializerFactory(),
			$this->getEntityNamespaceLookup(),
			0,
			[ 'foo' => 'foodb' ]
		);

		$this->assertInstanceOf( WikiPageEntityRevisionLookup::class, $factory->getLookup( 'foo' ) );
	}

	public function testGivenUnknownRepository_getLookupThrowsException() {
		$factory = new RepositorySpecificEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializerFactory(),
			$this->getEntityNamespaceLookup(),
			0,
			[ 'foo' => 'foodb' ]
		);

		$this->setExpectedException( UnknownForeignRepositoryException::class );

		$this->assertInstanceOf( WikiPageEntityRevisionLookup::class, $factory->getLookup( 'bar' ) );
	}

	public function testGetLookupReusesTheInstanceOverMultipleCalls() {
		$factory = new RepositorySpecificEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializerFactory(),
			$this->getEntityNamespaceLookup(),
			0,
			[ 'foo' => 'foodb' ]
		);

		$lookupOne = $factory->getLookup( 'foo' );
		$lookupTwo = $factory->getLookup( 'foo' );

		$this->assertSame( $lookupOne, $lookupTwo );
	}

	public function provideInvalidDatabaseNamesValue() {
		return [
			'empty list' => [ [] ],
			'repository name containing a colon' => [ [ 'fo:o' => 'foodb' ] ],
			'non-string key' => [ [ 0 => 'foodb' ] ],
			'not a string as a database name (true)' => [ [ 'foo' => true ] ],
			'not a string as a database name (null)' => [ [ 'foo' => null ] ],
			'not a string as a database name (int)' => [ [ 'foo' => 100 ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidDatabaseNamesValue
	 */
	public function testGivenInvalidDatabaseNamesValue_exceptionIsThrown( array $databaseNames ) {
		$this->setExpectedException( ParameterAssertionException::class );

		new RepositorySpecificEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializerFactory(),
			$this->getEntityNamespaceLookup(),
			0,
			$databaseNames
		);
	}

}
