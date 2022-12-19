<?php

/**
 * Definition of base entity types for use with Wikibase.
 *
 * @note: When adding entity types here, also add the corresponding information to
 * repo/WikibaseRepo.entitytypes.php
 *
 * @note This is bootstrap code, it is executed for EVERY request.
 * Avoid instantiating objects here!
 *
 * @see docs/entitytypes.wiki
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\Services\Diff\ItemPatcher;
use Wikibase\DataModel\Services\Diff\PropertyDiffer;
use Wikibase\DataModel\Services\Diff\PropertyPatcher;
use Wikibase\Lib\EntityTypeDefinitions as Def;

return [
	'item' => [
		Def::SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		Def::DESERIALIZER_FACTORY_CALLBACK => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newItemDeserializer();
		},
		Def::ENTITY_ID_PATTERN => ItemId::PATTERN,
		Def::ENTITY_ID_BUILDER => function( $serialization ) {
			return new ItemId( $serialization );
		},
		Def::ENTITY_ID_COMPOSER_CALLBACK => function( $repositoryName, $uniquePart ) {
			return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => function() {
			return new ItemDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => function() {
			return new ItemPatcher();
		},
	],
	'property' => [
		Def::SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		Def::DESERIALIZER_FACTORY_CALLBACK => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newPropertyDeserializer();
		},
		Def::ENTITY_ID_PATTERN => NumericPropertyId::PATTERN,
		Def::ENTITY_ID_BUILDER => function( $serialization ) {
			return new NumericPropertyId( $serialization );
		},
		Def::ENTITY_ID_COMPOSER_CALLBACK => function( $repositoryName, $uniquePart ) {
			return NumericPropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => function() {
			return new PropertyDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => function() {
			return new PropertyPatcher();
		},
	],
];
