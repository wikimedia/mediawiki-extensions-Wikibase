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
	 * Tests @see WikibaseItem::newEmpty
	 */
	public function testNewEmpty() {
		$this->assertTrue( WikibaseItem::newEmpty()->isEmpty(), 'Calling isEmpty on a new WikibaseItem after creating it via newEmpty should return true' );
	}

	public function testGetId() {
		$item = WikibaseItem::newFromArray( array( 'entity' => 'q42' ) );
		$this->assertEquals( $item->getId(), 42 );
	}

	/**
	 * @dataProvider provideLabels
	 */
	public function testGetLabels( $input, array $expected, array $languages = null ) {
		$contentHandler = new WikibaseContentHandler();

		$this->assertEquals(
			$contentHandler->unserializeContent( $input,'application/json' )->getLabels( $languages ),
			$expected
		);
	}

	public function provideLabels() {
		return array(
			array(
				'{ "label": [] }',
				array(),
			),
			array(
				'{ "label": [] }',
				array(),
				array(),
			),
			array(
				'{ "label": [] }',
				array(),
				array( 'en', 'de' ),
			),
			array(
				'{ "label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array( 'de' => 'de-value', 'en' => 'en-value' ),
			),
			array(
				'{ "label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array(),
				array(),
			),
			array(
				'{ "label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } } }',
				array( 'en' => 'en-value' ),
				array( 'en' ),
			),
		);
	}

}
