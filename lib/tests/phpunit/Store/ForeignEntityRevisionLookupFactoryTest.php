<?php

namespace Wikibase\Lib\Tests\Store;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\ForeignEntityRevisionLookupFactory;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;

/**
 * @covers Wikibase\Lib\Store\ForeignEntityRevisionLookupFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 */
class ForeignEntityRevisionLookupFactoryTest extends \PHPUnit_Framework_TestCase {

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
	 * @return DataValueDeserializer
	 */
	private function getDataValueDeserializer() {
		return $this->getMock( DataValueDeserializer::class );
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		return new EntityNamespaceLookup( [ 'item' => 100 ] );
	}

	public function testGivenKnownRepository_getLookupReturnsInstanceOfWikiPageEntityRevisionLookup() {
		$factory = new ForeignEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializer(),
			$this->getEntityNamespaceLookup(),
			0,
			[ 'foo' => 'foodb' ]
		);

		$this->assertInstanceOf( WikiPageEntityRevisionLookup::class, $factory->getLookup( 'foo' ) );
	}

	public function testGivenUnknownRepository_getLookupThrowsException() {
		$factory = new ForeignEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			$this->getEntitySerializer(),
			$this->getDataValueDeserializer(),
			$this->getEntityNamespaceLookup(),
			0,
			[ 'foo' => 'foodb' ]
		);

		$this->setExpectedException( UnknownForeignRepositoryException::class );

		$this->assertInstanceOf( WikiPageEntityRevisionLookup::class, $factory->getLookup( 'bar' ) );
	}

}
