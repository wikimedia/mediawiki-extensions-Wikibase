<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function buildDeserializerFactory() {
		return new DeserializerFactory( new DataValueDeserializer(), new BasicEntityIdParser() );
	}

	private function assertDeserializesWithoutException(
		Deserializer $deserializer,
		$serialization
	) {
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

	public function testNewItemDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newItemDeserializer(),
			array(
				'type' => 'item'
			)
		);
	}

	public function testNewPropertyDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newPropertyDeserializer(),
			array(
				'type' => 'property',
				'datatype' => 'string'
			)
		);
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

	public function testNewStatementDeserializer() {
		$this->assertTrue( $this->buildDeserializerFactory()->newStatementDeserializer()->isDeserializerFor(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			)
		) );
	}

	public function testStatementListDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newStatementListDeserializer(),
			array(
				'P42' => array(
				)
			)
		);
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

	public function testNewTermDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newTermDeserializer(),
			array( 'language' => 'en', 'value' => 'Some Term' )
		);
	}

	public function testNewTermListDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newTermListDeserializer(),
			array(
				'en' => array( 'language' => 'en', 'value' => 'Some Term' ),
				'de' => array( 'language' => 'de', 'value' => 'Some Term' ),
			)
		);
	}

	public function testNewAliasGroupListDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newAliasGroupListDeserializer(),
			array( 'en' => array( array( 'language' => 'en', 'value' => 'Some Term' ) ) )
		);
	}

}
