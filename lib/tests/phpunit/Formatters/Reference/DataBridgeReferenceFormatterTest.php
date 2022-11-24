<?php

namespace Wikibase\Lib\Tests\Formatters\Reference;

use DataValues\MonolingualTextValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MockMessageLocalizer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\Reference\DataBridgeReferenceFormatter;
use Wikibase\Lib\Formatters\Reference\WellKnownReferenceProperties;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\Reference\DataBridgeReferenceFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataBridgeReferenceFormatterTest extends TestCase {

	private $referenceUrlPropertyId;
	private $titlePropertyId;
	private $statedInPropertyId;
	private $authorPropertyId;
	private $publisherPropertyId;
	private $publicationDatePropertyId;
	private $retrievedDatePropertyId;
	private $otherPropertyId1;
	private $otherPropertyId2;

	protected function setUp(): void {
		parent::setUp();

		$this->referenceUrlPropertyId = new NumericPropertyId( 'P1' );
		$this->titlePropertyId = new NumericPropertyId( 'P2' );
		$this->statedInPropertyId = new NumericPropertyId( 'P3' );
		$this->authorPropertyId = new NumericPropertyId( 'P4' );
		$this->publisherPropertyId = new NumericPropertyId( 'P5' );
		$this->publicationDatePropertyId = new NumericPropertyId( 'P6' );
		$this->retrievedDatePropertyId = new NumericPropertyId( 'P7' );
		$this->otherPropertyId1 = new NumericPropertyId( 'P8' );
		$this->otherPropertyId2 = new NumericPropertyId( 'P9' );
	}

	private function stringValueSnak( PropertyId $propertyId, string $string ): Snak {
		return new PropertyValueSnak( $propertyId, new StringValue( $string ) );
	}

	private function stringSnakFormatter(): SnakFormatter {
		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback( function ( PropertyValueSnak $snak ) {
				return (string)$snak->getDataValue()->getValue();
			} );
		return $snakFormatter;
	}

	private function stringOrMonolingualTextSnakFormatter(): SnakFormatter {
		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback( function ( PropertyValueSnak $snak ) {
				$value = $snak->getDataValue();
				if ( $value instanceof StringValue ) {
					return $this->stringSnakFormatter()->formatSnak( $snak );
				} elseif ( $value instanceof MonolingualTextValue ) {
					return "<span lang=\"{$value->getLanguageCode()}\">{$value->getText()}</span>";
				} else {
					throw new InvalidArgumentException( 'Unexpected value' );
				}
			} );
		return $snakFormatter;
	}

	private function stringOrOtherSnakTypeSnakFormatter(): SnakFormatter {
		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback( function ( Snak $snak ) {
				if ( $snak instanceof PropertyValueSnak ) {
					return $this->stringSnakFormatter()->formatSnak( $snak );
				} else {
					return $snak->getType();
				}
			} );
		return $snakFormatter;
	}

	public function testEmptyReference() {
		$reference = new Reference();
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->createMock( SnakFormatter::class ),
			WellKnownReferenceProperties::newFromArray( [] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame( '', $wikitext );
	}

	public function testReferenceWithUnknownProperties() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->otherPropertyId1, 'string 1' ),
			$this->stringValueSnak( $this->otherPropertyId2, 'string 2' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'string 1(wikibase-reference-formatter-snak-separator)' .
			'string 2(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithWellKnownAndUnknownProperties() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->otherPropertyId2, 'other 2' ),
			$this->stringValueSnak( $this->retrievedDatePropertyId, 'retrieved date' ),
			$this->stringValueSnak( $this->publicationDatePropertyId, 'publication date' ),
			$this->stringValueSnak( $this->authorPropertyId, 'author' ),
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'reference URL' ),
			$this->stringValueSnak( $this->publisherPropertyId, 'publisher' ),
			$this->stringValueSnak( $this->otherPropertyId1, 'other 1' ),
			$this->stringValueSnak( $this->titlePropertyId, 'title 1' ),
			$this->stringValueSnak( $this->statedInPropertyId, 'stated in' ),
			$this->stringValueSnak( $this->titlePropertyId, 'title 2' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
				'statedIn' => $this->statedInPropertyId->getSerialization(),
				'author' => $this->authorPropertyId->getSerialization(),
				'publisher' => $this->publisherPropertyId->getSerialization(),
				'publicationDate' => $this->publicationDatePropertyId->getSerialization(),
				'retrievedDate' => $this->retrievedDatePropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			// note: this is not a particularly great way to format a reference with one URL and two titles, but it works
			'reference URL(wikibase-reference-formatter-snak-separator)' .
			'title 1(wikibase-reference-formatter-snak-separator)' .
			'title 2(wikibase-reference-formatter-snak-separator)' .
			'stated in(wikibase-reference-formatter-snak-separator)' .
			'author(wikibase-reference-formatter-snak-separator)' .
			'publisher(wikibase-reference-formatter-snak-separator)' .
			'publication date(wikibase-reference-formatter-snak-separator)' .
			'other 2(wikibase-reference-formatter-snak-separator)' .
			'other 1(wikibase-reference-formatter-snak-separator)' .
			'(wikibase-reference-formatter-snak-retrieved: retrieved date)' .
			'(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithValidLink_titleString() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://reference.example/URL' ),
			$this->stringValueSnak( $this->titlePropertyId, 'title' ),
			$this->stringValueSnak( $this->otherPropertyId1, 'other' ),
			$this->stringValueSnak( $this->authorPropertyId, 'author' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
				'author' => $this->authorPropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'[https://reference.example/URL title](wikibase-reference-formatter-snak-separator)' .
			'author(wikibase-reference-formatter-snak-separator)' .
			'other(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithValidLink_titleMonolingualText() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://reference.example/URL' ),
			new PropertyValueSnak(
				$this->titlePropertyId,
				new MonolingualTextValue( 'en', 'title' )
			),
			$this->stringValueSnak( $this->otherPropertyId1, 'other' ),
			$this->stringValueSnak( $this->authorPropertyId, 'author' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringOrMonolingualTextSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
				'author' => $this->authorPropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'[https://reference.example/URL <span lang="en">title</span>]' .
			'(wikibase-reference-formatter-snak-separator)' .
			'author(wikibase-reference-formatter-snak-separator)' .
			'other(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithInvalidLink_urlNone() {
		$reference = new Reference( [ $this->stringValueSnak( $this->titlePropertyId, 'title' ) ] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [ 'title' => $this->titlePropertyId->getSerialization() ] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'title(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithInvalidLink_referenceTwice() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://reference.example/URL-1' ),
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://reference.example/URL-2' ),
			$this->stringValueSnak( $this->titlePropertyId, 'title' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'https://reference.example/URL-1(wikibase-reference-formatter-snak-separator)' .
			'https://reference.example/URL-2(wikibase-reference-formatter-snak-separator)' .
			'title(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	/** @dataProvider provideOtherSnakTypesAndClasses */
	public function testReferenceWithInvalidLink_referenceOtherSnakType(
		string $otherSnakType,
		string $otherSnakClass
	) {
		$reference = new Reference( [
			new $otherSnakClass( $this->referenceUrlPropertyId ),
			$this->stringValueSnak( $this->titlePropertyId, 'title' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringOrOtherSnakTypeSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			"$otherSnakType(wikibase-reference-formatter-snak-separator)" .
			'title(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithInvalidLink_referenceNotString() {
		$reference = new Reference( [
			new PropertyValueSnak(
				$this->referenceUrlPropertyId,
				new MonolingualTextValue( 'zxx', 'https://reference.example/URL' )
			),
			$this->stringValueSnak( $this->titlePropertyId, 'title' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringOrMonolingualTextSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'<span lang="zxx">https://reference.example/URL</span>' .
			'(wikibase-reference-formatter-snak-separator)' .
			'title(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithInvalidLink_titleNone() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://ref.example' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [ 'referenceUrl' => $this->referenceUrlPropertyId->getSerialization() ] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'https://ref.example(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	public function testReferenceWithInvalidLink_titleTwice() {
		$reference = new Reference( [
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://reference.example/URL' ),
			$this->stringValueSnak( $this->titlePropertyId, 'title 1' ),
			$this->stringValueSnak( $this->titlePropertyId, 'title 2' ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'https://reference.example/URL(wikibase-reference-formatter-snak-separator)' .
			'title 1(wikibase-reference-formatter-snak-separator)' .
			'title 2(wikibase-reference-formatter-snak-terminator)',
			$wikitext
		);
	}

	/** @dataProvider provideOtherSnakTypesAndClasses */
	public function testReferenceWithInvalidLink_titleOtherSnakType(
		string $otherSnakType,
		string $otherSnakClass
	) {
		$reference = new Reference( [
			$this->stringValueSnak( $this->referenceUrlPropertyId, 'https://reference.example/URL' ),
			new $otherSnakClass( $this->titlePropertyId ),
		] );
		$referenceFormatter = new DataBridgeReferenceFormatter(
			$this->stringOrOtherSnakTypeSnakFormatter(),
			WellKnownReferenceProperties::newFromArray( [
				'referenceUrl' => $this->referenceUrlPropertyId->getSerialization(),
				'title' => $this->titlePropertyId->getSerialization(),
			] ),
			new MockMessageLocalizer()
		);

		$wikitext = $referenceFormatter->formatReference( $reference );

		$this->assertSame(
			'https://reference.example/URL(wikibase-reference-formatter-snak-separator)' .
			"$otherSnakType(wikibase-reference-formatter-snak-terminator)",
			$wikitext
		);
	}

	public function provideOtherSnakTypesAndClasses(): iterable {
		yield 'some/unknown value' => [ 'somevalue', PropertySomeValueSnak::class ];
		yield 'no value' => [ 'novalue', PropertyNoValueSnak::class ];
	}

}
