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
 * @author Marius Hoch
 */
class NullUsageTrackerTest extends PHPUnit_Framework_TestCase {

	public function testAddUsedEntities() {
		$instance = new NullUsageTracker();
		$this->assertNull( $instance->addUsedEntities( 0, array() ) );
	}

	public function testReplaceUsedEntities() {
		$instance = new NullUsageTracker();
		$this->assertNull( $instance->replaceUsedEntities( 0, array() ) );
	}

	public function testPruneUsages() {
		$instance = new NullUsageTracker();
		$this->assertSame( array(), $instance->pruneUsages( 0 ) );
	}

	public function testGetUsagesForPage() {
		$instance = new NullUsageTracker();
		$this->assertSame( array(), $instance->getUsagesForPage( 0 ) );
	}

	public function testGetUnusedEntities() {
		$instance = new NullUsageTracker();
		$this->assertSame( array(), $instance->getUnusedEntities( array() ) );
	}

	public function testGetPagesUsing() {
		$instance = new NullUsageTracker();
		$this->assertEquals( new ArrayIterator(), $instance->getPagesUsing( array() ) );
	}

}
