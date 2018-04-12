<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit4And6Compat;
use Wikibase\Lib\DataTypeFactory;
use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

/**
 * @covers Wikibase\Lib\WikibaseSnakFormatterBuilders
 *
 * @group SnakFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuildersTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return WikibaseSnakFormatterBuilders
	 */
	private function getWikibaseSnakFormatterBuilders() {
		$p1 = new PropertyId( 'P1' );

		$valueFormatterBuilders = $this->getMockBuilder( WikibaseValueFormatterBuilders::class )
			->disableOriginalConstructor()
			->getMock();

		$valueFormatterBuilders->expects( $this->any() )
			->method( 'newStringFormatter' )
			->will( $this->returnValue( new StringFormatter() ) );

		$propertyInfoLookup = new MockPropertyInfoLookup( [
			$p1->getSerialization() => [
				PropertyInfoLookup::KEY_DATA_TYPE => 'external-id',
				PropertyInfoLookup::KEY_FORMATTER_URL => 'http://acme.com/vocab/$1',
			],
		] );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( $p1, 'external-id' );

		$dataTypeFactory = new DataTypeFactory( [ 'external-id' => 'string' ] );

		return new WikibaseSnakFormatterBuilders(
			$valueFormatterBuilders,
			$propertyInfoLookup,
			$dataTypeLookup,
			$dataTypeFactory
		);
	}

	public function provideNewExternalIdentifierFormatter() {
		$p1 = new PropertyId( 'P1' );
		$snak = new PropertyValueSnak( $p1, new StringValue( 'AB123' ) );

		return [
			[ $snak, SnakFormatter::FORMAT_PLAIN, 'AB123' ],
			[ $snak, SnakFormatter::FORMAT_WIKI, '[http://acme.com/vocab/AB123 AB123]' ],
			[ $snak, SnakFormatter::FORMAT_HTML, '<a class="wb-external-id" href="http://acme.com/vocab/AB123">AB123</a>' ],
		];
	}

	/**
	 * @dataProvider provideNewExternalIdentifierFormatter
	 */
	public function testNewExternalIdentifierFormatter( Snak $snak, $format, $expected ) {
		$options = new FormatterOptions();
		$builders = $this->getWikibaseSnakFormatterBuilders();
		$formatter = $builders->newExternalIdentifierFormatter( $format, $options );
		$actual = $formatter->formatSnak( $snak );
		$this->assertSame( $expected, $actual );
	}

	public function testNewExternalIdentifierFormatter_bad_format() {
		$options = new FormatterOptions();
		$builders = $this->getWikibaseSnakFormatterBuilders();

		$this->setExpectedException( InvalidArgumentException::class );
		$builders->newExternalIdentifierFormatter( 'unknown', $options );
	}

}
