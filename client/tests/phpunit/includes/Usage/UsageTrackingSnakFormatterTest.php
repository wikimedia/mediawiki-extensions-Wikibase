<?php

namespace Wikibase\Client\Tests\Usage;

use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Client\Usage\UsageTrackingSnakFormatter
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class UsageTrackingSnakFormatterTest extends \MediaWikiTestCase {

	/**
	 * @param string $method
	 * @param string $return
	 *
	 * @return SnakFormatter
	 */
	private function getMockSnakFormatter( $method, $return ) {
		$mockFormatter = $this->getMock( SnakFormatter::class );

		$mockFormatter->expects( $this->once() )
			->method( $method )
			->will( $this->returnValue( $return ) );

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
		$usageTrackingLabelDescriptionLookup = $this->getMockBuilder( UsageTrackingLanguageFallbackLabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$getLabelInvocationMocker = $usageTrackingLabelDescriptionLookup->expects( $this->exactly( $formatEntity ? 1 : 0 ) )
			->method( 'getLabel' )
			->with( $formatEntity )
			->will( $this->returnValue( 'foobar' ) );

		return $usageTrackingLabelDescriptionLookup;
	}

	public function testFormatSnak_item() {
		$p1 = new PropertyId( 'P1' );
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
		$parser = $this->getMock( EntityIdParser::class );
		$parser->expects( $this->once() )
			->method( 'parse' )
			->will( $this->returnCallback( function ( $id ) {
				return $id === '1' ? null : new ItemId( $id );
			} ) );

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
		$p1 = new PropertyId( 'P1' );
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
		$p1 = new PropertyId( 'P1' );
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
		$this->assertEmpty( $acc->getUsages(), 'novalue' );
	}

	public function testFormatSnak_string() {
		$p1 = new PropertyId( 'P1' );
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
		$this->assertEmpty( $acc->getUsages(), 'string value' );
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
		$this->assertCount( 0, $acc->getUsages() );
	}

}
