<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\GenericEventDispatcher;

/**
 * @covers Wikibase\GenericEventDispatcher
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 */
class GenericEventDispatcherTest extends \PHPUnit_Framework_TestCase {

	public function testDispatch() {
		$watcher = $this->getMock( 'Wikibase\EntityStoreWatcher' );
		$watcher->expects( $this->once() )->method( 'entityDeleted' );

		$dispatcher = new GenericEventDispatcher( 'Wikibase\EntityStoreWatcher' );

		// check register & dispatch
		$handle = $dispatcher->registerWatcher( $watcher );
		$dispatcher->dispatch( 'entityDeleted', new ItemId( 'Q12' ) );

		// check unregister
		$dispatcher->unregisterWatcher( $handle );
		$dispatcher->dispatch( 'entityDeleted', new ItemId( 'Q13' ) );
	}

}
