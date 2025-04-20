<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use DataValues\StringValue;
use MediaWiki\Extension\Scribunto\ScribuntoContent;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\Parsoid\Config\SiteConfig;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Parsoid\Config\PageConfig as IPageConfig;
use Wikimedia\Parsoid\Config\StubMetadataCollector;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;

/**
 * @coversNothing
 *
 * @group Wikibase
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Arthur Taylor
 */
class ParsoidUsageTrackingTest extends \MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'Scribunto' );
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseRepository' );

		$this->setService( 'WikibaseClient.SiteGroup', 'test' );

		$this->registerModuleFixture( "Wikidata.lua", "Wikidata" );
	}

	private function getPageConfig(): IPageConfig {
		$pageConfigFactory = MediaWikiServices::getInstance()->getParsoidPageConfigFactory();
		$pageIdentity = $this->getNonexistingTestPage(); // arbitrary
		return $pageConfigFactory->createFromParserOptions(
			ParserOptions::newFromAnon(),
			$pageIdentity
		);
	}

	private function parseText(
		string $text,
		ContentMetadataCollector $contentMetadataCollector
	): string {
		$dataAccess = MediaWikiServices::getInstance()->getParsoidDataAccess();
		return $dataAccess->parseWikitext(
			$this->getPageConfig(),
			$contentMetadataCollector,
			$text
		);
	}

	private function getContentMetadataCollector() {
		return new StubMetadataCollector(
			$this->createMock( SiteConfig::class )
		);
	}

	public function testDataAccessNullRender() {
		$this->createAndRegisterEntity( "Q42" );
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $this->parseText( "dummytext", $contentMetadataCollector );
		$this->assertEquals( "<p>dummytext\n</p>", $parseResult );
		$this->assertNull(
			$contentMetadataCollector->getExtensionData( ParserOutputUsageAccumulator::EXTENSION_DATA_KEY )
		);
	}

	private function createAndRegisterEntity( string $name ): EntityDocument {
		$entityId = new ItemId( $name );
		$item = new Item(
			$entityId
		);
		$revision = WikibaseRepo::getEntityStore( $this->getServiceContainer() )
			->saveEntity( $item, '', $this->getTestUser()->getUser() );
		return $revision->getEntity();
	}

	private function addStatementToItem( EntityDocument $item, EntityId $property, string $statement ) {
		$value = new StringValue( $statement );
		$snak = new PropertyValueSnak( $property, $value );
		$statement = new Statement( $snak );
		$item->getStatements()->addStatement( $statement );
		WikibaseRepo::getEntityStore( $this->getServiceContainer() )
			->saveEntity( $item, '', $this->getTestUser()->getUser() );
	}

	private function createAndRegisterProperty( string $propertyName ): EntityId {
		$propertyId = new NumericPropertyId( $propertyName );
		$property = new Property( $propertyId, null, 'string' );
		$revision = WikibaseRepo::getEntityStore( $this->getServiceContainer() )
			->saveEntity( $property, '', $this->getTestUser()->getUser() );
		return $revision->getEntity()->getId();
	}

	private function registerModuleFixture( string $filename, string $moduleName ) {
		$templateText = file_get_contents( implode( DIRECTORY_SEPARATOR, [ __DIR__, "fixtures", $filename ] ) );
		$this->registerModule( $moduleName, $templateText );
	}

	private function registerModule( string $moduleName, string $moduleText ) {
		$status = $this->editPage( $moduleName, new ScribuntoContent( $moduleText ), '', NS_MODULE );
		$this->assertTrue( $status->wasRevisionCreated() );
	}

	private function registerTemplate( string $templateName, string $templateText ) {
		$status = $this->editPage( $templateName, $templateText, '', NS_TEMPLATE );
		$this->assertTrue( $status->wasRevisionCreated() );
	}

	private function assertExpectedUsages(
		array $expectedUsages, ContentMetadataCollector $contentMetadataCollector
	) {
		sort( $expectedUsages );
		$actualUsages = $contentMetadataCollector->getExtensionData( ParserOutputUsageAccumulator::EXTENSION_DATA_KEY );
		$this->assertNotNull( $actualUsages );
		sort( $actualUsages );
		$this->assertArrayEquals( $expectedUsages, $actualUsages );
	}

	public function testRenderLinkWithUsages() {
		$item = $this->createAndRegisterEntity( "Q42" );
		$property = $this->createAndRegisterProperty( "P509" );
		$testSnakValue = "Test Snak Value";
		$this->addStatementToItem( $item, $property, $testSnakValue );
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $this->parseText(
			"{{#invoke:Wikidata|claim|P509|id={$item->getId()->getSerialization()}|showerrors=true}}",
			$contentMetadataCollector
		);
		$this->assertEquals( "<p>" . $testSnakValue . "\n</p>", $parseResult );
		$this->assertExpectedUsages( [ "Q42#C.P509" ], $contentMetadataCollector );
	}

	public function testRenderTextTemplate() {
		$item = $this->createAndRegisterEntity( "Q42" );
		$this->registerTemplate( "Character Information", "This is a character" );
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $this->parseText(
			"{{Character Information}}",
			$contentMetadataCollector
		);
		$this->assertEquals( "<p>This is a character\n</p>", $parseResult );
		$this->assertNull(
			$contentMetadataCollector->getExtensionData( ParserOutputUsageAccumulator::EXTENSION_DATA_KEY )
		);
	}

	public function testRenderLinkUsageWithinTemplate() {
		$item = $this->createAndRegisterEntity( "Q42" );
		$property = $this->createAndRegisterProperty( "P509" );
		$testSnakValue = "Test Snak Value";
		$this->addStatementToItem( $item, $property, $testSnakValue );
		$this->registerTemplate( "Character Information",
			"{{#invoke:Wikidata|claim|P509|id={$item->getId()->getSerialization()}|showerrors=true}}" );
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $this->parseText(
			"{{Character Information}}",
			$contentMetadataCollector
		);
		$this->assertEquals( "<p>" . $testSnakValue . "\n</p>", $parseResult );
		$this->assertExpectedUsages( [ "Q42#C.P509" ], $contentMetadataCollector );
	}

	public function testRenderLinkUsageWithinNestedTemplate() {
		$item = $this->createAndRegisterEntity( "Q42" );
		$property = $this->createAndRegisterProperty( "P509" );
		$testSnakValue = "Test Snak Value";
		$this->addStatementToItem( $item, $property, $testSnakValue );
		$this->registerTemplate( "Character Details",
			"{{#invoke:Wikidata|claim|P509|id={$item->getId()->getSerialization()}|showerrors=true}}" );
		$this->registerTemplate( "Character Information",
			"{{Character Details}}" );
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $this->parseText(
			"{{Character Information}}",
			$contentMetadataCollector
		);
		$this->assertEquals( "<p>" . $testSnakValue . "\n</p>", $parseResult );
		$this->assertExpectedUsages( [ "Q42#C.P509" ], $contentMetadataCollector );
	}

	/**
	 * In this test, we want to reproduce bug T255706, where usages were not being tracked
	 * correctly. With the current (22/01/2024) implementation of Parsoid there is a
	 * mismatch between calls to `parser->resetOutput` (Parsoid::Config::DataAccess) and
	 * calls to `parser->clearState` (Parsoid::Parser) (which also calls
	 * `parser->resetOutput`). `clearState` is only called when the reference identity
	 * comparison for the page objects between two subsequent parses fails (i.e. they are
	 * different page objects) - this clears all the state and resets any singletons, as
	 * well as calling `resetOutput`. `resetOutput` is called every time a new parse
	 * begins.
	 *
	 * What this means for the `WikibaseEntityLibrary` is that it incorrectly
	 * retains a reference to an old `ParserOutput` when two parses are consecutively in
	 * the context of the same page.
	 */
	public function testRenderLinkUsageWithinTwoSeparateTemplates() {
		$item = $this->createAndRegisterEntity( "Q42" );
		$property1 = $this->createAndRegisterProperty( "P509" );
		$testSnakValue1 = "Test Snak Value";
		$this->addStatementToItem( $item, $property1, $testSnakValue1 );
		$property2 = $this->createAndRegisterProperty( "P510" );
		$testSnakValue2 = "Test Snak Value 2";
		$this->addStatementToItem( $item, $property2, $testSnakValue2 );
		$this->registerTemplate(
			"Character Information",
			"{{#invoke:Wikidata|claim|P509|id={$item->getId()->getSerialization()}|showerrors=true}}"
		);
		$this->registerTemplate(
			"Character Details",
			"{{#invoke:Wikidata|claim|P510|id={$item->getId()->getSerialization()}|showerrors=true}}"
		);

		// We want to use the same $dataAccess for both parses
		$dataAccess = MediaWikiServices::getInstance()->getParsoidDataAccess();
		// And we want to pass them the same $pageConfig
		$pageConfig = $this->getPageConfig();

		// First parse
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $dataAccess->parseWikitext(
			$pageConfig,
			$contentMetadataCollector,
			"{{Character Information}}"
		);
		$this->assertEquals( "<p>" . $testSnakValue1 . "\n</p>", $parseResult );

		// The first parse logs the property reference
		// Entity usage tracking is missing here because the resolution is stubbed.
		$this->assertExpectedUsages( [ "Q42#C.P509" ], $contentMetadataCollector );

		// Second parse
		$contentMetadataCollector = $this->getContentMetadataCollector();
		$parseResult = $dataAccess->parseWikitext(
			$pageConfig,
			$contentMetadataCollector,
			"{{Character Details}}"
		);
		$this->assertEquals( "<p>" . $testSnakValue2 . "\n</p>", $parseResult );

		// The second parse logs the second property
		$this->assertExpectedUsages( [ "Q42#C.P510" ], $contentMetadataCollector );
	}
}
