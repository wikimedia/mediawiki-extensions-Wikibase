<?php

namespace Wikibase\Client\Tests\Unit\Usage;

use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use MediaWikiCoversValidator;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Client\Usage\UsageTrackingSnakFormatter
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class UsageTrackingSnakFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	/**
	 * @param string $method
	 * @param string $return
	 *
	 * @return SnakFormatter
	 */
	private function getMockSnakFormatter( $method, $return ) {
		$mockFormatter = $this->createMock( SnakFormatter::class );

		$mockFormatter->expects( $this->once() )
			->method( $method )
			->willReturn( $return );

		return $mockFormatter;
	}

	/**
	 * Gets a UsageTrackingLanguageFallbackLabelDescriptionLookup that makes sure that
	 * it has been called/ not called with the correct EntityId.
	 *
	 * @param EntityId|null $formatEntity
	 *
	 * @return UsageTrackingLanguageFallbackLabelDescriptionLookup
	 */
	private function getUsageTrackingLabelDescriptionLookup( EntityId $formatEntity = null ) {
		$usageTrackingLabelDescriptionLookup = $this->createMock( UsageTrackingLanguageFallbackLabelDescriptionLookup::class );

		$getLabelInvocationMocker = $usageTrackingLabelDescriptionLookup->expects( $this->exactly( $formatEntity ? 1 : 0 ) )
			->method( 'getLabel' )
			->with( $formatEntity )
			->willReturn( 'foobar' );

		return $usageTrackingLabelDescriptionLookup;
	}

	public function testFormatSnak_item() {
		$p1 = new NumericPropertyId( 'P1' );
		$q1 = new ItemId( 'Q1' );
		$itemSnak = new PropertyValueSnak( $p1, new EntityIdValue( $q1 ) );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter(
			$mockFormatter,
			$acc,
			new ItemIdParser(),
			$this->getUsageTrackingLabelDescriptionLookup( $q1 )
		);

		$formatter->formatSnak( $itemSnak );

		$usages = $acc->getUsages();
		$this->assertArrayHasKey( 'Q1#T', $usages );
	}

	/**
	 * @return EntityIdParser
	 */
	private function getRepoItemUriParser() {
		$parser = $this->createMock( EntityIdParser::class );
		$parser->expects( $this->once() )
			->method( 'parse' )
			->willReturnCallback( function ( $id ) {
				return $id === '1' ? null : new ItemId( $id );
			} );

		return $parser;
	}

	public function formatSnakQuantityProvider() {
		return [
			[ '1', null ],
			[ 'Q1', new ItemId( 'Q1' ) ],
		];
	}

	/**
	 * @dataProvider formatSnakQuantityProvider
	 */
	public function testFormatSnak_quantity( $unit, EntityId $formattedEntityId = null ) {
		$p1 = new NumericPropertyId( 'P1' );
		$itemSnak = new PropertyValueSnak(
			$p1, UnboundedQuantityValue::newFromNumber( '1', $unit ) );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter(
			$mockFormatter,
			$acc,
			$this->getRepoItemUriParser(),
			$this->getUsageTrackingLabelDescriptionLookup( $formattedEntityId )
		);

		$formatter->formatSnak( $itemSnak );

		$this->assertSame( [], $acc->getUsages() );
	}

	public function testFormatSnak_novalue() {
		$p1 = new NumericPropertyId( 'P1' );
		$novalueSnak = new PropertyNoValueSnak( $p1 );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter(
			$mockFormatter,
			$acc,
			new ItemIdParser(),
			$this->getUsageTrackingLabelDescriptionLookup()
		);

		$formatter->formatSnak( $novalueSnak );
		$this->assertSame( [], $acc->getUsages(), 'novalue' );
	}

	public function testFormatSnak_string() {
		$p1 = new NumericPropertyId( 'P1' );
		$stringSnak = new PropertyValueSnak( $p1, new StringValue( 'xxx' ) );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter(
			$mockFormatter,
			$acc,
			new ItemIdParser(),
			$this->getUsageTrackingLabelDescriptionLookup()
		);

		$formatter->formatSnak( $stringSnak );
		$this->assertSame( [], $acc->getUsages(), 'string value' );
	}

	public function testGetFormat() {
		$mockFormatter = $this->getMockSnakFormatter( 'getFormat', 'TEST' );

		$acc = new HashUsageAccumulator();

		$formatter = new UsageTrackingSnakFormatter(
			$mockFormatter,
			$acc,
			new ItemIdParser(),
			$this->getUsageTrackingLabelDescriptionLookup()
		);

		$this->assertEquals( 'TEST', $formatter->getFormat(), 'getFormat' );
		$this->assertSame( [], $acc->getUsages() );
	}

}
