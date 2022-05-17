<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\GenericEventDispatcher;

/**
 * @covers \Wikibase\Repo\GenericEventDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class GenericEventDispatcherTest extends \PHPUnit\Framework\TestCase {

	public function testRegisterWatcher_failure() {
		$this->expectException( InvalidArgumentException::class );

		$watcher = $this->createMock( EntityStoreWatcher::class );
		$dispatcher = new GenericEventDispatcher( 'Wikibase\Lib\Store\FooBar' );

		// should fail because $watcher doesn't implement FooBar
		$dispatcher->registerWatcher( $watcher );
	}

	public function testDispatch() {
		$q12 = new ItemId( 'Q12' );

		$watcher = $this->createMock( EntityStoreWatcher::class );
		$watcher->expects( $this->once() )
			->method( 'entityDeleted' )
			->with( $q12 );

		$dispatcher = new GenericEventDispatcher( EntityStoreWatcher::class );

		// check register & dispatch
		$handle = $dispatcher->registerWatcher( $watcher );
		$dispatcher->dispatch( 'entityDeleted', $q12 );

		// check unregister
		$dispatcher->unregisterWatcher( $handle );
		$dispatcher->dispatch( 'entityDeleted', new ItemId( 'Q13' ) );
	}

}
