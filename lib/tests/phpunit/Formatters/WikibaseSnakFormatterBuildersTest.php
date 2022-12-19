<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

/**
 * @covers \Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders
 *
 * @group SnakFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuildersTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return WikibaseSnakFormatterBuilders
	 */
	private function getWikibaseSnakFormatterBuilders() {
		$p1 = new NumericPropertyId( 'P1' );

		$valueFormatterBuilders = $this->createMock( WikibaseValueFormatterBuilders::class );

		$valueFormatterBuilders->method( 'newStringFormatter' )
			->willReturn( new StringFormatter() );

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
		$p1 = new NumericPropertyId( 'P1' );
		$snak = new PropertyValueSnak( $p1, new StringValue( 'AB123' ) );

		return [
			[ $snak, SnakFormatter::FORMAT_PLAIN, 'AB123' ],
			[ $snak, SnakFormatter::FORMAT_WIKI, '[http://acme.com/vocab/AB123 AB123]' ],
			[
				$snak, SnakFormatter::FORMAT_HTML,
				'<a class="wb-external-id external" href="http://acme.com/vocab/AB123" rel="nofollow">AB123</a>',
			],
		];
	}

	/**
	 * @dataProvider provideNewExternalIdentifierFormatter
	 */
	public function testNewExternalIdentifierFormatter( Snak $snak, $format, $expected ) {
		$builders = $this->getWikibaseSnakFormatterBuilders();
		$formatter = $builders->newExternalIdentifierFormatter( $format );
		$actual = $formatter->formatSnak( $snak );
		$this->assertSame( $expected, $actual );
	}

	public function testNewExternalIdentifierFormatter_bad_format() {
		$options = new FormatterOptions();
		$builders = $this->getWikibaseSnakFormatterBuilders();

		$this->expectException( InvalidArgumentException::class );
		$builders->newExternalIdentifierFormatter( 'unknown' );
	}

}
