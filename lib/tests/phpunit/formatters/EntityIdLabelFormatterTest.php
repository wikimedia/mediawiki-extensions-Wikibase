<?php

namespace Wikibase\Test;

use Language;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Item;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Property;

/**
 * @covers Wikibase\Lib\EntityIdLabelFormatter
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group EntityIdFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdLabelFormatterTest extends \PHPUnit_Framework_TestCase {

	protected function newEntityLoader() {
		$loader = new MockRepository();

		$entity = Item::newEmpty();
		$entity->setLabel( 'en', 'foo' );
		$entity->setLabel( 'nl', 'bar' );
		$entity->setLabel( 'zh-cn', '测试' );
		$entity->setId( 42 );

		$loader->putEntity( $entity );

		return $loader;
	}

	protected function newEntityIdFormatter() {
		$options = new FormatterOptions();
		return new EntityIdFormatter( $options );
	}

	/**
	 * @since 0.4
	 *
	 * @return array
	 */
	public function validProvider() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		$argLists = array();

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'en' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'nl' );

		$argLists[] = array( new ItemId( 'q42' ), 'bar', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );

		$argLists[] = array( new ItemId( 'q42' ), 'Q42', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'de' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), '测试', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh-tw' ) ) );

		$argLists[] = array( new ItemId( 'q42' ), '測試', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'zh-tw' ), LanguageFallbackChainFactory::FALLBACK_SELF
		) );

		$argLists[] = array( new ItemId( 'q42' ), 'Q42', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'zh-tw' ),
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		) );

		$argLists[] = array( new ItemId( 'q42' ), '測試', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'sr' )
		) );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );
		$options->setOption( EntityIdLabelFormatter::OPT_LABEL_FALLBACK, EntityIdLabelFormatter::FALLBACK_EMPTY_STRING );

		$argLists[] = array( new ItemId( 'q42' ), '', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );
		$options->setOption( EntityIdLabelFormatter::OPT_LABEL_FALLBACK, EntityIdLabelFormatter::FALLBACK_EMPTY_STRING );

		$argLists[] = array( new ItemId( 'q42' ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new ItemId( 'q9001' ), 'Q9001', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new PropertyId( 'p9001' ), 'P9001', $options );

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId $entityId
	 * @param string $expectedString
	 * @param FormatterOptions $formatterOptions
	 */
	public function testParseWithValidArguments( EntityId $entityId, $expectedString, FormatterOptions $formatterOptions ) {
		$formatter = new EntityIdLabelFormatter( $formatterOptions, $this->newEntityLoader() );
		$formatter->setIdFormatter( $this->newEntityIdFormatter() );

		$formattedValue = $formatter->format( $entityId );

		$this->assertInternalType( 'string', $formattedValue );
		$this->assertEquals( $expectedString, $formattedValue );
	}

}
