<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\MultiEntityStoreWatcher;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * @covers Wikibase\DataAccess\MultiEntityStoreWatcher
 *
 * @license GPL-2.0+
 */
class MultiEntityStoreWatcherTest extends \PHPUnit_Framework_TestCase {

	public function testEntityUpdatedDelegatesEventToAllWatchers() {
		$watcherOne = $this->prophesize( EntityStoreWatcher::class );
		$watcherTwo = $this->prophesize( EntityStoreWatcher::class );

		$multiWatcher = new MultiEntityStoreWatcher( [
			$watcherOne->reveal(),
			$watcherTwo->reveal()
		] );

		$multiWatcher->entityUpdated( new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) ) );

		$watcherOne->entityUpdated(
			new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) )
		)->shouldHaveBeenCalled();
		$watcherTwo->entityUpdated(
			new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) )
		)->shouldHaveBeenCalled();
	}

	public function testEntityDeletedDelegatesEventToAllWatchers() {
		$watcherOne = $this->prophesize( EntityStoreWatcher::class );
		$watcherTwo = $this->prophesize( EntityStoreWatcher::class );

		$multiWatcher = new MultiEntityStoreWatcher( [
			$watcherOne->reveal(),
			$watcherTwo->reveal()
		] );

		$multiWatcher->entityDeleted( new ItemId( 'foo:Q123' ) );

		$watcherOne->entityDeleted( new ItemId( 'foo:Q123' ) )->shouldHaveBeenCalled();
		$watcherTwo->entityDeleted( new ItemId( 'foo:Q123' ) )->shouldHaveBeenCalled();
	}

	public function testRedirectUpdatedDelegatesEventToAllWatchers() {
		$watcherOne = $this->prophesize( EntityStoreWatcher::class );
		$watcherTwo = $this->prophesize( EntityStoreWatcher::class );

		$multiWatcher = new MultiEntityStoreWatcher( [
			$watcherOne->reveal(),
			$watcherTwo->reveal()
		] );

		$multiWatcher->redirectUpdated(
			new EntityRedirect( new ItemId( 'foo:Q1' ), new ItemId( 'foo:Q2' ) ),
			100
		);

		$watcherOne->redirectUpdated(
			new EntityRedirect( new ItemId( 'foo:Q1' ), new ItemId( 'foo:Q2' ) ),
			100
		)->shouldHaveBeenCalled();
		$watcherTwo->redirectUpdated(
			new EntityRedirect( new ItemId( 'foo:Q1' ), new ItemId( 'foo:Q2' ) ),
			100
		)->shouldHaveBeenCalled();
	}

}
