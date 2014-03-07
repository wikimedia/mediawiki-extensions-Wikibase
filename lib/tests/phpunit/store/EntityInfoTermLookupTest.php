<?php

namespace Wikibase\Test;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityInfoTermLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;

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
			'P1' => array(
				'labels' => array(
					'de' => array( 'language' => 'de', 'value' => 'de-label' ),
					'en' => array( 'language' => 'en', 'value' => 'en-label' ),
				),
				'descriptions' => array(
					'de' => array( 'language' => 'de', 'value' => 'de-description' ),
					'en' => array( 'language' => 'en', 'value' => 'en-description' ),
				),
			),
		) );
	}

	private function newLanguageFallbackChain() {
		return new LanguageFallbackChain( array(
			LanguageWithConversion::factory( 'en' ),
		) );
	}

	public function testGetLabelForId() {
		$lookup = $this->newEntityTermLookup();
		$label = $lookup->getLabelForId( new PropertyId( 'P1' ), 'en' );

		$this->assertEquals( 'en-label', $label );
	}

	public function testGetDescriptionForId() {
		$lookup = $this->newEntityTermLookup();
		$description = $lookup->getDescriptionForId( new PropertyId( 'P1' ), 'en' );

		$this->assertEquals( 'en-description', $description );
	}

	public function testGetLabelValueForId() {
		$lookup = $this->newEntityTermLookup();
		$chain = $this->newLanguageFallbackChain();
		$label = $lookup->getLabelValueForId( new PropertyId( 'P1' ), $chain );

		$this->assertEquals( 'en-label', $label );
	}

	public function testGetDescriptionValueForId() {
		$lookup = $this->newEntityTermLookup();
		$chain = $this->newLanguageFallbackChain();
		$label = $lookup->getDescriptionValueForId( new PropertyId( 'P1' ), $chain );

		$this->assertEquals( 'en-description', $label );
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
