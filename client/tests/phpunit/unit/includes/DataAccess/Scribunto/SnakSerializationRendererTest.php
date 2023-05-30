<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use DataValues\DataValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use Wikibase\Client\DataAccess\Scribunto\SnakSerializationRenderer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\SnakSerializationRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class SnakSerializationRendererTest extends \PHPUnit\Framework\TestCase {

	private static function getSnakSerialization( DataValue $value ): array {
		$snak = new PropertyValueSnak(
			new NumericPropertyId( 'P42' ),
			$value
		);

		$snakSerializer = new SnakSerializer( new DataValueSerializer() );
		$serialized = $snakSerializer->serialize( $snak );

		return $serialized;
	}

	/**
	 * @return SnakSerializationRenderer
	 */
	private function getSnakRenderer() {
		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback( function ( PropertyValueSnak $snak ) {
				$value = $snak->getDataValue();
				if ( $value instanceof EntityIdValue ) {
					return $value->getEntityId()->getSerialization();
				} else {
					return $value->getValue();
				}
			} );
		$snakFormatter->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_PLAIN );

		$deserializerFactory = WikibaseClient::getBaseDataModelDeserializerFactory();
		$snakDeserializer = $deserializerFactory->newSnakDeserializer();
		$snaksDeserializer = $deserializerFactory->newSnakListDeserializer();

		return new SnakSerializationRenderer(
			$snakFormatter,
			$snakDeserializer,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			$snaksDeserializer
		);
	}

	public function testRenderSnak() {
		$snakSerialization = self::getSnakSerialization( new StringValue( 'foo bar foo' ) );
		$snakRenderer = $this->getSnakRenderer();

		$this->assertSame( 'foo bar foo', $snakRenderer->renderSnak( $snakSerialization ) );
	}

	public static function provideRenderSnaks(): iterable {
		return [
			'Single Snak' => [
				'foo bar foo',
				[ 'P42' => [
					self::getSnakSerialization( new StringValue( 'foo bar foo' ) ),
				] ],
			],
			'Multiple Snaks' => [
				'foo, bar, Berlin',
				[ 'P42' => [
					self::getSnakSerialization( new StringValue( 'foo' ) ),
					self::getSnakSerialization( new StringValue( 'bar' ) ),
					self::getSnakSerialization( new StringValue( 'Berlin' ) ),
					self::getSnakSerialization( new StringValue( '' ) ),
				] ],
			],
		];
	}

	/**
	 * @dataProvider provideRenderSnaks
	 */
	public function testRenderSnaks( $expected, array $snaksSerialization ) {
		$snakRenderer = $this->getSnakRenderer();

		$this->assertSame( $expected, $snakRenderer->renderSnaks( $snaksSerialization ) );
	}

}
