<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\ParserErrorMessageFormatter;
use Wikibase\Property;
use Wikibase\PropertyParserFunction;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\PropertyParserFunction
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private function getDefaultInstance() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$targetLanguage = \Language::factory( 'en' );
		$errorFormatter = new ParserErrorMessageFormatter( $targetLanguage );
		$dataTypeFactory = $wikibaseClient->getDataTypeFactory();
		$mockRepo = $this->newMockRepository();
		$mockResolver = new MockPropertyLabelResolver( $targetLanguage->getCode(), $mockRepo );

		$formatter = new SnakFormatter(
			new EntityRetrievingDataTypeLookup( $mockRepo ),
			new TypedValueFormatter(),
			$dataTypeFactory
		);

		return new PropertyParserFunction(
			$targetLanguage,
			$mockRepo,
			$mockResolver,
			$errorFormatter,
			$formatter
		);
	}

	private function newMockRepository() {
		$propertyId = new PropertyId( 'P1337' );

		$entityLookup = new MockRepository();

		$item = Item::newEmpty();
		$item->setId( 42 );
		$item->addClaim( new Claim( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'Please write tests before merging your code' )
		) ) );
		$item->addClaim( new Claim( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'or kittens will die' )
		) ) );

		$property = Property::newEmpty();
		$property->setId( $propertyId );

		$property->setDataTypeId( 'string' );
		$property->setLabel( 'en', 'kitten' );

		$entityLookup->putEntity( $item );
		$entityLookup->putEntity( $property );

		return $entityLookup;
	}

	public static function provideRenderForEntityId() {
		return array(
			array(
				'p1337',
				'Please write tests before merging your code, or kittens will die',
				'Congratulations, you just killed a kitten'
			),
			array(
				'kitten',
				'Please write tests before merging your code, or kittens will die',
				'Congratulations, you just killed a kitten'
			),
		);
	}

	/**
	 * @dataProvider provideRenderForEntityId
	 */
	public function testRenderForEntityId( $name, $expected, $info ) {
		$parserFunction = $this->getDefaultInstance();

		$status = $parserFunction->renderForEntityId(
			new ItemId( 'Q42' ),
			$name
		);

		$this->assertTrue( $status->isOK() );

		$text = $status->getValue();
		$this->assertInternalType( 'string', $text );

		$this->assertEquals(
			$expected,
			$text,
			$info
		);
	}

	public function testGetInstance() {
		$parser = new \Parser();
		$parser->startExternalParse();
		$instance = PropertyParserFunction::getInstance( $parser );
		$this->assertInstanceOf( 'Wikibase\PropertyParserFunction', $instance );
	}

	/**
	 * @dataProvider provideIsParserUsingVariants
	 */
	public function testIsParserUsingVariants(
		$outputType, $interfaceMessage, $disableContentConversion, $disableTitleConversion, $expected
	) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$this->assertEquals( $expected, PropertyParserFunction::isParserUsingVariants( $parser ) );
	}

	public function provideIsParserUsingVariants() {
		return array(
			array( \Parser::OT_HTML, false, false, false, true ),
			array( \Parser::OT_WIKI, false, false, false, false ),
			array( \Parser::OT_PREPROCESS, false, false, false, false ),
			array( \Parser::OT_PLAIN, false, false, false, false ),
			array( \Parser::OT_HTML, true, false, false, false ),
			array( \Parser::OT_HTML, false, true, false, false ),
			array( \Parser::OT_HTML, false, false, true, true ),
		);
	}

	/**
	 * @dataProvider provideProcessRenderedText
	 */
	public function testProcessRenderedText(
		$outputType, $interfaceMessage, $disableContentConversion, $disableTitleConversion,
		$language, $text, $rawExpected, $markedNoConversion
	) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );
		$languageObject = Language::factory( $language );
		$parserOptions->setTargetLanguage( $languageObject );
		$parser->startExternalParse( null, $parserOptions, $outputType );
		if ( $markedNoConversion ) {
			$expected = $languageObject->getConverter()->markNoConversion( $rawExpected );
		} else {
			$expected = $rawExpected;
		}
		$this->assertEquals( $rawExpected, PropertyParserFunction::processRenderedText( $parser, $text ) );
	}

	public function provideProcessRenderedText() {
		return array(
			array(
				\Parser::OT_HTML, false, false, false
				'en', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', true
		       	),
			array(
				\Parser::OT_HTML, false, false, false
				'zh', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', true
		       	),
			array(
				\Parser::OT_WIKI, false, false, false
				'en', 'fo<b>ob</b>ar', 'fo<b>ob</b>ar', false
		       	),
			array(
				\Parser::OT_WIKI, false, false, false
				'zh', 'fo<b>ob</b>ar', 'fo<b>ob</b>ar', false
		       	),
			array(
				\Parser::OT_PREPROCESS, false, false, false
				'en', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', false
		       	),
			array(
				\Parser::OT_PREPROCESS, false, false, false
				'zh', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', false
		       	),
			array(
				\Parser::OT_PLAIN, false, false, false
				'en', 'fo<b>ob</b>ar', 'fo<b>ob</b>ar', false
		       	),
			array(
				\Parser::OT_PLAIN, false, false, false
				'zh', 'fo<b>ob</b>ar', 'fo<b>ob</b>ar', false
		       	),
			array(
				\Parser::OT_HTML, true, false, false
				'en', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', false
		       	),
			array(
				\Parser::OT_HTML, true, false, false
				'zh', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', false
		       	),
			array(
				\Parser::OT_HTML, false, true, false
				'en', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', false
		       	),
			array(
				\Parser::OT_HTML, false, true, false
				'zh', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', false
		       	),
			array(
				\Parser::OT_HTML, false, false, true
				'en', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', true
		       	),
			array(
				\Parser::OT_HTML, false, false, true
				'zh', 'fo<b>ob</b>ar', 'fo&lt;b&gt;ob&lt;/b&gt;ar', true
		       	),
		);
	}

}
