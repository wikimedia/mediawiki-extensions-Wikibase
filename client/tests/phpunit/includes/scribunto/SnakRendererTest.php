<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use PHPUnit_Framework_TestCase;
use DataValues\StringValue;
use DataValues\DataValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\Client\Scribunto\SnakRenderer;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Client\Scribunto\SnakRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SnakRendererTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	private function getSnakSerialization( DataValue $value ) {
		$snak = new PropertyValueSnak(
			new PropertyId( 'P42' ),
			$value
		);

		$snakSerializer = new SnakSerializer();
		$serialized = $snakSerializer->getSerialized( $snak );

		return $serialized;
	}

	/**
	 * @return SnakRenderer
	 */
	private function getSnakRenderer( UsageAccumulator $usageAccumulator ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );
		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback( function ( PropertyValueSnak $snak ) {
				$value = $snak->getDataValue();
				if ( $value instanceof EntityIdValue ) {
					return $value->getEntityId()->getSerialization();
				} else {
					return $value->getValue();
				}
			} ) );

		$snakDeserializer = $wikibaseClient->getDeserializerFactory()->newSnakDeserializer();
		$snaksDeserializer = $wikibaseClient->getDeserializerFactory()->newSnaksDeserializer();

		return new SnakRenderer(
			$snakFormatter,
			$snakDeserializer,
			Language::factory( 'en' ),
			$snaksDeserializer,
			$usageAccumulator
		);
	}

	public function testRenderSnak() {
		$snakSerialization = $this->getSnakSerialization( new StringValue( 'foo bar foo' ) );
		$usageAccumulator = new HashUsageAccumulator();
		$snakRenderer = $this->getSnakRenderer( $usageAccumulator );

		$this->assertSame( 'foo bar foo', $snakRenderer->renderSnak( $snakSerialization ) );

		$this->assertCount( 0, $usageAccumulator->getUsages() );
	}

	public function testRenderSnak_usage() {
		$Q42 = new ItemId( 'Q42' );
		$snakSerialization = $this->getSnakSerialization( new EntityIdValue( $Q42 ) );
		$usageAccumulator = new HashUsageAccumulator();
		$snakRenderer = $this->getSnakRenderer( $usageAccumulator );

		$this->assertSame( 'Q42', $snakRenderer->renderSnak( $snakSerialization ) );
		$usages = $usageAccumulator->getUsages();

		$this->assertCount( 1, $usages );
		$this->assertEquals(
			new EntityUsage( $Q42, EntityUsage::LABEL_USAGE ),
			array_shift( $usages )
		);
	}

	public function provideRenderSnaks() {
		return array(
			'Single Snak' => array(
				array( 'foo bar foo' ),
				array( 'P42' => array( $this->getSnakSerialization( new StringValue( 'foo bar foo' ) ) ) )
			),
			'Multiple Snaks' => array(
				array( 'foo', 'bar', 'Berlin' ),
				array( array(
					$this->getSnakSerialization( new StringValue( 'foo' ) ),
					$this->getSnakSerialization( new StringValue( 'bar' ) ),
					$this->getSnakSerialization( new StringValue( 'Berlin' ) )
				) )
			)
		);
	}

	/**
	 * @dataProvider provideRenderSnaks
	 */
	public function testRenderSnaks( array $expected, array $snaksSerialization ) {
		global $wgContLang;

		$usageAccumulator = new HashUsageAccumulator();
		$snakRenderer = $this->getSnakRenderer( $usageAccumulator );

		$this->assertSame(
			$wgContLang->commaList( $expected ),
			$snakRenderer->renderSnaks( $snaksSerialization )
		);

		$this->assertCount( 0, $usageAccumulator->getUsages() );
	}

	public function testRenderSnaks_usage() {
		global $wgContLang;

		$Q42 = new ItemId( 'Q42' );
		$Q43 = new ItemId( 'Q43' );
		$snaksSerialization = array( array() );
		$snaksSerialization[0][] = $this->getSnakSerialization( new EntityIdValue( $Q42 ) );
		$snaksSerialization[0][] = $this->getSnakSerialization( new EntityIdValue( $Q43 ) );

		$usageAccumulator = new HashUsageAccumulator();
		$snakRenderer = $this->getSnakRenderer( $usageAccumulator );

		$this->assertSame(
			$wgContLang->commaList( array( 'Q42', 'Q43' ) ),
			$snakRenderer->renderSnaks( $snaksSerialization )
		);
		$usages = $usageAccumulator->getUsages();

		$this->assertCount( 2, $usages );
		$this->assertEquals(
			new EntityUsage( $Q42, EntityUsage::LABEL_USAGE ),
			array_shift( $usages )
		);
		$this->assertEquals(
			new EntityUsage( $Q43, EntityUsage::LABEL_USAGE ),
			array_shift( $usages )
		);
	}
}
