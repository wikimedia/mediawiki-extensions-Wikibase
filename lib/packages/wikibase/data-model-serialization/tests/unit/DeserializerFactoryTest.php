<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class DeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function buildDeserializerFactory() {
		return new DeserializerFactory( new DataValueDeserializer(), new BasicEntityIdParser() );
	}

	private function assertDeserializesWithoutException( Deserializer $deserializer, $serialization ) {
		$deserializer->deserialize( $serialization );
		$this->assertTrue( true, 'No exception occurred during deserialization' );
	}

	public function testNewEntityDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newEntityDeserializer()->isDeserializerFor(
			array(
				'type' => 'item'
			)
		) );
		$this->assertTrue( $this->buildDeserializerFactory()->newEntityDeserializer()->isDeserializerFor(
			array(
				'type' => 'property'
			)
		) );
	}

	public function testNewSiteLinkDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newSiteLinkDeserializer(),
			array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat'
			)
		);
	}

	public function testNewClaimsDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newClaimsDeserializer(),
			array(
				'P42' => array(
				)
			)
		);
	}

	public function testNewClaimDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newClaimDeserializer()->isDeserializerFor(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			)
		) );
	}

	public function testNewReferencesDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newReferencesDeserializer(),
			array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
				)
			)
		);
	}

	public function testNewReferenceDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newReferenceDeserializer(),
			array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			)
		);
	}

	public function testNewSnaksDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newSnaksDeserializer(),
			array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			)
		);
	}

	public function testNewSnakDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newSnakDeserializer(),
			array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			)
		);
	}

	public function testNewEntityIdDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newEntityIdDeserializer(),
			'Q42'
		);
	}
}