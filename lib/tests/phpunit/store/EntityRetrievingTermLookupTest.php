<?php

namespace Wikibase\Test;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRetrievingTermLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;

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

	private function newLanguageFallbackChain() {
		return new LanguageFallbackChain( array(
			LanguageWithConversion::factory( 'en' ),
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

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetLabelForId_throwsException() {
		$lookup = $this->newEntityTermLookup();
		$lookup->getLabelForId( new PropertyId( 'P999' ), 'en' );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetDescriptionForId_throwsException() {
		$lookup = $this->newEntityTermLookup();
		$lookup->getDescriptionForId( new PropertyId( 'P999' ), 'en' );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetLabelValueForId_throwsException() {
		$lookup = $this->newEntityTermLookup();
		$chain = new LanguageFallbackChain( array() );
		$lookup->getLabelValueForId( new PropertyId( 'P999' ), $chain );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetDescriptionValueForId_throwsException() {
		$lookup = $this->newEntityTermLookup();
		$chain = new LanguageFallbackChain( array() );
		$lookup->getDescriptionValueForId( new PropertyId( 'P999' ), $chain );
	}

}
