<?php

namespace Wikibase\Test;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\Store\EntityTermLookup;

/**
 * @covers Wikibase\EntityTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
abstract class EntityTermLookupTest extends \MediaWikiTestCase {

	/**
	 * @var EntityTermLookup
	 */
	private $lookup;

	protected function setUp() {
		parent::setUp();

		$this->lookup = $this->getEntityTermLookup();
	}

	private function assertValueArray( $expectedLanguageCode, $expectedValue, array $valueArray ) {
		$this->assertEquals( array(
			'language' => $expectedLanguageCode,
			'value' => $expectedValue,
			'source' => null
		), $valueArray );
	}

	/**
	 * @return EntityTermLookup
	 */
	abstract protected function getEntityTermLookup();

	protected function getLanguageFallbackChain( $languageCode = 'en' ) {
		return new LanguageFallbackChain( array(
			LanguageWithConversion::factory( $languageCode ),
		) );
	}

	public function testGetLabelForId() {
		$term = $this->lookup->getLabelForId( new PropertyId( 'P1' ), 'en' );

		$this->assertEquals( 'en-label', $term );
	}

	public function testGetDescriptionForId() {
		$term = $this->lookup->getDescriptionForId( new PropertyId( 'P1' ), 'en' );

		$this->assertEquals( 'en-description', $term );
	}

	public function testGetLabelValueForId() {
		$chain = $this->getLanguageFallbackChain();
		$term = $this->lookup->getLabelValueForId( new PropertyId( 'P1' ), $chain );

		$this->assertValueArray( 'en', 'en-label', $term );
	}

	public function testGetDescriptionValueForId() {
		$chain = $this->getLanguageFallbackChain();
		$term = $this->lookup->getDescriptionValueForId( new PropertyId( 'P1' ), $chain );

		$this->assertValueArray( 'en', 'en-description', $term );
	}

	public function testGetLabelForId_termDoesNotExist() {
		$term = $this->lookup->getLabelForId( new PropertyId( 'P1' ), 'xx' );

		$this->assertNull( $term );
	}

	public function testGetDescriptionForId_termDoesNotExist() {
		$term = $this->lookup->getDescriptionForId( new PropertyId( 'P1' ), 'xx' );

		$this->assertNull( $term );
	}

	public function testGetLabelValueForId_termDoesNotExist() {
		$chain = $this->getLanguageFallbackChain( 'xx' );
		$term = $this->lookup->getLabelValueForId( new PropertyId( 'P1' ), $chain );

		$this->assertNull( $term );
	}

	public function testGetDescriptionValueForId_termDoesNotExist() {
		$chain = $this->getLanguageFallbackChain( 'xx' );
		$term = $this->lookup->getDescriptionValueForId( new PropertyId( 'P1' ), $chain );

		$this->assertNull( $term );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetLabelForId_entityDoesNotExist() {
		$this->lookup->getLabelForId( new PropertyId( 'P999' ), 'en' );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetDescriptionForId_entityDoesNotExist() {
		$this->lookup->getDescriptionForId( new PropertyId( 'P999' ), 'en' );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetLabelValueForId_entityDoesNotExist() {
		$chain = new LanguageFallbackChain( array() );
		$this->lookup->getLabelValueForId( new PropertyId( 'P999' ), $chain );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testGetDescriptionValueForId_entityDoesNotExist() {
		$chain = new LanguageFallbackChain( array() );
		$this->lookup->getDescriptionValueForId( new PropertyId( 'P999' ), $chain );
	}

}
