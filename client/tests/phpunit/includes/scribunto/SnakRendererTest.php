<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use PHPUnit_Framework_TestCase;
use DataValues\StringValue;
use Wikibase\Client\Scribunto\SnakRenderer;
use Wikibase\Client\WikibaseClient;
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
	private function getSnakSerialization( $str ) {
		$snak = new PropertyValueSnak(
			new PropertyId( 'P42' ),
			new StringValue( $str )
		);

		$snakSerializer = new SnakSerializer();
		$serialized = $snakSerializer->getSerialized( $snak );

		return $serialized;
	}

	/**
	 * @return SnakRenderer
	 */
	private function getSnakRenderer() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );
		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback( function ( PropertyValueSnak $snak ) {
				return $snak->getDataValue()->getValue();
			} ) );

		$snakDeserializer = $wikibaseClient->getDeserializerFactory()->newSnakDeserializer();
		$snaksDeserializer = $wikibaseClient->getDeserializerFactory()->newSnaksDeserializer();

		return new SnakRenderer(
			$snakFormatter,
			$snakDeserializer,
			Language::factory( 'en' ),
			$snaksDeserializer
		);
	}

	public function testRenderSnak() {
		$snakSerialization = $this->getSnakSerialization( 'foo bar foo' );
		$snakRenderer = $this->getSnakRenderer();

		$this->assertSame( 'foo bar foo', $snakRenderer->renderSnak( $snakSerialization ) );
	}

	public function provideRenderSnaks() {
		return array(
			'Single Snak' => array(
				'foo bar foo',
				array( 'P42' => array( $this->getSnakSerialization( 'foo bar foo' ) ) )
			),
			'Multiple Snaks' => array(
				'foo, bar, Berlin',
				array( array(
					$this->getSnakSerialization( 'foo' ),
					$this->getSnakSerialization( 'bar' ),
					$this->getSnakSerialization( 'Berlin' )
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
