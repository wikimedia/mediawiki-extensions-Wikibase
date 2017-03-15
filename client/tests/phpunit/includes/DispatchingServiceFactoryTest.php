<?php

namespace Wikibase\Client\Tests;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\EntityRevision;
use Wikibase\Lib\Interactors\DispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Store\DispatchingEntityInfoBuilderFactory;
use Wikibase\Lib\Store\DispatchingEntityPrefetcher;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\DispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\DispatchingTermBuffer;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

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
			[ 'database' => '', 'entity-types' => [], 'prefix-mapping' => [] ],
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

	public function testGetServiceMap_ReturnsArrayMappingNameOfRepositoryToServiceForThatRepository() {
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

	public function testGetEntityInfoBuilderFactory_AlwaysReturnsTheSameService() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'EntityInfoBuilderFactory',
			EntityInfoBuilderFactory::class
		);

		$this->assertInstanceOf(
			DispatchingEntityInfoBuilderFactory::class,
			$factory->getEntityInfoBuilderFactory()
		);

		$serviceOne = $factory->getEntityInfoBuilderFactory();
		$serviceTwo = $factory->getEntityInfoBuilderFactory();

		$this->assertSame( $serviceOne, $serviceTwo );
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

	public function testGetEntityInfoBuilderFactory_ReturnsDispatchingEntityInfoBuilderFactory() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'EntityInfoBuilderFactory',
			EntityInfoBuilderFactory::class
		);

		$this->assertInstanceOf(
			DispatchingEntityInfoBuilderFactory::class,
			$factory->getEntityInfoBuilderFactory()
		);
	}

	public function testGetEntityPrefetcher_ReturnsDispatchingEntityInfoBuilderFactory() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'EntityPrefetcher',
			EntityPrefetcher::class
		);

		$this->assertInstanceOf(
			DispatchingEntityPrefetcher::class,
			$factory->getEntityPrefetcher()
		);
	}

	public function testGetEntityRevisionLookup_ReturnsDispatchingEntityRevisionLookup() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'EntityRevisionLookup',
			EntityRevisionLookup::class
		);

		$this->assertInstanceOf(
			DispatchingEntityRevisionLookup::class,
			$factory->getEntityRevisionLookup()
		);
	}

	public function testGetPropertyInfoLookup_ReturnsDispatchingPropertyInfoLookup() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'PropertyInfoLookup',
			PropertyInfoLookup::class
		);

		$this->assertInstanceOf(
			DispatchingPropertyInfoLookup::class,
			$factory->getPropertyInfoLookup()
		);
	}

	public function testGetTermBuffer_ReturnsDispatchingTermBuffer() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'PrefetchingTermLookup',
			TermBuffer::class
		);

		$this->assertInstanceOf(
			DispatchingTermBuffer::class,
			$factory->getTermBuffer()
		);
	}

	public function testGetTermSearchInteractorFactory_ReturnsDispatchingTermSearchInteractorFactory() {
		$factory = $this->createFactoryWithRepositoryContainerReturningDummyObjectFor(
			'TermSearchInteractorFactory',
			TermSearchInteractorFactory::class
		);

		$this->assertInstanceOf(
			DispatchingTermSearchInteractorFactory::class,
			$factory->getTermSearchInteractorFactory()
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

		$containerFactory
			->method( 'newContainer' )
			->will(
				$this->returnCallback(
					function ( $container ) use ( $containers ) {
						return $containers[$container];
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

	/**
	 * @param string $repositoryServiceContainerKey
	 * @param string $classOfObjectToReturn
	 * @return DispatchingServiceFactory
	 */
	private function createFactoryWithRepositoryContainerReturningDummyObjectFor(
		$repositoryServiceContainerKey,
		$classOfObjectToReturn
	) {
		/** @var RepositoryServiceContainer|ObjectProphecy $localRepositoryServiceContainer */
		$localRepositoryServiceContainer = $this->prophesize( RepositoryServiceContainer::class );
		$localRepositoryServiceContainer->getService( $repositoryServiceContainerKey )->willReturn(
			$this->dummy( $classOfObjectToReturn )
		);

		/** @var RepositoryServiceContainerFactory|ObjectProphecy $rscf */
		$rscf = $this->prophesize( RepositoryServiceContainerFactory::class );
		$rscf->newContainer( '' )->willReturn( $localRepositoryServiceContainer );

		$factory = new DispatchingServiceFactory(
			$rscf->reveal(),
			new RepositoryDefinitions(
				array_merge(
					$this->getRepositoryDefinition( '', [] )
				)
			)
		);
		return $factory;
	}

}
