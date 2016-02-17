<?php

/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoEntityTypes
 * resp. $wgWBClientEntityTypes.
 *
 * @note: When adding entity types here, also add the corresponding information to
 * repo/Wikibase.entitytypes.php and client/WikibaseClient.entitytypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\EntityChange;
use Wikibase\ItemChange;

return array(
	'item' => array(
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newItemDeserializer();
		},
		'entity-factory-callback' => function() {
			return new Item();
		},
		'change-factory-callback' => function( array $fields ) {
			return new ItemChange( $fields );
		}
	),
	'property' => array(
		'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newPropertyDeserializer();
		},
		'entity-factory-callback' => function() {
			return Property::newFromType( '' );
		},
		'change-factory-callback' => function( array $fields ) {
			return new EntityChange( $fields );
		}
	)
);
