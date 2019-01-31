<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\NeverToBeUsedEntitySource;

/**
 * @covers \Wikibase\DataAccess\NeverToBeUsedEntitySource
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NeverToBeUsedEntitySourceTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \LogicException
	 */
	public function testGetDatabaseNameThrowsException() {
		$source = new NeverToBeUsedEntitySource();

		$source->getDatabaseName();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetSourceNameThrowsException() {
		$source = new NeverToBeUsedEntitySource();

		$source->getSourceName();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetEntitySlotNamesThrowsException() {
		$source = new NeverToBeUsedEntitySource();

		$source->getEntitySlotNames();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetEntityTypesThrowsException() {
		$source = new NeverToBeUsedEntitySource();

		$source->getEntityTypes();
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGetEntityNamespaceIdsThrowsException() {
		$source = new NeverToBeUsedEntitySource();

		$source->getEntityNamespaceIds();
	}

}
