<?php

namespace Wikibase\Client\Tests\RecentChanges;

use ArrayIterator;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Usage\NullUsageTracker;

/**
 * @covers Wikibase\Client\Usage\NullUsageTracker
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class NullUsageTrackerTest extends PHPUnit_Framework_TestCase {

	public function testTrackUsedEntities() {
		$instance = new NullUsageTracker();
		$this->assertNull( $instance->trackUsedEntities( 0, [], '' ) );
	}

	public function testPruneStaleUsages() {
		$instance = new NullUsageTracker();
		$this->assertSame( [], $instance->pruneStaleUsages( 0, '' ) );
	}

	public function testGetUsagesForPage() {
		$instance = new NullUsageTracker();
		$this->assertSame( [], $instance->getUsagesForPage( 0 ) );
	}

	public function testGetUnusedEntities() {
		$instance = new NullUsageTracker();
		$this->assertSame( [], $instance->getUnusedEntities( [] ) );
	}

	public function testGetPagesUsing() {
		$instance = new NullUsageTracker();
		$this->assertEquals( new ArrayIterator(), $instance->getPagesUsing( [] ) );
	}

}
