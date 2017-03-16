<?php

namespace Wikibase\Client\Tests\Store;

use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Edrsf\RepositoryServiceContainer;
use Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory;

/**
 * @covers Wikibase\Client\Store\RepositoryServiceContainerFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class RepositoryServiceContainerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function getRepositoryServiceContainerFactory() {
		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			new ItemIdParser(), []
		);

		return new \Wikibase\Edrsf\RepositoryServiceContainerFactory(
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
