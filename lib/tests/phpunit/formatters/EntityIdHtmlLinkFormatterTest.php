<?php

namespace Wikibase\Test;

use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;

/**
 * @covers Wikibase\Lib\EntityIdHtmlLinkFormatter
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
class EntityIdHtmlLinkFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function validProvider() {
		$argLists = array();

		$options = new FormatterOptions();
		$options->setOption( EntityIdHtmlLinkFormatter::OPT_LANG, 'es' );

		$argLists['foo'] = array( new ItemId( 'Q42' ), 'es', '@^<a.*>foo</a>$@', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdHtmlLinkFormatter::OPT_LANG, 'en' );
		$options->setOption( EntityIdHtmlLinkFormatter::OPT_LOOKUP_LABEL, false );

		$argLists['no lookup'] = array( new EntityIdValue( new ItemId( 'Q42' ) ), 'en', '@^<a.*>Q42</a>$@', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdHtmlLinkFormatter::OPT_LANG, 'en' );

		$argLists['no label'] = array( new EntityIdValue( new ItemId( 'Q9001' ) ), 'en', '@^<a.*>Q9001</a>$@', $options );


		$options = new FormatterOptions();
		$options->setOption( EntityIdHtmlLinkFormatter::OPT_LANG, 'en' );

		$argLists['property id'] = array( new PropertyId( 'P9001' ), 'en', '@^<a.*>P9001</a>$@', $options );

		$options = new FormatterOptions();
		$options->setOption( EntityIdHtmlLinkFormatter::OPT_LANG, 'en' );

		$argLists['unresolved-redirect'] = array( new ItemId( 'Q23' ), 'en', '@^<a.*>Q23</a>$@', $options );

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId|EntityIdValue $entityId
	 * @param string $languageCode
	 * @param string $expectedPattern
	 * @param FormatterOptions $formatterOptions
	 */
	public function testParseWithValidArguments( $entityId, $languageCode, $expectedPattern,
		FormatterOptions $formatterOptions
	) {
		$labelLookup = $this->getLabelLookup( $languageCode );
		$formatter = new EntityIdHtmlLinkFormatter( $formatterOptions, $labelLookup );

		$formattedValue = $formatter->format( $entityId );

		$this->assertInternalType( 'string', $formattedValue );
		$this->assertRegExp( $expectedPattern, $formattedValue );
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
