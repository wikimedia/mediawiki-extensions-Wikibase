<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
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
		$this->assertTrue( $this->buildDeserializerFactory()->newSiteLinkDeserializer()->isDeserializerFor(
			array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat'
			)
		) );
	}

	public function testNewClaimsDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newClaimsDeserializer()->isDeserializerFor(
			array(
				'P42' => array(
					array(
						'mainsnak' => array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						),
						'type' => 'claim'
					)
				)
			)
		) );
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
		$this->assertTrue( $this->buildDeserializerFactory()->newReferencesDeserializer()->isDeserializerFor(
			array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
				)
			)
		) );
	}

	public function testNewReferenceDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newReferenceDeserializer()->isDeserializerFor(
			array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			)
		) );
	}

	public function testNewSnaksDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newSnaksDeserializer()->isDeserializerFor(
			array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			)
		) );
	}

	public function testNewSnakDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newSnakDeserializer()->isDeserializerFor(
			array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			)
		) );
	}

	public function testNewEntityIdDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newEntityIdDeserializer()->isDeserializerFor(
			'Q42'
		) );
	}
}