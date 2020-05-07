<?php

declare( strict_types = 1 );
namespace Wikibase\Client\Tests\Integration\DataAccess\ParserFunctions;

use ExtensionRegistry;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\WikibaseRepo;

/**
 * Simple integration test for the Wikibase Lua functionality.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class LuaWikibaseIntegrationTest extends MediaWikiTestCase {

	/**
	 * @var bool|null
	 */
	private $oldAllowDataAccessInUserLanguage;

	/**
	 * @var Item|null
	 */
	private $testItem = null;

	protected function setUp(): void {
		parent::setUp();

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Scribunto' ) ) {
			$this->markTestSkipped( 'Lua tests need Scribunto to be installled.' );
		}
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'This integration test needs WikibaseRepo.' );
		}

		$this->setMwGlobals( 'wgLanguageCode', 'de' );

		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );
		$settings = $wikibaseClient->getSettings();

		$this->oldAllowDataAccessInUserLanguage = $settings->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );

		$this->insertLuaModule();
	}

	private function insertLuaModule(): void {
		$lua = "local p = {}\n" .
		// Create a dummy Snak
		"local dataValue = { type = 'wikibase-entityid', value = { ['entity-type'] = 'item', id = 'ENTITY-ID' } }\n" .
		"local snak = { datatype = 'wikibase-item', property = 'P435739845', snaktype = 'value', datavalue = dataValue }\n" .
		// Functions called by the tests
		"p.getLabel = function() return mw.wikibase.getLabel( 'ENTITY-ID' ) end\n" .
		"p.getLabelByLang = function() return mw.wikibase.getLabelByLang( 'ENTITY-ID', 'en' ) end\n" .
		"p.getEntity_labels = function() return mw.wikibase.getEntity( 'ENTITY-ID' ).labels.de.value end\n" .
		"p.getDescription = function() return mw.wikibase.getDescription( 'ENTITY-ID' ) end\n" .
		"p.getSitelink = function() return mw.wikibase.getSitelink( 'ENTITY-ID', 'integrationtestwiki' ) end\n" .
		"p.formatValue = function() return mw.wikibase.formatValue( snak ) end\n" .
		"return p";

		$this->insertPage(
			'Module:LuaWikibaseIntegrationTest',
			str_replace( 'ENTITY-ID', $this->getTestItem()->getId()->getSerialization(), $lua )
		);
	}

	private function getTestItem(): Item {
		$siteLink = new SiteLink( 'integrationtestwiki', 'a-page-name' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$itemId = $wikibaseRepo->getStore()->newSiteLinkStore()->getItemIdForSiteLink( $siteLink );
		if ( $itemId ) {
			$this->testItem = $wikibaseRepo->getEntityLookup()->getEntity( $itemId );
		}

		if ( !$this->testItem ) {
			$this->testItem = new Item();
			$this->testItem->setLabel( 'de', 'a-German-label' );
			$this->testItem->setLabel( 'en', 'an-English-label' );
			$this->testItem->setDescription( 'de', 'a-German-description' );
			$this->testItem->addSiteLink( $siteLink );

			$wikibaseRepo->getEntityStore()->saveEntity(
				$this->testItem,
				'',
				$this->getTestUser()->getUser(),
				EDIT_NEW
			);
		}

		return $this->testItem;
	}

	private function newParserOutputUsageAccumulator( ParserOutput $parserOutput ): ParserOutputUsageAccumulator {
		return new ParserOutputUsageAccumulator(
			$parserOutput,
			new EntityUsageFactory( new BasicEntityIdParser() )
		);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );

		WikibaseClient::getDefaultInstance( 'reset' );
	}

	/**
	 * Get the usage string for our test item and the given aspect.
	 *
	 * @param string $aspect
	 * @return string
	 */
	private function getUsageString( string $aspect ): string {
		return $this->testItem->getId()->getSerialization() . '#' . $aspect;
	}

	/**
	 * @param bool $value
	 */
	private function setAllowDataAccessInUserLanguage( bool $value ): void {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

	public function testStatementsParserFunction_getLabel() {
		$result = $this->parseWikitextToHtml( '{{#invoke:LuaWikibaseIntegrationTest|getLabel}}' );

		$this->assertSame( "<p>a-German-label\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ $this->getUsageString( 'L.de' ) ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_getLabelByLang(): void {
		$result = $this->parseWikitextToHtml( '{{#invoke:LuaWikibaseIntegrationTest|getLabelByLang}}' );

		$this->assertSame( "<p>an-English-label\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ $this->getUsageString( 'L.en' ) ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_getEntityGetLabel(): void {
		$result = $this->parseWikitextToHtml( '{{#invoke:LuaWikibaseIntegrationTest|getEntity_labels}}' );

		$this->assertSame( "<p>a-German-label\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ $this->getUsageString( 'L.de' ) ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_getDescription(): void {
		$result = $this->parseWikitextToHtml( '{{#invoke:LuaWikibaseIntegrationTest|getDescription}}' );

		$this->assertSame( "<p>a-German-description\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ $this->getUsageString( 'D.de' ) ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_getSitelink(): void {
		$result = $this->parseWikitextToHtml( '{{#invoke:LuaWikibaseIntegrationTest|getSitelink}}' );

		$this->assertSame( "<p>a-page-name\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ $this->getUsageString( 'S' ) ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_formatValue(): void {
		$result = $this->parseWikitextToHtml( '{{#invoke:LuaWikibaseIntegrationTest|formatValue}}' );

		$this->assertStringContainsString( "a-German-label", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[
				$this->getUsageString( 'T' ), $this->getUsageString( 'L.de' ),
			],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	/**
	 * @param string $wikiText
	 *
	 * @return ParserOutput
	 */
	private function parseWikitextToHtml( string $wikiText ): ParserOutput {
		$popt = new ParserOptions( User::newFromId( 0 ), Language::factory( 'en' ) );
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();

		return $parser->parse(
			$wikiText,
			Title::newFromText( 'WikibaseClientDataAccessTest' ),
			$popt,
			Parser::OT_HTML
		);
	}

}
