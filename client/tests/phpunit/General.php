<?php

/**
 * Tests for the WikibaseClient.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 * 
 * @group Wikibase
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 */
class WikibaseClientGeneralTests extends MediaWikiTestCase {

	/**
	 * No local links, no remote links.
	 */
	public function testEmpty() {
		$links = $this->doParse( new Title(), array(), array() );
		$this->assertEquals( array(), $links );
	}

	/**
	 * Only local links, no remote links, alphabetical sorting.
	 */
	public function testLocal() {
		$links = $this->doParse(
			Title::newFromText( "Berlin" ),
			array(
				'source' => array(
					'var' => array(
						"Berlin" => array()
					)
				),
				'sort' => 'alphabetic',
			),
			array( "fr:Berlin", "de:Berlin" )
		);
		$this->assertEquals( array( "de:Berlin", "fr:Berlin" ), $links );
	}

	/**
	 * No local links, only remote links, alphabetical sorting.
	 */
	public function testRemote() {
		$links = $this->doParse(
			Title::newFromText( "Berlin" ),
			array(
				'source' => array(
					'var' => array(
						"Berlin" => array( "mk" => "Берлин", "fr" => "Berlin" )
					)
				),
				'sort' => 'alphabetic',
			),
			array()
		);
		$this->assertEquals( array( "fr:Berlin", "mk:Берлин" ), $links );
	}

	/**
	 * Local links, remote links, alphabetical sorting.
	 */
	public function testAll() {
		$links = $this->doParse(
			Title::newFromText( "Berlin" ),
			array(
				'source' => array(
					'var' => array(
						"Berlin" => array( "fr" => "Berlin", "de" => "Berlin" )
					)
				),
				'sort' => 'alphabetic',
			),
			array( "mk:Берлин", "fr:Berlin" )
		);
		$this->assertEquals( array( "de:Berlin", "fr:Berlin", "mk:Берлин" ), $links );
	}

	/**
	 * Don't do anything outside of the main namespace...
	 */
	public function testNoNamespace() {
		$title = Title::makeTitle( NS_CATEGORY, "Berlin" );

		$links = $this->doParse(
			$title,
			array(
				'source' => array(
					'var' => array(
						$title->getFullText() => array( "fr" => "Berlin", "de" => "Berlin" )
					)
				),
			),
			array()
		);
		$this->assertEquals( array(), $links );
	}

	/**
	 * ...unless I say so.
	 */
	public function testNamespace() {
		$title = Title::makeTitle( NS_CATEGORY, "Berlin" );

		$links = $this->doParse(
			$title,
			array(
				'source' => array(
					'var' => array(
						$title->getFullText() => array( "fr" => "Berlin", "de" => "Berlin" )
					)
				),
				'sort' => 'alphabetic',
				'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			),
			array()
		);
		$this->assertEquals( array( "de:Berlin", "fr:Berlin" ), $links );
	}

	/**
	 * Parse a Title, add language links to the result, then run the extension.
	 *
	 * Please don't look on a full stomach! You have been warned!
	 */
	protected function doParse( $title, $settings, $links ) {
		$this->setSettings( $settings );
		$parser = new Parser();
		$opt = new ParserOptions();
		$parser->parse("", $title, $opt);
		$parser->getOutput()->setLanguageLinks( $links );
		$dummy = "";
		WBCLangLinkHandler::onParserBeforeTidy( $parser, $dummy );
		return $parser->getOutput()->getLanguageLinks();
	}

	/**
	 * Set WikibaseClient's global settings.
	 */
	protected function setSettings( $settings = null ) {
		global $egWBCSettings;
		$egWBCSettings = $settings;
		WBCSettings::singleton()->rebuildSettings();
		WBCLangLinkHandler::buildSortOrder();
	}

}
