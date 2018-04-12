<?php

namespace Wikibase\DataAccess\Tests;

use PHPUnit4And6Compat;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\PerRepositoryServiceContainer;
use Wikibase\DataAccess\PerRepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;

/**
 * @covers Wikibase\DataAccess\PerRepositoryServiceContainerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PerRepositoryServiceContainerFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getRepositoryServiceContainerFactory() {
		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			new ItemIdParser(), []
		);

		$entityTypeDefinitions = new EntityTypeDefinitions( [] );

		return new PerRepositoryServiceContainerFactory(
			$idParserFactory,
			new EntityIdComposer( [] ),
			new RepositorySpecificDataValueDeserializerFactory( $idParserFactory ),
			[ '' => false ],
			[],
			new GenericServices( $entityTypeDefinitions, [] ),
			new DataAccessSettings( 0, true, false ),
			$entityTypeDefinitions
		);
	}

	public function testGivenKnownRepository_newContainerReturnsContainerForThisRepository() {
		$factory = $this->getRepositoryServiceContainerFactory();

		$container = $factory->newContainer( '' );

		$this->assertInstanceOf( PerRepositoryServiceContainer::class, $container );
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
