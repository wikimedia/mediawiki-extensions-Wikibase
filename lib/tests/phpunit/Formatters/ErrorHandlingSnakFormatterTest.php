<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Exception;
use MediaWikiIntegrationTestCase;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\ErrorHandlingSnakFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\ErrorHandlingSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ErrorHandlingSnakFormatterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param Exception|null $throw
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $throw = null ) {
		$formatter = $this->createMock( SnakFormatter::class );

		$formatter->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_HTML );

		if ( $throw ) {
			$formatter->method( 'formatSnak' )
				->willThrowException( $throw );
		} else {
			$formatter->method( 'formatSnak' )
				->willReturn( 'SNAK' );
		}

		return $formatter;
	}

	/**
	 * @return ValueFormatter
	 */
	private function getValueFormatter() {
		$formatter = $this->createMock( ValueFormatter::class );

		$formatter->method( 'format' )
			->willReturn( 'VALUE' );

		return $formatter;
	}

	public function testFormatSnak_good() {
		$formatter = new ErrorHandlingSnakFormatter( $this->getSnakFormatter(), null, null );

		$snak = new PropertyNoValueSnak(
			new NumericPropertyId( 'P1' )
		);

		$text = $formatter->formatSnak( $snak );
		$this->assertEquals( 'SNAK', $text );
	}

	public function provideFormatSnak_error() {
		$p1 = new NumericPropertyId( 'P1' );

		$valueFormatter = $this->getValueFormatter();

		return [
			'MismatchingDataValueTypeException' => [
				'<span class="error wb-format-error">(wikibase-snakformatter-valuetype-mismatch: string, number)</span>',
				new PropertyValueSnak( $p1, new StringValue( 'foo' ) ),
				new MismatchingDataValueTypeException( 'number', 'string' ),
			],
			'MismatchingDataValueTypeException+fallback' => [
				'VALUE <span class="error wb-format-error">(wikibase-snakformatter-valuetype-mismatch: string, number)</span>',
				new PropertyValueSnak( $p1, new StringValue( 'foo' ) ),
				new MismatchingDataValueTypeException( 'number', 'string' ),
				$valueFormatter,
			],
			'MismatchingDataValueTypeException+UnDeserializableValue' => [
				'<span class="error wb-format-error">(wikibase-undeserializable-value)</span>',
				new PropertyValueSnak( $p1, new UnDeserializableValue( 'string', 'XYZ', 'test' ) ),
				new MismatchingDataValueTypeException( 'number', UnDeserializableValue::getType() ),
			],
			'MismatchingDataValueTypeException+UnDeserializableValue+fallback' => [
				'VALUE <span class="error wb-format-error">(wikibase-undeserializable-value)</span>',
				new PropertyValueSnak( $p1, new UnDeserializableValue( 'string', 'XYZ', 'test' ) ),
				new MismatchingDataValueTypeException( 'number', UnDeserializableValue::getType() ),
				$valueFormatter,
			],
			'PropertyDataTypeLookupException' => [
				'<span class="error wb-format-error">(wikibase-snakformatter-property-not-found: P1)</span>',
				new PropertyValueSnak( $p1, new StringValue( 'foo' ) ),
				new PropertyDataTypeLookupException( new NumericPropertyId( 'P1' ) ),

			],
			'PropertyDataTypeLookupException+fallback' => [
				'VALUE <span class="error wb-format-error">(wikibase-snakformatter-property-not-found: P1)</span>',
				new PropertyValueSnak( $p1, new StringValue( 'foo' ) ),
				new PropertyDataTypeLookupException( new NumericPropertyId( 'P1' ) ),
				$valueFormatter,
			],
			'FormattingException' => [
				'<span class="error wb-format-error">(wikibase-snakformatter-formatting-exception: TEST)</span>',
				new PropertyValueSnak( $p1, new StringValue( 'foo' ) ),
				new FormattingException( 'TEST' ),
			],
		];
	}

	/**
	 * @dataProvider provideFormatSnak_error
	 */
	public function testFormatSnak_error(
		$expected,
		PropertyValueSnak $snak,
		Exception $ex,
		ValueFormatter $fallbackFormatter = null
	) {
		$formatter = new ErrorHandlingSnakFormatter(
			$this->getSnakFormatter( $ex ),
			$fallbackFormatter,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' )
		);

		$text = $formatter->formatSnak( $snak );
		$this->assertEquals( $expected, $text );
	}

}
