<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesController;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesControllerDispatcher;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesControllerDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WbSearchEntitiesControllerDispatcherTest extends TestCase {

	public function testReturnsRegisteredControllerForKnownType(): void {
		$expectedController = $this->createStub( WbSearchEntitiesController::class );
		$dispatcher = new WbSearchEntitiesControllerDispatcher( [ 'item' => static fn() => $expectedController ] );

		$this->assertSame( $expectedController, $dispatcher->getControllerForEntityType( 'item' ) );
	}

	public function testThrowsForUnknownEntityType(): void {
		$dispatcher = new WbSearchEntitiesControllerDispatcher( [] );

		$this->expectException( InvalidArgumentException::class );
		$dispatcher->getControllerForEntityType( 'property' );
	}

}
