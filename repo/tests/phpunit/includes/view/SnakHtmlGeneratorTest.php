<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use DataValues\TimeValue;
use Language;
use Title;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\View\SnakHtmlGenerator;

/**
 * @covers Wikibase\SnakHtmlGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SnakHtmlGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getSnakHtmlProvider
	 */
	public function testGetSnakHtml(
		$snakFormatter,
		$entityTitleLookup,
		$propertyLabels,
		$snak,
		$cssPattern,
		$formattedSnak
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup,
			Language::factory( 'qqx' )
		);

		$html = $snakHtmlGenerator->getSnakHtml( $snak, $propertyLabels );

		$this->assertRegExp( $cssPattern, $html, 'has snak value css' );
		$this->assertContains( $formattedSnak, $html, 'has formatted snak' );
	}

	public function getSnakHtmlProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$entityTitleLookupMock = $this->getEntityTitleLookupMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			array(),
			new PropertySomeValueSnak( 42 ),
			'/wb-snakview-variation-somevalue/',
			'a snak!'
		);

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			array(),
			new PropertySomeValueSnak( 42 ),
			'/wb-snakview-variation-somevalue/',
			'a snak!'
		);

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			array(),
			new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
			'/wb-snakview-variation-value/',
			'a snak!'
		);

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			array(),
			new PropertyValueSnak( 51, new StringValue( 'cake!' ) ),
			'/wb-snakview-variation-valuesnak-datavaluetypemismatch/',
			'wikibase-snakview-variation-datavaluetypemismatch-details: string, time'
		);

		return $testCases;
	}

	/**
	 * @return DispatchingSnakFormatter
	 */
	protected function getSnakFormatterMock() {
		$snakFormatter = $this->getMockBuilder( 'Wikibase\Lib\DispatchingSnakFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback( function( $snak ) {
				if ( $snak->getType() === 'value' ) {
					// mismatching
					$propertyId = $snak->getPropertyId()->getSerialization();

					if ( $propertyId === 'P51' ) {
						throw new MismatchingDataValueTypeException(
							TimeValue::getType(),
							StringValue::getType(),
							'Data value type mismatch. Expected a StringValue.'
						);
					} else {
						return 'a snak!';
					}
				} else {
					return 'a snak!';
				}
			} )
		);

		return $snakFormatter;
	}

	/**
	 * @param EntityId $id
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

}
