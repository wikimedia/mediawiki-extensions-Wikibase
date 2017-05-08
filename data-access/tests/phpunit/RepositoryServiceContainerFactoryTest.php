<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\RepositoryServiceContainer;
use Wikibase\DataAccess\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;

/**
 * @covers Wikibase\DataAccess\RepositoryServiceContainerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class RepositoryServiceContainerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function getRepositoryServiceContainerFactory() {
		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			new ItemIdParser(), []
		);

		return new RepositoryServiceContainerFactory(
			$idParserFactory,
			new RepositorySpecificDataValueDeserializerFactory( $idParserFactory ),
			[ '' => false ],
			[],
			WikibaseClient::getDefaultInstance()
		);
	}

	public function testGivenKnownRepository_newContainerReturnsContainerForThisRepository() {
		$factory = $this->getRepositoryServiceContainerFactory();

		$container = $factory->newContainer( '' );

		$this->assertInstanceOf( RepositoryServiceContainer::class, $container );
		$this->assertSame( '', $container->getRepositoryName() );
	}

	public function testGivenUnknownRepository_newContainerThrowsException() {
		$factory = $this->getRepositoryServiceContainerFactory();

		$this->setExpectedException( UnknownForeignRepositoryException::class );

		$factory->newContainer( 'foo' );
	}

	public function testNewContainerReturnsAFreshInstanceOnEachCall() {
		$factory = $this->getRepositoryServiceContainerFactory();

		$containerOne = $factory->newContainer( '' );
		$containerTwo = $factory->newContainer( '' );

		$this->assertNotSame( $containerOne, $containerTwo );
	}

}
