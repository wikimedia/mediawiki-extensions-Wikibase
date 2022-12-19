<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @covers Wikibase\DataModel\DeserializerFactory
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DeserializerFactoryTest extends TestCase {

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
			[
				'type' => 'item',
			]
		) );
		$this->assertTrue( $this->buildDeserializerFactory()->newEntityDeserializer()->isDeserializerFor(
			[
				'type' => 'property',
			]
		) );
	}

	public function testNewItemDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newItemDeserializer(),
			[
				'type' => 'item',
			]
		);
	}

	public function testNewPropertyDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newPropertyDeserializer(),
			[
				'type' => 'property',
				'datatype' => 'string',
			]
		);
	}

	public function testNewSiteLinkDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newSiteLinkDeserializer(),
			[
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
			]
		);
	}

	public function testNewStatementDeserializer() {
		$this->assertTrue(
			$this->buildDeserializerFactory()->newStatementDeserializer()->isDeserializerFor(
				[
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
					'type' => 'claim',
				]
			)
		);
	}

	public function testStatementListDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newStatementListDeserializer(),
			[
				'P42' => [
				],
			]
		);
	}

	public function testNewReferencesDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newReferencesDeserializer(),
			[
				[
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => [],
				],
			]
		);
	}

	public function testNewReferenceDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newReferenceDeserializer(),
			[
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => [],
			]
		);
	}

	public function testNewSnakDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newSnakDeserializer(),
			[
				'snaktype' => 'novalue',
				'property' => 'P42',
			]
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
			[ 'language' => 'en', 'value' => 'Some Term' ]
		);
	}

	public function testNewTermListDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newTermListDeserializer(),
			[
				'en' => [ 'language' => 'en', 'value' => 'Some Term' ],
				'de' => [ 'language' => 'de', 'value' => 'Some Term' ],
			]
		);
	}

	public function testNewAliasGroupListDeserializer() {
		$this->assertDeserializesWithoutException(
			$this->buildDeserializerFactory()->newAliasGroupListDeserializer(),
			[ 'en' => [ [ 'language' => 'en', 'value' => 'Some Term' ] ] ]
		);
	}

}
