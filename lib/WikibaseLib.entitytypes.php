<?php

/**
 * Definition of entity types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoEntityTypes
 * resp. $wgWBClientEntityTypes.
 *
 * @note: When adding entity types here, also add the corresponding information to
 * repo/WikibaseRepo.entitytypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @see docs/entitytypes.wiki
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\Services\Diff\PropertyDiffer;

return array(
	'item' => array(
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newItemDeserializer();
		},
		'entity-differ-strategy-builder' => function() {
			return new ItemDiffer();
		}
	),
	'property' => array(
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newPropertyDeserializer();
		},
		'entity-differ-strategy-builder' => function() {
			return new PropertyDiffer();
		}
	)
);
