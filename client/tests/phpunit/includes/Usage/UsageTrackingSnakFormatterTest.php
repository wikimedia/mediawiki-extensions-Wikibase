<?php

namespace Wikibase\Client\Tests\Usage;

use DataValues\StringValue;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
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
 * @license GPL-2.0+
 * @author Daniel Kinzler
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

	public function testFormatSnak_item() {
		$p1 = new PropertyId( 'P1' );
		$q1 = new ItemId( 'Q1' );
		$itemSnak = new PropertyValueSnak( $p1, new EntityIdValue( $q1 ) );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter( $mockFormatter, $acc, [ 'ru', 'en' ] );

		$formatter->formatSnak( $itemSnak );

		$usages = $acc->getUsages();
		$this->assertArrayHasKey( 'Q1#L.ru', $usages );
		$this->assertArrayHasKey( 'Q1#L.en', $usages );
		$this->assertArrayHasKey( 'Q1#T', $usages );
	}

	public function testFormatSnak_novalue() {
		$p1 = new PropertyId( 'P1' );
		$novalueSnak = new PropertyNoValueSnak( $p1 );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter( $mockFormatter, $acc, [ 'ru', 'en' ] );

		$formatter->formatSnak( $novalueSnak );
		$this->assertEmpty( $acc->getUsages(), 'novalue' );
	}

	public function testFormatSnak_string() {
		$p1 = new PropertyId( 'P1' );
		$stringSnak = new PropertyValueSnak( $p1, new StringValue( 'xxx' ) );

		$mockFormatter = $this->getMockSnakFormatter( 'formatSnak', 'test' );
		$acc = new HashUsageAccumulator();
		$formatter = new UsageTrackingSnakFormatter( $mockFormatter, $acc, [ 'ru', 'en' ] );

		$formatter->formatSnak( $stringSnak );
		$this->assertEmpty( $acc->getUsages(), 'string value' );
	}

	public function testGetFormat() {
		$mockFormatter = $this->getMockSnakFormatter( 'getFormat', 'TEST' );

		$acc = new HashUsageAccumulator();

		$formatter = new UsageTrackingSnakFormatter( $mockFormatter, $acc, [ 'ru', 'en' ] );

		$this->assertEquals( 'TEST', $formatter->getFormat(), 'getFormat' );
		$this->assertCount( 0, $acc->getUsages() );
	}

}
