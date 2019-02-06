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

	use \PHPUnit4And6Compat;

	public function testGetDatabaseNameThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getDatabaseName();
	}

	public function testGetSourceNameThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getSourceName();
	}

	public function testGetEntitySlotNamesThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getEntitySlotNames();
	}

	public function testGetEntityTypesThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getEntityTypes();
	}

	public function testGetEntityNamespaceIdsThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getEntityNamespaceIds();
	}

	public function testGetConceptBaseUriThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getConceptBaseUri();
	}

	public function testGetInterwikiPrefixThrowsException() {
		$source = new UnusableEntitySource();

		$this->expectException( \LogicException::class );

		$source->getInterwikiPrefix();
	}

}
