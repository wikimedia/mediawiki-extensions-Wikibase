<?php

namespace Wikibase;

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
class WikibaseClientGeneralTests extends \MediaWikiTestCase {

	public $defaultSettings = array(
		'source' => array(
			'var' => array(
				"Berlin" => array( 
					"fr" => array( "site" => "fr", "title"	=> "Berlin" ),
					"de" => array( "site" => "de", "title"	=> "Berlin" )
				)
			)
		),
		'sort' => 'alphabetic',
	);

	/**
	 * No local links, no remote links.
	 */
	public function testEmpty() {
		$links = $this->doParse( new \Title(), array(), array() );
		$this->assertEquals( array(), $links );
	}

	/**
	 * Only local links, no remote links, alphabetical sorting.
	 */
	public function testLocal() {
		$settings = $this->defaultSettings;
		$settings['source']['var'] = array( "Berlin" => array() );
		$links = $this->doParse(
			\Title::newFromText( "Berlin" ),
			$settings,
			array( "fr:Berlin", "de:Berlin" )
		);
		$this->assertEquals( array( "de:Berlin", "fr:Berlin" ), $links );
	}

	/**
	 * No local links, only remote links, alphabetical sorting.
	 */
	public function testRemote() {
		$links = $this->doParse(
			\Title::newFromText( "Berlin" ),
			$this->defaultSettings,
			array()
		);
		$this->assertEquals( array( "de:Berlin", "fr:Berlin" ), $links );
	}

	/**
	 * Local links, remote links, alphabetical sorting.
	 */
	public function testAll() {
		$links = $this->doParse(
			\Title::newFromText( "Berlin" ),
			$this->defaultSettings,
			array( "en:Berlin" )
		);
		$this->assertEquals( array( "de:Berlin", "en:Berlin", "fr:Berlin" ), $links );
	}

	/**
	 * Don't do anything outside of the main namespace...
	 */
	public function testNoNamespace() {
		$title = \Title::makeTitle( NS_CATEGORY, "Berlin" );

		$links = $this->doParse(
			$title,
			$this->defaultSettings,
			array()
		);
		$this->assertEquals( array(), $links );
	}

	/**
	 * ...unless I say so.
	 */
	public function testNamespace() {
		$title = \Title::makeTitle( NS_CATEGORY, "Berlin" );
		$settings = $this->defaultSettings;
		$settings['source']['var'] = array(
			"Category:Berlin" => array(
				"fr" => array( "site" => "fr", "title"  => "Berlin" ),
				"de" => array( "site" => "de", "title"  => "Berlin" )
			)
		);

		$settings['namespaces'] = array( NS_MAIN, NS_CATEGORY );
		$links = $this->doParse(		
			$title,
			$settings,
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
		$parser = new \Parser();
		$opt = new \ParserOptions();
		$parser->parse("", $title, $opt);
		$parser->getOutput()->setLanguageLinks( $links );
		$dummy = "";
		\Wikibase\LangLinkHandler::resetLangLinks();
		\Wikibase\LangLinkHandler::onParserBeforeTidy( $parser, $dummy );
		return $parser->getOutput()->getLanguageLinks();
	}

	/**
	 * Set WikibaseClient's global settings.
	 */
	protected function setSettings( $settings = null ) {
		global $egWBSettings;
		$egWBSettings = $settings;
		\Wikibase\Settings::singleton( true );
		\Wikibase\LangLinkHandler::buildSortOrder();
	}

}
