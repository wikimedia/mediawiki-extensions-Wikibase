<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use DataValues\DataValue;
use DataValues\StringValue;
use Language;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\DataAccess\Scribunto\SnakSerializationRenderer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\SnakSerializationRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SnakSerializationRendererTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param DataValue $value
	 *
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
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return SnakSerializationRenderer
	 */
	private function getSnakRenderer() {
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

		return new SnakSerializationRenderer(
			$snakFormatter,
			$snakDeserializer,
			Language::factory( 'en' ),
			$snaksDeserializer
		);
	}

	public function testRenderSnak() {
		$snakSerialization = $this->getSnakSerialization( new StringValue( 'foo bar foo' ) );
		$snakRenderer = $this->getSnakRenderer();

		$this->assertSame( 'foo bar foo', $snakRenderer->renderSnak( $snakSerialization ) );
	}

	public function provideRenderSnaks() {
		return array(
			'Single Snak' => array(
				'foo bar foo',
				array( 'P42' => array( $this->getSnakSerialization( new StringValue( 'foo bar foo' ) ) ) )
			),
			'Multiple Snaks' => array(
				'foo, bar, Berlin',
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
	public function testRenderSnaks( $expected, array $snaksSerialization ) {
		$snakRenderer = $this->getSnakRenderer();

		$this->assertSame( $expected, $snakRenderer->renderSnaks( $snaksSerialization ) );
	}

}
