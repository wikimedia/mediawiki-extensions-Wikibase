<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\RepositoryServiceContainer;
use Wikibase\DataAccess\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
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

		$client = WikibaseClient::getDefaultInstance();

		return new RepositoryServiceContainerFactory(
			$idParserFactory,
			new EntityIdComposer( [] ),
			new RepositorySpecificDataValueDeserializerFactory( $idParserFactory ),
			[ '' => false ],
			[],
			new GenericServices( $client->getEntityNamespaceLookup(), new EntityTypeDefinitions( [] ) ),
			new DataAccessSettings( 0, false ),
			$client
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
