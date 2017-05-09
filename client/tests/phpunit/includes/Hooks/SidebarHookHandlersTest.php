<?php

namespace Wikibase\Client\Tests\Hooks;

use IContextSource;
use Language;
use OutputPage;
use ParserOutput;
use RequestContext;
use Skin;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarHookHandlers;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\NamespaceChecker;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Hooks\SidebarHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SidebarHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup() {
		$labelLookup = $this->getMock( LabelDescriptionLookup::class );

		$labelLookup->expects( $this->any() )
			  ->method( 'getLabel' )
			  ->will( $this->returnValue( 'o' ) );

		return $labelLookup;
	}

	/**
	 * @param array $settings
	 *
	 * @return SettingsArray
	 */
	private function newSettings( array $settings ) {
		$defaults = array(
			'siteGlobalID' => 'enwiki',
			'languageLinkSiteGroup' => 'wikipedia',
			'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			'otherProjectsLinks' => array( 'commonswiki' ),
			'otherProjectsLinksBeta' => true,
			'otherProjectsLinksByDefault' => false,
		);

		return new SettingsArray( array_merge( $defaults, $settings ) );
	}

	/**
	 * @param array $projects
	 *
	 * @return OtherProjectsSidebarGenerator
	 */
	private function getSidebarGenerator( array $projects ) {
		$sidebarGenerator = $this->getMockBuilder( OtherProjectsSidebarGenerator::class )
			->disableOriginalConstructor()
			->getMock();

		$sidebarGenerator->expects( $this->any() )
			->method( 'buildProjectLinkSidebar' )
			->will( $this->returnValue( $projects ) );

		return $sidebarGenerator;
	}

	/**
	 * @param array $projects
	 *
	 * @return OtherProjectsSidebarGeneratorFactory
	 */
	private function getOtherProjectsSidebarGeneratorFactory( array $projects ) {
		$otherProjectsSidebarGenerator = $this->getSidebarGenerator( $projects );

		$otherProjectsSidebarGeneratorFactory = $this->getMockBuilder(
				OtherProjectsSidebarGeneratorFactory::class
			)
			->disableOriginalConstructor()
			->getMock();

		$otherProjectsSidebarGeneratorFactory->expects( $this->any() )
			->method( 'getOtherProjectsSidebarGenerator' )
			->will( $this->returnValue( $otherProjectsSidebarGenerator ) );

		return $otherProjectsSidebarGeneratorFactory;
	}

	private function newSidebarHookHandlers( array $settings = array() ) {
		$en = Language::factory( 'en' );
		$settings = $this->newSettings( $settings );

		$namespaces = $settings->getSetting( 'namespaces' );
		$namespaceChecker = new NamespaceChecker( array(), $namespaces );

		$badgeDisplay = new LanguageLinkBadgeDisplay(
			$this->getLabelDescriptionLookup(),
			array( 'Q17' => 'featured' ),
			$en
		);

		return new SidebarHookHandlers(
			$namespaceChecker,
			$badgeDisplay,
			$this->getOtherProjectsSidebarGeneratorFactory( array( 'dummy' => 'xyz' ) ),
			$settings->getSetting( 'otherProjectsLinksBeta' ),
			$settings->getSetting( 'otherProjectsLinksByDefault' )
		);
	}

	private function primeParserOutput( ParserOutput $parserOutput, array $pageProps, array $extensionData ) {
		foreach ( $pageProps as $name => $value ) {
			$parserOutput->setProperty( $name, $value );
		}

		foreach ( $extensionData as $key => $value ) {
			$parserOutput->setExtensionData( $key, $value );
		}
	}

	public function testNewFromGlobalState() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$oldSiteGroupValue = $settings->getSetting( 'siteGroup' );
		$settings->setSetting( 'siteGroup', 'NYAN' );

		$handler = SidebarHookHandlers::newFromGlobalState();
		$this->assertInstanceOf( SidebarHookHandlers::class, $handler );

		$settings->setSetting( 'siteGroup', $oldSiteGroupValue );
	}

	public function testDoOutputPageParserOutput() {
		$title = Title::makeTitle( NS_MAIN, 'Oxygen' );

		$sisterLinks = array(
			array(
				'msg' => 'wikibase-otherprojects-test',
				'class' => 'wb-otherproject-link wb-otherproject-test',
				'href' => 'http://acme.tests.com/wiki/Foo'
			),
		);

		$pageProps = array(
			'noexternallanglinks' => serialize( array( '*' ) ),
			'wikibase_item' => 'Q1',
		);

		$extData = array(
			'wikibase-otherprojects-sidebar' => $sisterLinks,
		);

		$outputProps = array(
			'noexternallanglinks' => array( '*' ),
			'wikibase_item' => 'Q1',
			'wikibase-otherprojects-sidebar' => $sisterLinks,
		);

		$handler = $this->newSidebarHookHandlers();

		$parserOutput = new ParserOutput();

		$context = new RequestContext();
		$outputPage = new OutputPage( $context );
		$outputPage->setTitle( $title );

		$this->primeParserOutput( $parserOutput, $pageProps, $extData );

		$handler->doOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertOutputPageProperties( $outputProps, $outputPage );
	}

	public function testDoSkinTemplateGetLanguageLink() {
		$badges = array(
			'en' => array(
				'class' => 'badge-Q3',
				'label' => 'Lesenswerter Artikel',
			)
		);

		$link = array(
			'href' => 'http://acme.com',
			'class' => 'foo',
		);

		$expected = array(
			'href' => 'http://acme.com',
			'class' => 'foo badge-Q3',
			'itemtitle' => 'Lesenswerter Artikel',
		);

		$languageLinkTitle = Title::makeTitle( NS_MAIN, 'Test', '', 'en' );

		$dummy = Title::makeTitle( NS_MAIN, 'Dummy' );

		$context = new RequestContext();
		$output = new OutputPage( $context );
		$output->setProperty( 'wikibase_badges', $badges );

		$handler = $this->newSidebarHookHandlers();
		$handler->doSkinTemplateGetLanguageLink( $link, $languageLinkTitle, $dummy, $output );

		$this->assertEquals( $expected, $link );
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return Skin
	 */
	private function newSkin( IContextSource $context ) {
		$skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		return $skin;
	}

	/**
	 * Call the doSidebarBeforeOutput() function on the SidebarHookHandlers object under test.
	 *
	 * @param boolean $enabled
	 * @param array|null $projects A list of projects
	 * @param string $itemId
	 *
	 * @return array The resulting sidebar array
	 */
	private function callDoSidebarBeforeOutput( $enabled, $projects, $itemId = 'Q42' ) {
		$title = Title::makeTitle( NS_MAIN, 'Oxygen' );

		$context = new RequestContext();

		$output = new OutputPage( $context );
		$output->setTitle( $title );
		$output->setProperty( 'wikibase_item', $itemId );
		$output->setProperty( 'wikibase-otherprojects-sidebar', $projects );

		$context->setOutput( $output );
		$skin = $this->newSkin( $context );

		$sidebar = array();

		$handler = $this->newSidebarHookHandlers( array(
			'otherProjectsLinksByDefault' => $enabled
		) );

		$handler->doSidebarBeforeOutput( $skin, $sidebar );
		return $sidebar;
	}

	public function testDoSidebarBeforeOutput() {
		$projects = array( 'foo' => 'bar' );
		$sidebar = $this->callDoSidebarBeforeOutput( true, $projects );

		$this->assertArrayHasKey( 'wikibase-otherprojects', $sidebar );
		$this->assertEquals( $sidebar['wikibase-otherprojects'], $projects );
	}

	public function testDoSidebarBeforeOutput_noItem() {
		$sidebar = $this->callDoSidebarBeforeOutput( true, null, null );

		$this->assertArrayNotHasKey( 'wikibase-otherprojects', $sidebar );
	}

	public function testDoSidebarBeforeOutput_empty() {
		$projects = array();
		$sidebar = $this->callDoSidebarBeforeOutput( true, $projects );

		$this->assertArrayNotHasKey( 'wikibase-otherprojects', $sidebar );
	}

	public function testDoSidebarBeforeOutput_disabled() {
		$projects = array( 'foo' => 'bar' );
		$sidebar = $this->callDoSidebarBeforeOutput( false, $projects );

		$this->assertArrayNotHasKey( 'wikibase-otherprojects', $sidebar );
	}

	public function testDoSidebarBeforeOutput_generate() {
		// If no sidebar is set, it should be generated on the fly
		$sidebar = $this->callDoSidebarBeforeOutput( true, null );

		$this->assertArrayHasKey( 'wikibase-otherprojects', $sidebar );
		$this->assertNotEmpty( $sidebar );
	}

	private function assertOutputPageProperties( $props, OutputPage $outputPage ) {
		$this->assertInternalType( 'array', $props );

		foreach ( $props as $key => $value ) {
			$this->assertEquals( $value, $outputPage->getProperty( $key ), 'OutputProperty: ' . $key );
		}
	}

}
