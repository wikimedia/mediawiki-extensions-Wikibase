<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use MediaWikiTestCase;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder;

/**
 * @covers Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputJsConfigBuilderTest extends MediaWikiTestCase {

	private function newEntitySerializer() {
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		return $serializerFactory->newEntitySerializer();
	}

	public function testBuildConfigItem() {
		$item = new Item( new ItemId( 'Q5881' ) );
		$this->addLabels( $item );
		$mainSnakPropertyId = $this->addStatements( $item );

		$configBuilder = new ParserOutputJsConfigBuilder( $this->newEntitySerializer() );
		$configVars = $configBuilder->build( $item );

		$this->assertWbEntityId( 'Q5881', $configVars );

		$this->assertWbEntity(
			$this->getSerialization( $item, $mainSnakPropertyId ),
			$configVars
		);

		$this->assertSerializationEqualsEntity(
			$item,
			json_decode( $configVars['wbEntity'], true )
		);
	}

	public function testBuildConfigProperty() {
		$property = new Property( new PropertyId( 'P330' ), null, 'string' );
		$this->addLabels( $property );
		$mainSnakPropertyId = $this->addStatements( $property );

		$configBuilder = new ParserOutputJsConfigBuilder( $this->newEntitySerializer() );
		$configVars = $configBuilder->build( $property );

		$this->assertWbEntityId( 'P330', $configVars );

		$expectedSerialization = $this->getSerialization( $property, $mainSnakPropertyId );
		$expectedSerialization['datatype'] = 'string';

		$this->assertWbEntity( $expectedSerialization, $configVars );

		$this->assertSerializationEqualsEntity(
			$property,
			json_decode( $configVars['wbEntity'], true )
		);
	}

	public function assertWbEntityId( $expectedId, array $configVars ) {
		$this->assertEquals(
			$expectedId,
			$configVars['wbEntityId'],
			'wbEntityId'
		);
	}

	public function assertWbEntity( array $expectedSerialization, array $configVars ) {
		$this->assertEquals(
			$expectedSerialization,
			json_decode( $configVars['wbEntity'], true ),
			'wbEntity'
		);
	}

	public function assertSerializationEqualsEntity( EntityDocument $entity, $serialization ) {
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer( [ 'string' => StringValue::class ] ),
			new BasicEntityIdParser()
		);

		$unserializedEntity = $deserializerFactory->newEntityDeserializer()->deserialize( $serialization );

		$this->assertTrue(
			$unserializedEntity->equals( $entity ),
			'unserialized entity equals entity'
		);
	}

	private function addLabels( LabelsProvider $entity ) {
		$termList = $entity->getLabels();
		$termList->setTextForLanguage( 'en', 'Cake' );
		$termList->setTextForLanguage( 'de', 'Kuchen' );
	}

	private function addStatements( StatementListProvider $statementListProvider ) {
		$propertyId = new PropertyId( 'P794' );

		$statementListProvider->getStatements()->addNewStatement(
			new PropertyValueSnak( $propertyId, new StringValue( 'kittens!' ) ),
			null,
			null,
			$this->makeGuid( $statementListProvider->getId() )
		);

		return $propertyId;
	}

	private function makeGuid( EntityId $entityId ) {
		return $entityId->getSerialization() . '$muahahaha';
	}

	private function getSerialization( EntityDocument $entity, PropertyId $propertyId ) {
		$serialization = [
			'id' => $entity->getId()->getSerialization(),
			'type' => $entity->getType(),
			'labels' => [
				'de' => [
					'language' => 'de',
					'value' => 'Kuchen'
				],
				'en' => [
					'language' => 'en',
					'value' => 'Cake'
				]
			],
			'descriptions' => [],
			'aliases' => [],
			'claims' => [
				$propertyId->getSerialization() => [
					[
						'id' => $this->makeGuid( $entity->getId() ),
						'mainsnak' => [
							'snaktype' => 'value',
							'property' => $propertyId->getSerialization(),
							'datavalue' => [
								'value' => 'kittens!',
								'type' => 'string'
							],
						],
						'type' => 'statement',
						'rank' => 'normal',
					],
				],
			],
		];

		if ( $entity instanceof Item ) {
			$serialization['sitelinks'] = [];
		}
		return $serialization;
	}

}
