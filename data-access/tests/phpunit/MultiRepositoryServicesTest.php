<?php

namespace Wikibase\DataAccess\Tests;

use Prophecy\Argument;
use Wikibase\DataAccess\MultiRepositoryServices;
use Wikibase\DataAccess\PerRepositoryServiceContainer;
use Wikibase\DataAccess\PerRepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\RepositoryDefinitions;

/**
 * @covers Wikibase\DataAccess\MultiRepositoryServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class MultiRepositoryServicesTest extends \PHPUnit_Framework_TestCase {

	const ITEM_NAMESPACE = 100;
	const PROPERTY_NAMESPACE = 300;

	private function getRepositoryDefinition( $repositoryName, array $customSettings ) {
		return [ $repositoryName => array_merge(
			[
				'database' => '',
				'base-uri' => 'http://acme.test/concept/',
				'entity-namespaces' => [],
				'prefix-mapping' => []
			],
			$customSettings
		) ];
	}

	public function testGetServiceNames_ReturnsNameOfDefinedService() {
		$services = new MultiRepositoryServices(
			$this->dummy( PerRepositoryServiceContainerFactory::class ),
			new RepositoryDefinitions( $this->getRepositoryDefinition( '', [] ) )
		);

		$services->defineService(
			'SomeService',
			function () {
				return $this->someService( 'does not matter' );
			}
		);

		$this->assertContains( 'SomeService', $services->getServiceNames() );
	}

	private function newMultiRepositoryServices( PerRepositoryServiceContainerFactory $containerFactory ) {
		return new MultiRepositoryServices(
			$containerFactory,
			new RepositoryDefinitions( array_merge(
				$this->getRepositoryDefinition( '', [ 'entity-namespaces' => [ Item::ENTITY_TYPE => self::ITEM_NAMESPACE ] ] ),
				$this->getRepositoryDefinition( 'foo', [ 'entity-namespaces' => [ Property::ENTITY_TYPE => self::PROPERTY_NAMESPACE ] ] )
			) )
		);
	}

	public function testGetServiceMap_ReturnsArrayMappingNameOfRepositoryToServiceForThatRepository(
	) {
		$someServiceName = 'some-service';
		$localService = $this->someService( 'local' );
		$fooService = $this->someService( 'foo' );

		$localContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$localContainer->getService( $someServiceName )->willReturn( $localService );

		$fooContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$fooContainer->getService( $someServiceName )->willReturn( $fooService );

		$containerFactory = $this->prophesize( PerRepositoryServiceContainerFactory::class );
		$containerFactory->newContainer( '' )->willReturn( $localContainer );
		$containerFactory->newContainer( 'foo' )->willReturn( $fooContainer );
		$multiRepositoryServices = $this->newMultiRepositoryServices( $containerFactory->reveal() );

		$serviceMap = $multiRepositoryServices->getServiceMap( $someServiceName );

		$expectedServiceMap = [
			'' => $localService,
			'foo' => $fooService,
		];
		$this->assertEquals( $expectedServiceMap, $serviceMap );
	}

	public function testGetService_AlwaysReturnsTheSameService() {
		$services = new MultiRepositoryServices(
			$this->dummy( PerRepositoryServiceContainerFactory::class ),
			new RepositoryDefinitions( $this->getRepositoryDefinition( '', [] ) )
		);

		$someService = $this->someService( 'some service instance' );
		$services->defineService(
			'some-service',
			function () use ( $someService ) {
				return $someService;
			}
		);

		$serviceOne = $services->getService( 'some-service' );
		$serviceTwo = $services->getService( 'some-service' );

		$this->assertSame( $someService, $serviceOne );
		$this->assertSame( $someService, $serviceTwo );
	}

	public function testEntityUpdatedDelegatesEventToContainerOfRelevantRepository() {
		$localContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$fooContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$multiRepositoryServices = $this->newMultiRepositoryServices(
			$this->createRepositoryServiceContainerFactory(
				[ '' => $localContainer->reveal(), 'foo' => $fooContainer->reveal() ]
			)
		);

		$multiRepositoryServices->entityUpdated( new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) ) );

		$fooContainer->entityUpdated(
			new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) )
		)->shouldHaveBeenCalled();
		$localContainer->entityUpdated( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testEntityDeletedDelegatesEventToContainerOfRelevantRepository() {
		$localContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$fooContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$multiRepositoryServices = $this->newMultiRepositoryServices(
			$this->createRepositoryServiceContainerFactory(
				[ '' => $localContainer->reveal(), 'foo' => $fooContainer->reveal() ]
			)
		);

		$multiRepositoryServices->entityDeleted( new ItemId( 'foo:Q123' ) );

		$fooContainer->entityDeleted( new ItemId( 'foo:Q123' ) )->shouldHaveBeenCalled();
		$localContainer->entityDeleted( Argument::any() )->shouldNotHaveBeenCalled();
	}

	public function testRedirectUpdatedDelegatesEventToContainerOfRelevantRepository() {
		$localContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$fooContainer = $this->prophesize( PerRepositoryServiceContainer::class );
		$multiRepositoryServices = $this->newMultiRepositoryServices(
			$this->createRepositoryServiceContainerFactory(
				[ '' => $localContainer->reveal(), 'foo' => $fooContainer->reveal() ]
			)
		);

		$multiRepositoryServices->redirectUpdated(
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
		$services = $this->newMultiRepositoryServices(
			$this->dummy( PerRepositoryServiceContainerFactory::class )
		);

		$this->assertEquals(
			[
				Item::ENTITY_TYPE => [ [ '', self::ITEM_NAMESPACE ] ],
				Property::ENTITY_TYPE => [ [ 'foo', self::PROPERTY_NAMESPACE ] ],
			],
			$services->getEntityTypeToRepoMapping()
		);
	}

	/**
	 * @param array $containers Assoc array [ '<repo name>' => RepositoryServiceContainer, ... ]
	 *
	 * @return PerRepositoryServiceContainerFactory
	 */
	private function createRepositoryServiceContainerFactory( array $containers ) {
		$containerFactory = $this->getMockBuilder( PerRepositoryServiceContainerFactory::class )
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
	 * @param string $description
	 *
	 * @return object
	 */
	private function someService( $description ) {
		$result = new \stdClass();
		$result->description = $description;

		return $result;
	}

}
