<?php

namespace Wikibase\Test\Api;

use LogicException;
use OutOfBoundsException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityId;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\Serializers\DispatchingEntitySerializer;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class EntityTestHelper {

	//@todo allow data to be defined dynamically in tests
	//this way this class need not contain data but only a way to
	//manage it for the tests

	/**
	 * @var array of currently active handles and their current ids
	 */
	private static $activeHandles = array();
	/**
	 * @var array of currently active ids and their current handles
	 */
	private static $activeIds;
	/**
	 * @var array handles and any registered default output data
	 */
	private static $entityOutput = array();

	/**
	 * @var array of pre defined entity data for use in tests
	 */
	private static $entityData = array(
		'Empty' => array(
			"type" => "item",
			"data" => array(),
		),
		'Empty2' => array(
			"type" => "item",
			"data" => array(),
		),
		'Berlin' => array(
			"type" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Berlin" ),
					array( "site" => "enwiki", "title" => "Berlin" ),
					array( "site" => "nlwiki", "title" => "Berlin" ),
					array( "site" => "nnwiki", "title" => "Berlin" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Berlin" ),
					array( "language" => "en", "value" => "Berlin" ),
					array( "language" => "nb", "value" => "Berlin" ),
					array( "language" => "nn", "value" => "Berlin" ),
				),
				"aliases" => array(
					array( array( "language" => "de", "value" => "Dickes B" ) ),
					array( array( "language" => "en", "value" => "Dickes B" ) ),
					array( array( "language" => "nl", "value" => "Dickes B" ) ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland." ),
					array( "language" => "en", "value" => "Capital city and a federated state of the Federal Republic of Germany." ),
					array( "language" => "nb", "value" => "Hovedsted og delstat og i Forbundsrepublikken Tyskland." ),
					array( "language" => "nn", "value" => "Hovudstad og delstat i Forbundsrepublikken Tyskland." ),
				),
				"claims" => array(
					array( 'mainsnak' => array(
						'snaktype' => 'value',
						'property' => 'P56',
						'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ),
					),
					'type' => 'statement',
					'rank' => 'normal' )
				),
			)
		),
		'London' => array(
			"type" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "enwiki", "title" => "London" ),
					array( "site" => "dewiki", "title" => "London" ),
					array( "site" => "nlwiki", "title" => "London" ),
					array( "site" => "nnwiki", "title" => "London" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "London" ),
					array( "language" => "en", "value" => "London" ),
					array( "language" => "nb", "value" => "London" ),
					array( "language" => "nn", "value" => "London" ),
				),
				"aliases" => array(
					array(
						array( "language" => "de", "value" => "City of London" ),
						array( "language" => "de", "value" => "Greater London" ),
					),
					array(
						array( "language" => "en", "value" => "City of London" ),
						array( "language" => "en", "value" => "Greater London" ),
					),
					array(
						array( "language" => "nl", "value" => "City of London" ),
						array( "language" => "nl", "value" => "Greater London" ),
					),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Hauptstadt Englands und des Vereinigten Königreiches." ),
					array( "language" => "en", "value" => "Capital city of England and the United Kingdom." ),
					array( "language" => "nb", "value" => "Hovedsted i England og Storbritannia." ),
					array( "language" => "nn", "value" => "Hovudstad i England og Storbritannia." ),
				),
			)
		),
		'Oslo' => array(
			"type" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Oslo" ),
					array( "site" => "enwiki", "title" => "Oslo" ),
					array( "site" => "nlwiki", "title" => "Oslo" ),
					array( "site" => "nnwiki", "title" => "Oslo" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Oslo" ),
					array( "language" => "en", "value" => "Oslo" ),
					array( "language" => "nb", "value" => "Oslo" ),
					array( "language" => "nn", "value" => "Oslo" ),
				),
				"aliases" => array(
					array(
						array( "language" => "nb", "value" => "Christiania" ),
						array( "language" => "nb", "value" => "Kristiania" ),
					),
					array(
						array( "language" => "nn", "value" => "Christiania" ),
						array( "language" => "nn", "value" => "Kristiania" ),
					),
					array( "language" => "de", "value" => "Oslo City" ),
					array( "language" => "en", "value" => "Oslo City" ),
					array( "language" => "nl", "value" => "Oslo City" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Hauptstadt der Norwegen." ),
					array( "language" => "en", "value" => "Capital city in Norway." ),
					array( "language" => "nb", "value" => "Hovedsted i Norge." ),
					array( "language" => "nn", "value" => "Hovudstad i Noreg." ),
				),
			)
		),
		'Episkopi' => array(
			"type" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Episkopi Cantonment" ),
					array( "site" => "enwiki", "title" => "Episkopi Cantonment" ),
					array( "site" => "nlwiki", "title" => "Episkopi Cantonment" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Episkopi Cantonment" ),
					array( "language" => "en", "value" => "Episkopi Cantonment" ),
					array( "language" => "nl", "value" => "Episkopi Cantonment" ),
				),
				"aliases" => array(
					array( "language" => "de", "value" => "Episkopi" ),
					array( "language" => "en", "value" => "Episkopi" ),
					array( "language" => "nl", "value" => "Episkopi" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Sitz der Verwaltung der Mittelmeerinsel Zypern." ),
					array( "language" => "en", "value" => "The capital of Akrotiri and Dhekelia." ),
					array( "language" => "nl", "value" => "Het bestuurlijke centrum van Akrotiri en Dhekelia." ),
				),
			)
		),
		'Leipzig' => array(
			"type" => "item",
			"data" => array(
				"labels" => array(
					array( "language" => "de", "value" => "Leipzig" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Stadt in Sachsen." ),
					array( "language" => "en", "value" => "City in Saxony." ),
				),
			)
		),
		'Guangzhou' => array(
			"type" => "item",
			"data" => array(
				"labels" => array(
					array( "language" => "de", "value" => "Guangzhou" ),
					array( "language" => "yue", "value" => "廣州" ),
					array( "language" => "zh-cn", "value" => "广州市" ),
				),
				"descriptions" => array(
					array( "language" => "en", "value" => "Capital of Guangdong." ),
					array( "language" => "zh-hk", "value" => "廣東的省會。" ),
				),
			)
		),

	);

	/**
	 * Get the entity with the given handle
	 * @param $handle string of entity to get data for
	 * @throws OutOfBoundsException
	 * @return array of entity data
	 */
	public static function getEntityData( $handle ){
		if( !array_key_exists( $handle, self::$entityData ) ){
			throw new OutOfBoundsException( "No entity defined with handle {$handle}" );
		}
		$entity = self::$entityData[ $handle ];

		if ( !is_string( $entity['data'] ) ) {
			$entity['data'] = json_encode( $entity['data'] );
		}

		return $entity;
	}

	/**
	 * Get the data of the entity with the given handle we received after creation
	 * @param string $handle
	 * @param null|array $props array of props we want the output to have
	 * @param null|array $langs array of langs we want the output to have
	 * @throws OutOfBoundsException
	 * @return mixed
	 */
	public static function getEntityOutput( $handle, $props = null, $langs = null ){
		if( !array_key_exists( $handle, self::$entityOutput ) ){
			throw new OutOfBoundsException( "No entity output defined with handle {$handle}" );
		}
		if( !is_array( $props ) ){
			return self::$entityOutput[ $handle ];
		} else {
			return self::stripUnwantedOutputValues( self::$entityOutput[ $handle ], $props, $langs );
		}
	}

	/**
	 * Remove props and langs that are not included in $props or $langs from the $entityOutput array
	 * @param array $entityOutput Array of entity output
	 * @param array $props Props to keep in the output
	 * @param null|array $langs Languages to keep in the output
	 * @return array Array of entity output with props and langs removed
	 */
	protected static function stripUnwantedOutputValues( $entityOutput, $props = array(), $langs = null  ){
		$entityProps = array();
		$props[] = 'type'; // always return the type so we can demobilize
		foreach( $props as $prop ){
			if( array_key_exists( $prop, $entityOutput ) ){
				$entityProps[ $prop ] = $entityOutput[ $prop ] ;
			}
		}
		foreach( $entityProps as $prop => $value ){
			if( ( $prop == 'aliases' || $prop == 'labels' || $prop == 'descriptions' ) && $langs != null && is_array( $langs ) ){
				$langValues = array();
				foreach( $langs as $langCode ){
					if( array_key_exists( $langCode, $value ) ){
						$langValues[ $langCode ] = $value[ $langCode ];
					}
				}
				if( $langValues === array() ){
					unset( $entityProps[ $prop ] );
				} else {
					$entityProps[ $prop ] = $langValues;
				}

			}
		}
		return $entityProps;
	}

	/**
	 * Register the entity after it has been created
	 * @param string $handle
	 * @param string $id
	 * @param null $entity
	 */
	public static function registerEntityOutput( $handle, $id, $entity = null ) {
		self::$activeHandles[ $handle ] = $id;
		self::$activeIds[ $id ] = $handle;

		if( $entity ){
			self::$entityOutput[ $handle ] = $entity;
		}
	}

	/**
	 * Register the entity after for the given handle
	 *
	 * @param string $handle
	 * @param Entity $entity
	 */
	public static function registerEntity( $handle, Entity $entity ) {
		$id = $entity->getId()->getSerialization();

		$data = self::serializeEntity( $entity );

		self::registerEntityOutput( $handle, $id, $data );
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array Entity data (external style).
	 */
	public static function serializeEntity( Entity $entity ) {
		$entitySerializer = new DispatchingEntitySerializer( new SerializerFactory() );
		$data = $entitySerializer->getSerialized( $entity );
		return $data;
	}

	/**
	 * Unregister the entity after it has been cleared
	 * @param $handle
	 * @throws OutOfBoundsException
	 */
	public static function unRegisterEntity( $handle ) {
		unset( self::$activeIds[ self::$activeHandles[ $handle ] ] );
		unset( self::$activeHandles[ $handle ] );
	}

	/**
	 * Returns an array of currently activated handles
	 * @return array of currently active handles
	 */
	public static function getActiveHandles(){
		$usedHandles = self::$activeHandles;
		return $usedHandles;
	}

	/**
	 * Return the id for the entity with the given handle
	 * @param $handle string of handles
	 * @throws OutOfBoundsException
	 * @return null|string id of current handle (if active)
	 */
	public static function getId( $handle ){
		if( !array_key_exists( $handle, self::$activeHandles ) ){
			throw new OutOfBoundsException( "No entity id defined with handle {$handle}" );
		}
		return self::$activeHandles[ $handle ];
	}

	/**
	 * @param $id string of entityid
	 * @return null|string id of current handle (if active)
	 */
	public static function getHandle( $id ){
		if( array_key_exists( $id, self::$activeIds ) ){
			return self::$activeIds[ $id ];
		}
		return null;
	}


	/**
	 * Applies $idMap to all data in the given data structure, recursively.
	 *
	 * @param mixed $data
	 * @param array $idMap
	 */
	public static function injectIds( &$data, array &$idMap ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => &$value ) {
				self::injectIds( $value, $idMap );

				$newKey = $key;
				self::injectIds( $newKey, $idMap );

				if ( $newKey !== $key ) {
					$data[$newKey] = $value;
					unset( $data[$key] );
				}
			}
		} elseif ( is_string( $data ) ) {
			$data = str_replace( array_keys( $idMap ), array_values( $idMap ), $data );
		}
	}

	/**
	 * @param string $type
	 * @param array $data entity data (internal style)
	 *
	 * @return Entity
	 */
	private static function newEntity( $type, $serializedEntity ) {
		if ( $type === Property::ENTITY_TYPE ) {
			$entity = Property::newFromArray( $serializedEntity );
		} else {
			$entity = Item::newFromArray( $serializedEntity );
		}

		return $entity;
	}

	/**
	 * Creates an entity in the database.
	 *
	 * @param array $data as provided by getEntityData()
	 * @return Entity
	 */
	public static function createEntity( $data ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$rawEntityData = $data['data'];

		if ( is_string( $rawEntityData ) ) {
			$rawEntityData = json_decode( $rawEntityData, true );
		}

		if ( isset( $rawEntityData['claims'] ) ) {
			$claims = $rawEntityData['claims'];
			unset( $rawEntityData['claims'] );
		} else {
			$claims = null;
		}

		$serializedEntity = self::convertToInternalSerialization( $rawEntityData );
		$entity = self::newEntity( $data['type'], $serializedEntity );

		$store->saveEntity( $entity, 'init test entity', $GLOBALS['wgUser'], EDIT_NEW );
		$entityId = $entity->getId();

		if ( $claims ) {
			// If there are claims, inject GUIDs, put claims back into entity data, and save again.
			$claims = self::injectClaimGuids( $claims, $entityId );

			$rawEntityData['claims'] = $claims;
			$serializedEntity = self::convertToInternalSerialization( $rawEntityData );

			$entity = self::newEntity( $data['type'], $serializedEntity );
			$entity->setId( $entityId );

			$entity->getClaims();
			$store->saveEntity( $entity, 'claims for test entity', $GLOBALS['wgUser'], EDIT_UPDATE );
		}

		return $entity;
	}

	/**
	 * @param array[] $claims
	 * @param EntityId $entityId
	 *
	 * @return array[]
	 */
	private static function injectClaimGuids( $claims, EntityId $entityId ) {
		$generator = new ClaimGuidGenerator( $entityId );

		foreach ( $claims as &$claim ) {
			$claim['id'] = $generator->newGuid();
		}

		return $claims;
	}

	private static function convertToInternalSerialization( array $data ) {
		$internal = array();

		if ( isset( $data['labels'] ) ) {
			$internal['label'] = self::flattenArray( $data['labels'], 'language', 'value' );
		}

		if ( isset( $data['descriptions'] ) ) {
			$internal['description'] = self::flattenArray( $data['descriptions'], 'language', 'value' );
		}

		if ( isset( $data['aliases'] ) ) {
			$internal['aliases'] = self::flattenArray( $data['aliases'], 'language', 'value', true );
		}

		if ( isset( $data['sitelinks'] ) ) {
			$internal['links'] = self::flattenArray( $data['sitelinks'], 'site', 'title' );
		}

		if ( isset( $data['claims'] ) ) {
			$internal['claims'] = array();
			foreach ( $data['claims'] as $claim ) {
				$internal['claims'][] = self::convertClaimSerialization( $claim );
			};
		}

		return $internal;
	}

	/**
	 * @param array $claim external claim serialization
	 *
	 * @return array internal claim serialization
	 */
	private static function convertClaimSerialization( array $claim ) {
		$serialized = array();

		$serialized['m'] = self::convertSnakSerialization( $claim['mainsnak'] );

		$serialized['rank'] = Claim::RANK_NORMAL;
		if ( isset( $claim['rank'] ) ) {
			$ranks = array(
				'deprecated' => Claim::RANK_DEPRECATED,
				'normal' => Claim::RANK_NORMAL,
				'preferred' => Claim::RANK_PREFERRED
			);

			$key = $claim['rank'];

			$serialized['rank'] = $ranks[$key];
		}

		$serialized['g'] = null;
		if ( isset( $claim['id'] ) ) {
			$serialized['g'] = $claim['id'];
		}

		$serialized['q'] = array();
		if ( isset( $claim['qualifiers'] ) ) {
			foreach ( $claim['qualifiers'] as $snak ) {
				$serialized['qualifiers'][] = self::convertSnakSerialization( $snak );
			};
		}

		$serialized['refs'] = array();
		if ( isset( $claim['references'] ) ) {
			throw new LogicException( 'Conversion of references is not implemented' );
		}

		return $serialized;
	}

	/**
	 * @param array $snak external snak serialization
	 *
	 * @return array internal snak serialization
	 */
	private static function convertSnakSerialization( array $snak ) {
		$serialized = array();

		$serialized[0] = $snak['snaktype'];
		$serialized[1] = (int)substr( $snak['property'], 1 );

		if ( isset( $snak['datavalue'] ) ) {
			$serialized[2] = $snak['datavalue']['type'];
			$serialized[3] = $snak['datavalue']['value'];
		}

		return $serialized;
	}


	/**
	 * Utility function for converting an array from "deep" (indexed) to "flat" (keyed) structure.
	 * Arrays that already use a flat structure are left unchanged.
	 *
	 * Arrays with a deep structure are expected to be list of entries that are associative arrays,
	 * where which entry has at least the fields given by $keyField and $valueField.
	 *
	 * Arrays with a flat structure are associative and assign values to meaningful keys.
	 *
	 * @param array $data the input array.
	 * @param string $keyField the name of the field in each entry that shall be used as the key in the flat structure
	 * @param string $valueField the name of the field in each entry that shall be used as the value in the flat structure
	 * @param bool $multiValue whether the value in the flat structure shall be an indexed array of values instead of a single value.
	 * @param array $into optional aggregator.
	 *
	 * @return array array the flat version of $data
	 */
	public static function flattenArray( $data, $keyField, $valueField, $multiValue = false, array &$into = null ) {
		if ( $into === null ) {
			$into = array();
		}

		foreach ( $data as $index => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value[$keyField] ) && isset( $value[$valueField] ) ) {
					// found "deep" entry in the array
					$k = $value[ $keyField ];
					$v = $value[ $valueField ];
				} elseif ( isset( $value[0] ) && !is_array( $value[0] ) && $multiValue ) {
					// found "flat" multi-value entry in the array
					$k = $index;
					$v = $value;
				} else {
					// found list, recurse
					self::flattenArray( $value, $keyField, $valueField, $multiValue, $into );
					continue;
				}
			} else {
				// found "flat" entry in the array
				$k = $index;
				$v = $value;
			}

			if ( $multiValue ) {
				if ( is_array( $v ) ) {
					$into[$k] = empty( $into[$k] ) ? $v : array_merge( $into[$k], $v );
				} else {
					$into[$k][] = $v;
				}
			} else {
				$into[$k] = $v;
			}
		}

		return $into;
	}


	/**
	 * Loads an entity from the database (via an API call).
	 */
	public static function parseEntityId( $id ) {
		if ( is_string( $id ) ) {
			$parser = new BasicEntityIdParser();
			$id = $parser->parse( $id );
		}

		return $id;
	}

}
