<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use Language;
use Parser;
use ParserOptions;
use Status;
use Title;
use User;
use Wikibase\DataAccess\PropertyParserFunction\Renderer;
use Wikibase\DataAccess\PropertyParserFunction\Runner;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Test\MockPropertyLabelResolver;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\Runner
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class RunnerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param Parser $parser
	 * @param Renderer $renderer
	 * @param Entity|null $entity
	 *
	 * @return Runner
	 */
	private function getRunner( Parser $parser, Renderer $renderer, Entity $entity = null ) {
		$entityLookup = new MockRepository();

		if ( $entity !== null ) {
			$entityLookup->putEntity( $entity );
		}

		$propertyLabelResolver = new MockPropertyLabelResolver(
			$parser->getTargetLanguage(),
			$entityLookup
		);

		return new Runner(
			$entityLookup,
			$propertyLabelResolver,
			$this->getRendererFactory( $renderer ),
			$this->getSiteLinkLookup(),
			'enwiki'
		);
	}

	/**
	 * @dataProvider isParserUsingVariantsProvider
	 */
	public function testIsParserUsingVariants(
		$outputType,
		$interfaceMessage,
		$disableContentConversion,
		$disableTitleConversion,
		$expected
	) {
		$parserOptions = new ParserOptions();
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		$parser = $this->getParser( 'de' );
		$parser->startExternalParse( null, $parserOptions, $outputType );

		$runner = $this->getRunner( $parser, $this->getRenderer() );

		$this->assertEquals( $expected, $runner->isParserUsingVariants( $parser ) );
	}

	public function isParserUsingVariantsProvider() {
		return array(
			array( Parser::OT_HTML, false, false, false, true ),
			array( Parser::OT_WIKI, false, false, false, false ),
			array( Parser::OT_PREPROCESS, false, false, false, false ),
			array( Parser::OT_PLAIN, false, false, false, false ),
			array( Parser::OT_HTML, true, false, false, false ),
			array( Parser::OT_HTML, false, true, false, false ),
			array( Parser::OT_HTML, false, false, true, true ),
		);
	}

	/**
	 * @dataProvider processRenderedArrayProvider
	 */
	public function testProcessRenderedArray( $outputType, array $textArray, $expected ) {
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$runner = $this->getRunner( $parser, $this->getRenderer() );
		$this->assertEquals( $expected, $runner->processRenderedArray( $textArray ) );
	}

	public function processRenderedArrayProvider() {
		return array(
			array( Parser::OT_HTML, array(
				'zh-cn' => 'fo&#60;ob&#62;ar',
				'zh-tw' => 'FO&#60;OB&#62;AR',
			), '-{zh-cn:fo&#60;ob&#62;ar;zh-tw:FO&#60;OB&#62;AR;}-' ),
			// Don't create "-{}-" for empty input,
			// to keep the ability to check a missing property with {{#if: }}.
			array( Parser::OT_HTML, array(), '' ),
		);
	}

	public function testRenderInLanguage() {
		$runner = $this->getRunner(
			$this->getParser( 'es' ),
			$this->getRenderer()
		);

		$language = Language::factory( 'he' );
		$result = $runner->renderInLanguage( new ItemId( 'Q3' ), 'gato', $language );

		$this->assertEquals( 'meow!', $result );
	}

	private function getRenderer() {
		$entityRenderer = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\Renderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$entityRenderer->expects( $this->any() )
			->method( 'renderForEntityId' )
			->will( $this->returnValue( Status::newGood( 'meow!' ) ) );

		return $entityRenderer;
	}

	public function testRenderForPropertyNotFound() {
		$runner = $this->getRunner(
			$this->getParser( 'qqx' ),
			$this->getRendererForPropertyNotFound()
		);

		$language = Language::factory( 'qqx' );
		$result = $runner->renderInLanguage( new ItemId( 'Q4' ), 'invalidLabel', $language );

		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	private function getRendererForPropertyNotFound() {
		$entityRenderer = $this->getRenderer();

		$entityRenderer->expects( $this->any() )
			->method( 'renderForEntityId' )
			->will( $this->returnCallback( function() {
				throw new PropertyLabelNotResolvedException( 'invalidLabel', 'qqx' );
			} ) );

		return $entityRenderer;
	}

	private function getSiteLinkLookup() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( new ItemId( 'Q3' ) ) );

		return $siteLinkLookup;
	}

	private function getRendererFactory( Renderer $renderer ) {
		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\RendererFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$rendererFactory->expects( $this->any() )
			->method( 'newFromLanguage' )
			->will( $this->returnValue( $renderer ) );

		return $rendererFactory;
	}

	private function getParser( $languageCode ) {
		$parserConfig = array( 'class' => 'Parser' );
		$parser = new Parser( $parserConfig );

		$parser->setTitle( Title::newFromText( 'Cat' ) );

		$language = Language::factory( $languageCode );
		$parserOptions = new ParserOptions( User::newFromId( 0 ), $languageCode );
		$parserOptions->setTargetLanguage( $language );

		$parser->startExternalParse( null, $parserOptions, Parser::OT_WIKI );

		return $parser;
	}

}
