<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit;

use Generator;
use LogicException;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\HookContainer\StaticHookRegistry;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikimedia\ObjectFactory;

/**
 * @license GPL-2.0-or-later
 */
abstract class ServiceWiringTestCase extends TestCase {

	/**
	 * @var array
	 */
	private $wiring;

	/**
	 * @var MockObject|MediaWikiServices
	 */
	protected $serviceContainer;

	public function setUp(): void {
		parent::setUp();

		$this->wiring = $this->loadWiring();
		$this->serviceContainer = $this->createMock( MediaWikiServices::class );
	}

	protected function getDefinition( $name ): callable {
		if ( !array_key_exists( $name, $this->wiring ) ) {
			throw new LogicException( "Service wiring '$name' does not exist" );
		}
		return $this->wiring[ $name ];
	}

	protected function getService( $name ) {
		return $this->getDefinition( $name )( $this->serviceContainer );
	}

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
		return require __DIR__ . '/../../../WikibaseRepo.ServiceWiring.php';
	}

}
