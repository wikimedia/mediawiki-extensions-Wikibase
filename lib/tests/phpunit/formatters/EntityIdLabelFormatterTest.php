<?php

namespace Wikibase\Test;

use Language;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\LabelLookup;

/**
 * @covers Wikibase\Lib\EntityIdLabelFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group EntityIdFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function validProvider() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		$argLists = array();

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new ItemId( 'Q42' ), 'foo', 'en', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage( Language::factory( 'en' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', 'en', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'nl' );

		$argLists[] = array( new ItemId( 'Q42' ), 'bar', 'nl', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );

		$argLists[] = array( new ItemId( 'Q42' ), 'Q42', 'de', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage( Language::factory( 'de' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', 'de', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), '测试', 'zh', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh-tw' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), '測試', 'zh-tw', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'zh-tw' ), LanguageFallbackChainFactory::FALLBACK_SELF
		) );

		$argLists[] = array( new ItemId( 'q42' ), 'Q42', 'zh-tw', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'zh-tw' ),
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		) );

		$argLists[] = array( new ItemId( 'q42' ), '測試', 'zh-tw', $options );


		$options = new FormatterOptions();
		$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'sr' )
		) );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', 'sr', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );
		$options->setOption( EntityIdLabelFormatter::OPT_LABEL_FALLBACK, EntityIdLabelFormatter::FALLBACK_EMPTY_STRING );

		$argLists[] = array( new EntityIdValue( new ItemId( 'Q42' ) ), '', 'de', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );
		$options->setOption( EntityIdLabelFormatter::OPT_LOOKUP_LABEL, false );

		$argLists[] = array( new EntityIdValue( new ItemId( 'Q42' ) ), 'Q42', 'en', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new EntityIdValue( new ItemId( 'Q9001' ) ), 'Q9001', 'en', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new PropertyId( 'P9001' ), 'P9001', 'en', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists['unresolved-redirect'] = array( new ItemId( 'Q23' ), 'Q23', 'en', $options );

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId|EntityIdValue $entityId
	 * @param string $expectedString
	 * @param FormatterOptions $formatterOptions
	 */
	public function testParseWithValidArguments( $entityId, $expectedString,
		$languageCode, FormatterOptions $formatterOptions
	) {
		$labelLookup = new LabelLookup( $this->getTermLookup(), $languageCode );
		$formatter = new EntityIdLabelFormatter( $formatterOptions, $labelLookup );

		$formattedValue = $formatter->format( $entityId );

		$this->assertInternalType( 'string', $formattedValue );
		$this->assertEquals( $expectedString, $formattedValue );
	}

	private function getTermLookup() {
		$terms = array(
			new \Wikibase\Term( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'foo',
				'entityId' => 42,
				'entityType' => 'item',
			) ),
			new \Wikibase\Term( array(
				'termType' => 'label',
				'termLanguage' => 'nl',
				'termText' => 'bar',
				'entityId' => 42,
				'entityType' => 'item',
			) ),
			new \Wikibase\Term( array(
				'termType' => 'label',
				'termLanguage' => 'zh-cn',
				'termText' => '测试',
				'entityId' => 42,
				'entityType' => 'item',
			) )
		);

		return new MockTermIndex( $terms );
	}

}
