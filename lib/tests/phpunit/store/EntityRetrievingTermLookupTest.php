<?php

namespace Wikibase\Test;

use Wikibase\EntityRetrievingTermLookup;

/**
 * @covers Wikibase\EntityRetrievingTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityRetrievingTermLookupTest extends \MediaWikiTestCase {

	private function newEntityTermLookup() {
		$entityLookup = new MockRepository();
		return new EntityRetrievingTermLookup( $entityLookup );
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
