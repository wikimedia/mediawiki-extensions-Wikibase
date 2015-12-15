<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use Language;
use MediaWikiTestCase;
use ParserOptions;
use SpecialPage;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilderFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ExternalLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutputGeneratorTest extends MediaWikiTestCase {

	private static $html = '<html>Nyan data!!!</html>';
	private static $placeholders = array( 'key' => 'value' );
	private static $configVars = array( 'foo' => 'bar' );

	public function testGetParserOutput() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = $this->newItem();
		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13044, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision, $this->getParserOptions() );

		$this->assertEquals(
			self::$html,
			$parserOutput->getText(),
			'html text'
		);

		$this->assertEquals(
			self::$placeholders,
			$parserOutput->getExtensionData( 'wikibase-view-chunks' ),
			'view chunks'
		);

		$this->assertEquals(
			self::$configVars,
			$parserOutput->getJsConfigVars(),
			'config vars'
		);

		$this->assertEquals(
			'kitten item',
			$parserOutput->getExtensionData( 'wikibase-titletext' ),
			'title text'
		);

		$this->assertEquals(
			array( 'http://an.url.com', 'https://another.url.org' ),
			array_keys( $parserOutput->getExternalLinks() ),
			'external links'
		);

		$this->assertEquals(
			array( 'File:This_is_a_file.pdf', 'File:Selfie.jpg' ),
			array_keys( $parserOutput->getImages() ),
			'images'
		);

		$expectedUsedOptions = array( 'userlang', 'editsection' );
		$actualOptions = $parserOutput->getUsedOptions();
		$missingOptions = array_diff( $expectedUsedOptions, $actualOptions );
		$this->assertEmpty(
			$missingOptions,
			'Missing cache-split flags: ' . join( '|', $missingOptions ) . '. Options: ' . join( '|', $actualOptions )
		);

		$jsonHref = SpecialPage::getTitleFor( 'EntityData', $item->getId()->getSerialization() . '.json' )->getCanonicalURL();
		$ntHref = SpecialPage::getTitleFor( 'EntityData', $item->getId()->getSerialization() . '.nt' )->getCanonicalURL();

		$this->assertEquals(
			array(
				array(
					'rel' => 'alternate',
					'href' => $jsonHref,
					'type' => 'application/json'
				),
				array(
					'rel' => 'alternate',
					'href' => $ntHref,
					'type' => 'application/n-triples'
				)
			),
			$parserOutput->getExtensionData( 'wikibase-alternate-links' ),
			'alternate links (extension data)'
		);
	}

	public function testGetParserOutput_dontGenerateHtml() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = $this->newItem();
		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13044, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision, $this->getParserOptions(), false );

		$this->assertSame( '', $parserOutput->getText() );
		// ParserOutput without HTML must not end up in the cache.
		$this->assertFalse( $parserOutput->isCacheable() );
	}

	public function testTitleText_ItemHasNolabel() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setDescription( 'en', 'a kitten' );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13045, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision, $this->getParserOptions() );

		$this->assertEquals(
			'Q7799929',
			$parserOutput->getExtensionData( 'wikibase-titletext' ),
			'title text'
		);
	}

	private function newEntityParserOutputGenerator() {
		$entityDataFormatProvider = new EntityDataFormatProvider();

		$formats = array( 'json', 'ntriples' );
		$entityDataFormatProvider->setFormatWhiteList( $formats );

		$entityTitleLookup = $this->getEntityTitleLookupMock();

		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() );

		$dataUpdaters = array(
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher ),
			new ReferencedEntitiesDataUpdater(
				$entityTitleLookup,
				new BasicEntityIdParser()
			)
		);

		return new EntityParserOutputGenerator(
			$this->getEntityViewFactory(),
			$this->getConfigBuilderMock(),
			$entityTitleLookup,
			new SqlEntityInfoBuilderFactory(),
			$this->newLanguageFallbackChain(),
			TemplateFactory::getDefaultInstance(),
			$entityDataFormatProvider,
			$dataUpdaters,
			'en'
		);
	}

	private function newLanguageFallbackChain() {
		$fallbackChain = $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( $labels ) {
				if ( array_key_exists( 'en', $labels ) ) {
					return array(
						'value' => $labels['en'],
						'language' => 'en',
						'source' => 'en'
					);
				}

				return null;
			} ) );

		return $fallbackChain;
	}

	private function newItem() {
		$item = new Item( new ItemId( 'Q7799929' ) );

		$item->setLabel( 'en', 'kitten item' );

		$statements = $item->getStatements();

		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'http://an.url.com' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'https://another.url.org' ) ) );

		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:This is a file.pdf' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:Selfie.jpg' ) ) );

		return $item;
	}

	private function getEntityViewFactory() {
		$entityViewFactory = $this->getMockBuilder( 'Wikibase\View\EntityViewFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityView = $this->getMockBuilder( 'Wikibase\View\EntityView' )
			->disableOriginalConstructor()
			->getMock();

		$entityView->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( '<html>Nyan data!!!</html>' ) );

		$entityView->expects( $this->any() )
			->method( 'getPlaceholders' )
			->will( $this->returnValue( array( 'key' => 'value' ) ) );

		$entityViewFactory->expects( $this->any() )
			->method( 'newEntityView' )
			->will( $this->returnValue( $entityView ) );

		return $entityViewFactory;
	}

	private function getConfigBuilderMock() {
		$configBuilder = $this->getMockBuilder( 'Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnValue( self::$configVars ) );

		return $configBuilder;
	}

	private function getEntityTitleLookupMock() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . ':' . $id->getSerialization()
				);
			} ) );

		return $entityTitleLookup;
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'commonsMedia' );

		return $dataTypeLookup;
	}

	private function getParserOptions() {
		return new ParserOptions( null, Language::factory( 'en' ) );
	}

}
