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
use Wikibase\Lib\Store\CachingLanguageLabelLookup;
use Wikibase\Lib\Store\EntityRedirect;

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
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityIdLabelFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function validProvider() {
		$argLists = array();

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'es' );

		$argLists[] = array( new ItemId( 'Q42' ), 'es', 'foo', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'de' );
		$options->setOption(
			EntityIdLabelFormatter::OPT_LABEL_FALLBACK,
			EntityIdLabelFormatter::FALLBACK_EMPTY_STRING
		);

		$argLists[] = array( new EntityIdValue( new ItemId( 'Q42' ) ), 'de', '', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );
		$options->setOption( EntityIdLabelFormatter::OPT_LOOKUP_LABEL, false );

		$argLists[] = array( new EntityIdValue( new ItemId( 'Q42' ) ), 'en', 'Q42', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new EntityIdValue( new ItemId( 'Q9001' ) ), 'en', 'Q9001', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists[] = array( new PropertyId( 'P9001' ), 'en', 'P9001', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdLabelFormatter::OPT_LANG, 'en' );

		$argLists['unresolved-redirect'] = array( new ItemId( 'Q23' ), 'en', 'Q23', $options );

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId|EntityIdValue $entityId
	 * @param string $languageCode
	 * @param string $expectedString
	 * @param FormatterOptions $formatterOptions
	 */
	public function testParseWithValidArguments( $entityId, $languageCode, $expectedString,
		FormatterOptions $formatterOptions
	) {
		$labelLookup = $this->getLabelLookup( $languageCode );
		$formatter = new EntityIdLabelFormatter( $formatterOptions, $labelLookup );

		$formattedValue = $formatter->format( $entityId );

		$this->assertInternalType( 'string', $formattedValue );
		$this->assertEquals( $expectedString, $formattedValue );
	}

	protected function getLabelLookup( $languageCode ) {
		$labelLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\LabelLookup' )
			->disableOriginalConstructor()
			->getMock();

		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $entityId ) use ( $languageCode ) {
				if ( $entityId->getSerialization() === 'Q42' && $languageCode === 'es' ) {
					return 'foo';
				} else {
					throw new \OutOfBoundsException( 'Label not found' );
				}
			} ) );

		return $labelLookup;
	}

}
