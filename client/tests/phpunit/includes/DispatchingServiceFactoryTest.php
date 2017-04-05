<?php

namespace Wikibase\Client\Tests;

use Prophecy\Argument;
use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;
use Wikibase\Lib\RepositoryDefinitions;

/**
 * @covers Wikibase\Client\DispatchingServiceFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactoryTest extends \PHPUnit_Framework_TestCase {

	private function getRepositoryDefinition( $repositoryName, array $customSettings ) {
		return [ $repositoryName => array_merge(
			[
				'database' => '',
				'base-concept-uri' => 'http://acme.test/concept/',
				'base-data-uri' => 'http://acme.test/data/',
				'entity-types' => [],
				'prefix-mapping' => []
			],
			$customSettings
		) ];
	}

	public function testGetServiceNames_ReturnsNameOfDefinedService() {
		$factory = new DispatchingServiceFactory(
			$this->dummy( RepositoryServiceContainerFactory::class ),
			new RepositoryDefinitions( $this->getRepositoryDefinition( '', [] ) )
		);

		$factory->defineService(
			'SomeService',
			function () {
				return $this->someService( 'does not matter' );
			}
		);

		$this->assertContains( 'SomeService', $factory->getServiceNames() );
	}

	private function newDispatchingServiceFactory( RepositoryServiceContainerFactory $containerFactory ) {
		return new DispatchingServiceFactory(
			$containerFactory,
			new RepositoryDefinitions( array_merge(
				$this->getRepositoryDefinition( '', [ 'entity-types' => [ Item::ENTITY_TYPE ] ] ),
				$this->getRepositoryDefinition( 'foo', [ 'entity-types' => [ Property::ENTITY_TYPE ] ] )
			) )
		);
	}

	public function testGetServiceMap_ReturnsArrayMappingNameOfRepositoryToServiceForThatRepository(
	) {
		$someServiceName = 'some-service';
		$localService = $this->someService( 'local' );
		$fooService = $this->someService( 'foo' );

		$localContainer = $this->prophesize( RepositoryServiceContainer::class );
		$localContainer->getService( $someServiceName )->willReturn( $localService );

		$fooContainer = $this->prophesize( RepositoryServiceContainer::class );
		$fooContainer->getService( $someServiceName )->willReturn( $fooService );

		$rscFactory = $this->prophesize( RepositoryServiceContainerFactory::class );
		$rscFactory->newContainer( '' )->willReturn( $localContainer );
		$rscFactory->newContainer( 'foo' )->willReturn( $fooContainer );
		$dispatchingFactory = $this->newDispatchingServiceFactory( $rscFactory->reveal() );

		$serviceMap = $dispatchingFactory->getServiceMap( $someServiceName );

		$expectedServiceMap = [
			'' => $localService,
			'foo' => $fooService,
		];
		$this->assertEquals( $expectedServiceMap, $serviceMap );
	}

	public function testGetService_AlwaysReturnsTheSameService() {
		$factory = new DispatchingServiceFactory(
			$this->dummy( RepositoryServiceContainerFactory::class ),
			new RepositoryDefinitions( $this->getRepositoryDefinition( '', [] ) )
		);

		$someService = $this->someService( 'some service instance' );
		$factory->defineService(
			'some-service',
			function () use ( $someService ) {
				return $someService;
			}
		);

		$serviceOne = $factory->getService( 'some-service' );
		$serviceTwo = $factory->getService( 'some-service' );

		$this->assertSame( $someService, $serviceOne );
		$this->assertSame( $someService, $serviceTwo );
	}

	public function testEntityUpdatedDelegatesEventToContainerOfRelevantRepository() {
		$localContainer = $this->prophesize( RepositoryServiceContainer::class );
		$fooContainer = $this->prophesize( RepositoryServiceContainer::class );
		$factory = $this->newDispatchingServiceFactory(
			$this->createRepositoryServiceContainerFactory(
				[ '' => $localContainer->reveal(), 'foo' => $fooContainer->reveal() ]
			)
		);

		$factory->entityUpdated( new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) ) );

		$fooContainer->entityUpdated(
			new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) )
		)->shouldHaveBeenCalled();
		$localContainer->entityUpdated( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testEntityDeletedDelegatesEventToContainerOfRelevantRepository() {
		$localContainer = $this->prophesize( RepositoryServiceContainer::class );
		$fooContainer = $this->prophesize( RepositoryServiceContainer::class );
		$factory = $this->newDispatchingServiceFactory(
			$this->createRepositoryServiceContainerFactory(
				[ '' => $localContainer->reveal(), 'foo' => $fooContainer->reveal() ]
			)
		);

		$factory->entityDeleted( new ItemId( 'foo:Q123' ) );

		$fooContainer->entityDeleted( new ItemId( 'foo:Q123' ) )->shouldHaveBeenCalled();
		$localContainer->entityDeleted( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testRedirectUpdatedDelegatesEventToContainerOfRelevantRepository() {
		$localContainer = $this->prophesize( RepositoryServiceContainer::class );
		$fooContainer = $this->prophesize( RepositoryServiceContainer::class );
		$factory = $this->newDispatchingServiceFactory(
			$this->createRepositoryServiceContainerFactory(
				[ '' => $localContainer->reveal(), 'foo' => $fooContainer->reveal() ]
			)
		);

		$factory->redirectUpdated(
			new EntityRedirect( new ItemId( 'foo:Q1' ), new ItemId( 'foo:Q2' ) ),
			100
		);

		$fooContainer->redirectUpdated(
			new EntityRedirect( new ItemId( 'foo:Q1' ), new ItemId( 'foo:Q2' ) ),
			100
		)
			->shouldHaveBeenCalled();
		$localContainer->redirectUpdated(
			Argument::any(),
			Argument::any()
		)->shouldNotHaveBeenCalled();
	}

	public function testGetEntityTypeToRepoMapping() {
		$factory = $this->newDispatchingServiceFactory(
			$this->dummy( RepositoryServiceContainerFactory::class )
		);

		$this->assertEquals(
			[
				Item::ENTITY_TYPE => '',
				Property::ENTITY_TYPE => 'foo',
			],
			$factory->getEntityTypeToRepoMapping()
		);
	}

	/**
	 * @param array $containers Assoc array [ '<repo name>' => RepositoryServiceContainer, ... ]
	 *
	 * @return RepositoryServiceContainerFactory
	 */
	private function createRepositoryServiceContainerFactory( array $containers ) {
		$containerFactory = $this->getMockBuilder( RepositoryServiceContainerFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$containerFactory->method( 'newContainer' )
			->will(
				$this->returnCallback(
					function ( $container ) use ( $containers ) {
						return $containers[ $container ];
					}
				)
			);

		return $containerFactory;
	}

	/**
	 * Creates test dummy
	 *
	 * @param string $class
	 *
	 * @return object
	 */
	private function dummy( $class ) {
		return $this->prophesize( $class )->reveal();
	}

	/**
	 * Creates dummy object (in context of this text to represent some service)
	 *
	 * @param $description
	 *
	 * @return object
	 */
	private function someService( $description ) {
		$result = new \stdClass();
		$result->description = $description;

		return $result;
	}

}
