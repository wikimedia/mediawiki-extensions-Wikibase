<?php

/**
 * Tests for the WikibaseItem class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseItemTests extends MediaWikiTestCase {

	/**
	 * Tests @see WikibaseItem::getIdForLinkSite
	 */
	public function testNotFound() {
		$this->assertFalse(
			WikibaseItem::getIdForLinkSite( 9999, "ThisDoesNotExist" ),
			'Calling getIdForLinkSite( 42, "ThisDoesNotExist" ) should return false'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::newEmpty
	 */
	public function testNewEmpty() {
		$this->assertFalse(
			WikibaseItem::newEmpty()->isEmpty(),
			'Calling isEmpty on a new WikibaseItem after creating it via newEmpty() should return true'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::getLabels
	 * @depends testNewEmpty
	 */
	public function testNewEmptyLabels() {
		$this->assertCount(
			0,
			WikibaseItem::newEmpty()->getLabels(),
			'Calling count on a new WikibaseItem after creating it and dumping labels should return zero'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::getDescriptions
	 * @depends testNewEmpty
	 */
	public function testNewEmptyDescriptions() {
		$this->assertCount(
			0,
			WikibaseItem::newEmpty()->getDescriptions(),
			'Calling count on a new WikibaseItem after creating it and dumping descriptionss should return zero'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::testGetId
	 */
	public function testGetId() {
		$item = WikibaseItem::newFromArray( array( 'entity' => 'q42' ) );
		$this->assertEquals(
			42,
			$item->getId(),
			'Calling getID on a new WikibaseItem after creating it with an entity "q42" should return number 42'
		);
	}

	/**
	 * @dataProvider provideLabels
	 */
	public function testGetLabels( array $expected, $input, array $languages = null ) {
		$contentHandler = new WikibaseContentHandler();

		$this->assertEquals(
			$expected,
			$contentHandler->unserializeContent( $input,'application/json' )->getLabels( $languages ),
			'Testing getLabels on a new WikibaseItem after creating it with preset values and doing a unserializeContent'
		);
	}

	/**
	 * @dataProvider provideDescriptions
	 */
	public function testGetDescriptions( array $expected, $input, array $languages = null ) {
		$contentHandler = new WikibaseContentHandler();

		$this->assertEquals(
			$expected,
			$contentHandler->unserializeContent( $input,'application/json' )->getDescriptions( $languages ),
			'Testing getDescriptions on a new WikibaseItem after creating it with preset values and doing a unserializeContent'
		);
	}

	public function provideLabels() {
		return array(
			array(
				array(),
				'{ "label": [] }',
			),
			array(
				array(),
				'{ "label": [] }',
				array(),
			),
			array(
				array(),
				'{ "label": [] }',
				array( 'en', 'de' ),
			),
			array(
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				'{ "label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
			),
			array(
				array(),
				'{ "label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array(),
			),
			array(
				array( 'en' => 'en-value' ),
				'{ "label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array( 'en' ),
			),
		);
	}
	
	public function provideDescriptions() {
		return array(
			array(
				array(),
				'{ "description": [] }',
			),
			array(
				array(),
				'{ "description": [] }',
				array(),
			),
			array(
				array(),
				'{ "description": [] }',
				array( 'en', 'de' ),
			),
			array(
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				'{ "description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
			),
			array(
				array(),
				'{ "description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array(),
			),
			array(
				array( 'en' => 'en-value' ),
				'{ "description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array( 'en' ),
			),
		);
	}
	
}
