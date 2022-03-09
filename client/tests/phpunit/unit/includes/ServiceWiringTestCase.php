<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit;

use Generator;
use LogicException;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\HookContainer\StaticHookRegistry;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikimedia\ObjectFactory\ObjectFactory;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;

/**
 * @license GPL-2.0-or-later
 */
abstract class ServiceWiringTestCase extends TestCase {

	/**
	 * @var array
	 */
	private $wiring;

	/** @var mixed[] */
	private $mockedServices;

	/** @var null[] */
	private $accessedServices;

	/**
	 * @var MockObject|MediaWikiServices
	 */
	protected $serviceContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->wiring = $this->loadWiring();
		$this->mockedServices = [];
		$this->accessedServices = [];
		$this->serviceContainer = $this->createMock( MediaWikiServices::class );
		$this->serviceContainer->method( 'get' )
			->willReturnCallback( function ( string $name ) {
				$this->assertArrayNotHasKey( $name, $this->accessedServices,
					"Service $name must not be accessed more than once" );
				$this->accessedServices[$name] = null;
				$this->assertArrayHasKey( $name, $this->mockedServices,
					"Service $name must be mocked" );
				return $this->mockedServices[$name];
			} );
		$this->serviceContainer->expects( $this->never() )
			->method( 'getService' ); // get() should be used instead
		// WikibaseClient service getters should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDbAccess();
		$this->disallowHttpAccess();
	}

	protected function tearDown(): void {
		$this->assertEqualsCanonicalizing( array_keys( $this->mockedServices ), array_keys( $this->accessedServices ),
			'Expected every mocked service to be used' );

		parent::tearDown();
	}

	private function getDefinition( $name ): callable {
		if ( !array_key_exists( $name, $this->wiring ) ) {
			throw new LogicException( "Service wiring '$name' does not exist" );
		}
		return $this->wiring[ $name ];
	}

	/**
	 * Get a WikibaseClient service by calling its wiring function.
	 *
	 * @param string $id full service name (including "WikibaseClient." prefix)
	 * @return mixed service (typically an object)
	 */
	protected function getService( $name ) {
		return $this->getDefinition( $name )( $this->serviceContainer );
	}

	/**
	 * Mock a service that will be accessed through get().
	 *
	 * Typically, this will be a WikibaseClient service.
	 * The test will assert that the service is used exactly once.
	 *
	 * To mock a MediaWiki service, directly mock the getServiceName()
	 * method on the service container instead.
	 *
	 * @param string $name full service name (including prefix like "WikibaseClient.")
	 * @param mixed $service service (usually an object)
	 */
	protected function mockService( string $name, $service ) {
		$this->assertArrayNotHasKey( $name, $this->mockedServices,
			"Service $name must not be mocked already" );
		$this->mockedServices[$name] = $service;
	}

	/**
	 * Mock the MediaWiki hook container with the given hook handlers.
	 *
	 * @param callable[][] $globalHooks
	 * @param callable[][] $extensionHooks
	 * @param callable[][] $deprecatedHooks
	 */
	protected function configureHookContainer(
		array $globalHooks = [],
		array $extensionHooks = [],
		array $deprecatedHooks = []
	): void {
		$hookContainer = new HookContainer(
			new StaticHookRegistry( $globalHooks, $extensionHooks, $deprecatedHooks ),
			new ObjectFactory( $this->serviceContainer )
		);
		$this->serviceContainer->method( 'getHookContainer' )
			->willReturn( $hookContainer );
	}

	public function provideWiring(): Generator {
		$wiring = $this->loadWiring();
		foreach ( $wiring as $name => $definition ) {
			yield $name => [ $name, $definition ];
		}
	}

	private function loadWiring(): array {
		return require __DIR__ . '/../../../../WikibaseClient.ServiceWiring.php';
	}

	private function disallowDbAccess() {
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->expects( $this->never() )
			->method( 'getConnection' );
		$lb->expects( $this->never() )
			->method( 'getConnectionRef' );
		$lb->expects( $this->never() )
			->method( 'getMaintenanceConnectionRef' );
		$lb->method( 'getLocalDomainID' )
			->willReturn( 'banana' );

		$this->serviceContainer->method( 'getDBLoadBalancer' )
			->willReturn( $lb );

		$this->serviceContainer->method( 'getDBLoadBalancerFactory' )
			->willReturnCallback( function() use ( $lb ) {
				$lbFactory = $this->createMock( LBFactory::class );
				$lbFactory->method( 'getMainLB' )
					->willReturn( $lb );
				$lbFactory->method( 'getLocalDomainID' )
					->willReturn( 'clientDbDomain' );

				return $lbFactory;
			} );
	}

	private function disallowHttpAccess() {
		$this->serviceContainer->method( 'getHttpRequestFactory' )
			->willReturnCallback( function() {
				$factory = $this->createMock( HttpRequestFactory::class );
				$factory->expects( $this->never() )
					->method( 'create' );
				$factory->expects( $this->never() )
					->method( 'request' );
				$factory->expects( $this->never() )
					->method( 'get' );
				$factory->expects( $this->never() )
					->method( 'post' );
				return $factory;
			} );
	}

}
