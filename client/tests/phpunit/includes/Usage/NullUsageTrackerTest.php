<?php

namespace Wikibase\Client\Tests\Usage;

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
 * @author Thiemo Kreuz
 * @author Marius Hoch
 */
class NullUsageTrackerTest extends PHPUnit_Framework_TestCase {

	public function testAddUsedEntities() {
		$instance = new NullUsageTracker();
		$this->assertNull( $instance->addUsedEntities( 0, [] ) );
	}

	public function testReplaceUsedEntities() {
		$instance = new NullUsageTracker();
		$this->assertSame( [], $instance->replaceUsedEntities( 0, [] ) );
	}

	public function testPruneUsages() {
		$instance = new NullUsageTracker();
		$this->assertSame( [], $instance->pruneUsages( 0 ) );
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
