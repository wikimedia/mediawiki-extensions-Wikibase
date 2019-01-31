<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\UnusableEntitySource;

/**
 * @covers \Wikibase\DataAccess\UnusableEntitySource
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnusableEntitySourceTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \LogicException
	 */
	public function testGetDatabaseNameThrowsException() {
		$source = new UnusableEntitySource();

		$source->getDatabaseName();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetSourceNameThrowsException() {
		$source = new UnusableEntitySource();

		$source->getSourceName();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetEntitySlotNamesThrowsException() {
		$source = new UnusableEntitySource();

		$source->getEntitySlotNames();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetEntityTypesThrowsException() {
		$source = new UnusableEntitySource();

		$source->getEntityTypes();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetEntityNamespaceIdsThrowsException() {
		$source = new UnusableEntitySource();

		$source->getEntityNamespaceIds();
	}

}
