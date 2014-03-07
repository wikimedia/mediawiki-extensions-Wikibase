<?php

namespace Wikibase\Test;

use Wikibase\EntityInfoTermLookup;

/**
 * @covers Wikibase\EntityInfoTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityInfoTermLookupTest extends \MediaWikiTestCase {

	private function newEntityTermLookup() {
		return new EntityInfoTermLookup( array(
		) );
	}

	public function testGetLabelForId() {
		$lookup = $this->newEntityTermLookup();

		// TODO
		$this->assertTrue( true );
	}

	public function testGetDescriptionForId() {
		$lookup = $this->newEntityTermLookup();

		// TODO
		$this->assertTrue( true );
	}

	public function testGetLabelValueForId() {
		$lookup = $this->newEntityTermLookup();

		// TODO
		$this->assertTrue( true );
	}

	public function testGetDescriptionValueForId() {
		$lookup = $this->newEntityTermLookup();

		// TODO
		$this->assertTrue( true );
	}

}
