<?php

namespace Wikibase\Test;

use Language;
use ValueFormatters\FormatterOptions;
use Wikibase\EntityId;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Item;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Property;

/**
 * Unit tests for the Wikibase\EntityIdLabelFormatter class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
		$options->setOption(
			EntityIdFormatter::OPT_PREFIX_MAP,
			array(
				 Item::ENTITY_TYPE => 'I',
				 Property::ENTITY_TYPE => 'PropERTY',
			)
		);

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

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'en' ) ) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'nl' );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'bar', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'I42', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'de' ) ) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) ) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), '测试', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh-tw' ) ) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), '測試', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'zh-tw' ), LanguageFallbackChainFactory::FALLBACK_SELF
		) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'I42', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'zh-tw' ),
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), '測試', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, $languageFallbackChainFactory->newFromLanguage(
			Language::factory( 'sr' )
		) );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );
		$options->setOption( EntityIdLabelFormatter::OPT_LABEL_FALLBACK, EntityIdLabelFormatter::FALLBACK_EMPTY_STRING );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), '', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );
		$options->setOption( EntityIdLabelFormatter::OPT_LABEL_FALLBACK, EntityIdLabelFormatter::FALLBACK_EMPTY_STRING );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'foo', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 9001 ), 'I9001', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new EntityId( Property::ENTITY_TYPE, 9001 ), 'PropERTY9001', $options );

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
