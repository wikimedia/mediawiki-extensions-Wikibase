<?php

namespace Wikibase\Repo\Tests\Api;

use OutOfBoundsException;

/**
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class EntityTestHelper {

	/**
	 * @var string[] List of currently active handles and their current ids
	 */
	private static $activeHandles = [];

	/**
	 * @var string[] List of currently active ids and their current handles
	 */
	private static $activeIds;

	/**
	 * @var array[] Handles and any registered default output data
	 */
	private static $entityOutput = [];

	/**
	 * @var array[] Set of pre defined entity data for use in tests
	 */
	private static $entityData = [
		'Empty' => [
			"new" => "item",
			"data" => [],
		],
		'Empty2' => [
			"new" => "item",
			"data" => [],
		],
		'StringProp' => [
			"new" => "property",
			"data" => [
				'datatype' => 'string',
			],
		],
		'Berlin' => [
			"new" => "item",
			"data" => [
				"sitelinks" => [
					[ "site" => "dewiki", "title" => "Berlin" ],
					[ "site" => "enwiki", "title" => "Berlin" ],
					[ "site" => "nlwiki", "title" => "Berlin" ],
					[ "site" => "nnwiki", "title" => "Berlin" ],
				],
				"labels" => [
					[ "language" => "de", "value" => "Berlin" ],
					[ "language" => "en", "value" => "Berlin" ],
					[ "language" => "nb", "value" => "Berlin" ],
					[ "language" => "nn", "value" => "Berlin" ],
				],
				"aliases" => [
					[ [ "language" => "de", "value" => "Dickes B" ] ],
					[ [ "language" => "en", "value" => "Dickes B" ] ],
					[ [ "language" => "nl", "value" => "Dickes B" ] ],
				],
				"descriptions" => [
					[ "language" => "de", "value" => "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland." ],
					[ "language" => "en", "value" => "Capital city and a federated state of the Federal Republic of Germany." ],
					[ "language" => "nb", "value" => "Hovedsted og delstat og i Forbundsrepublikken Tyskland." ],
					[ "language" => "nn", "value" => "Hovudstad og delstat i Forbundsrepublikken Tyskland." ],
				],
				"claims" => [
					[
						'mainsnak' => [
							'snaktype' => 'value',
							'property' => '%StringProp%',
							'datavalue' => [ 'value' => 'imastring1', 'type' => 'string' ],
						],
						'type' => 'statement',
						'rank' => 'normal',
						'qualifiers' => [
							 [
								'snaktype' => 'value',
								'property' => '%StringProp%',
								'datavalue' => [ 'value' => 'imastring1', 'type' => 'string' ],
								'datatype' => 'string',
							],
						],
						'references' => [
							[
								'snaks' => [
										[
											'snaktype' => 'value',
											'property' => '%StringProp%',
											'datavalue' => [ 'value' => 'imastring1', 'type' => 'string' ],
											'datatype' => 'string',
										],
								],
							],
						],
					],
				],
			],
		],
		'London' => [
			"new" => "item",
			"data" => [
				"sitelinks" => [
					[ "site" => "enwiki", "title" => "London" ],
					[ "site" => "dewiki", "title" => "London" ],
					[ "site" => "nlwiki", "title" => "London" ],
					[ "site" => "nnwiki", "title" => "London" ],
				],
				"labels" => [
					[ "language" => "de", "value" => "London" ],
					[ "language" => "en", "value" => "London" ],
					[ "language" => "nb", "value" => "London" ],
					[ "language" => "nn", "value" => "London" ],
				],
				"aliases" => [
					[
						[ "language" => "de", "value" => "City of London" ],
						[ "language" => "de", "value" => "Greater London" ],
					],
					[
						[ "language" => "en", "value" => "City of London" ],
						[ "language" => "en", "value" => "Greater London" ],
					],
					[
						[ "language" => "nl", "value" => "City of London" ],
						[ "language" => "nl", "value" => "Greater London" ],
					],
				],
				"descriptions" => [
					[ "language" => "de", "value" => "Hauptstadt Englands und des Vereinigten Königreiches." ],
					[ "language" => "en", "value" => "Capital city of England and the United Kingdom." ],
					[ "language" => "nb", "value" => "Hovedsted i England og Storbritannia." ],
					[ "language" => "nn", "value" => "Hovudstad i England og Storbritannia." ],
				],
			],
		],
		'Oslo' => [
			"new" => "item",
			"data" => [
				"sitelinks" => [
					[ "site" => "dewiki", "title" => "Oslo" ],
					[ "site" => "enwiki", "title" => "Oslo" ],
					[ "site" => "nlwiki", "title" => "Oslo" ],
					[ "site" => "nnwiki", "title" => "Oslo" ],
				],
				"labels" => [
					[ "language" => "de", "value" => "Oslo" ],
					[ "language" => "en", "value" => "Oslo" ],
					[ "language" => "nb", "value" => "Oslo" ],
					[ "language" => "nn", "value" => "Oslo" ],
				],
				"aliases" => [
					[
						[ "language" => "nb", "value" => "Christiania" ],
						[ "language" => "nb", "value" => "Kristiania" ],
					],
					[
						[ "language" => "nn", "value" => "Christiania" ],
						[ "language" => "nn", "value" => "Kristiania" ],
					],
					[ "language" => "de", "value" => "Oslo City" ],
					[ "language" => "en", "value" => "Oslo City" ],
					[ "language" => "nl", "value" => "Oslo City" ],
				],
				"descriptions" => [
					[ "language" => "de", "value" => "Hauptstadt der Norwegen." ],
					[ "language" => "en", "value" => "Capital city in Norway." ],
					[ "language" => "nb", "value" => "Hovedsted i Norge." ],
					[ "language" => "nn", "value" => "Hovudstad i Noreg." ],
				],
			],
		],
		'Episkopi' => [
			"new" => "item",
			"data" => [
				"sitelinks" => [
					[ "site" => "dewiki", "title" => "Episkopi Cantonment" ],
					[ "site" => "enwiki", "title" => "Episkopi Cantonment" ],
					[ "site" => "nlwiki", "title" => "Episkopi Cantonment" ],
				],
				"labels" => [
					[ "language" => "de", "value" => "Episkopi Cantonment" ],
					[ "language" => "en", "value" => "Episkopi Cantonment" ],
					[ "language" => "nl", "value" => "Episkopi Cantonment" ],
				],
				"aliases" => [
					[ "language" => "de", "value" => "Episkopi" ],
					[ "language" => "en", "value" => "Episkopi" ],
					[ "language" => "nl", "value" => "Episkopi" ],
				],
				"descriptions" => [
					[ "language" => "de", "value" => "Sitz der Verwaltung der Mittelmeerinsel Zypern." ],
					[ "language" => "en", "value" => "The capital of Akrotiri and Dhekelia." ],
					[ "language" => "nl", "value" => "Het bestuurlijke centrum van Akrotiri en Dhekelia." ],
				],
			],
		],
		'Osaka' => [
			"new" => "item",
			"data" => [
				"labels" => [
					[ "language" => "en", "value" => "Osaka" ],
				],
			],
		],
		'Leipzig' => [
			"new" => "item",
			"data" => [
				"labels" => [
					[ "language" => "de", "value" => "Leipzig" ],
				],
				"descriptions" => [
					[ "language" => "de", "value" => "Stadt in Sachsen." ],
					[ "language" => "en", "value" => "City in Saxony." ],
				],
			],
		],
		'Guangzhou' => [
			"new" => "item",
			"data" => [
				"labels" => [
					[ "language" => "de", "value" => "Guangzhou" ],
					[ "language" => "yue", "value" => "廣州" ],
					[ "language" => "zh-cn", "value" => "广州市" ],
				],
				"descriptions" => [
					[ "language" => "en", "value" => "Capital of Guangdong." ],
					[ "language" => "zh-hk", "value" => "廣東的省會。" ],
				],
			],
		],

	];

	/**
	 * Provides default values for the placeholders used in $entityData.
	 *
	 * @var string[] An associative array mapping placeholders to default values.
	 */
	public static $defaultPlaceholderValues = [
		'%StringProp%' => 'P56',
	];

	/**
	 * Get the entity with the given handle
	 *
	 * @param string $handle String handle of entity to get data for
	 *
	 * @throws OutOfBoundsException
	 * @return array of entity data
	 */
	public static function getEntity( $handle ) {
		if ( !array_key_exists( $handle, self::$entityData ) ) {
			throw new OutOfBoundsException( "No entity defined with handle {$handle}" );
		}
		$entity = self::$entityData[ $handle ];

		if ( !is_string( $entity['data'] ) ) {
			$entity['data'] = json_encode( $entity['data'] );
		}

		return $entity;
	}

	/**
	 * Get the data to pass to the api to clear the entity with the given handle
	 *
	 * @param string $handle String handle of entity to get data for
	 *
	 * @throws OutOfBoundsException
	 * @return array|null
	 */
	public static function getEntityClear( $handle ) {
		if ( !array_key_exists( $handle, self::$activeHandles ) ) {
			throw new OutOfBoundsException( "No entity clear data defined with handle {$handle}" );
		}
		$id = self::$activeHandles[ $handle ];
		self::unRegisterEntity( $handle );
		return [ 'id' => $id, 'data' => '{}', 'clear' => '' ];
	}

	/**
	 * Get the data to pass to the api to create the entity with the given handle
	 *
	 * @param string $handle
	 *
	 * @throws OutOfBoundsException
	 * @return mixed
	 */
	public static function getEntityData( $handle ) {
		if ( !array_key_exists( $handle, self::$entityData ) ) {
			throw new OutOfBoundsException( "No entity defined with handle {$handle}" );
		}
		return self::$entityData[ $handle ]['data'];
	}

	/**
	 * Get the data of the entity with the given handle we received after creation
	 *
	 * @param string $handle
	 * @param string[]|null $props Keys of entity elements we want the output to have.
	 * @param string[]|null $langs Language codes of labels, descriptions, and aliases we want the
	 *  output to have.
	 *
	 * @throws OutOfBoundsException
	 * @return array
	 */
	public static function getEntityOutput( $handle, array $props = null, array $langs = null ) {
		if ( !array_key_exists( $handle, self::$entityOutput ) ) {
			throw new OutOfBoundsException( "No entity output defined with handle {$handle}" );
		}
		if ( $props === null ) {
			return self::$entityOutput[ $handle ];
		} else {
			return self::stripUnwantedOutputValues( self::$entityOutput[ $handle ], $props, $langs );
		}
	}

	/**
	 * Remove props and langs that are not included in $props or $langs from the $entityOutput array
	 *
	 * @param array $entityOutput Array of entity output
	 * @param string[] $props Keys of entity elements to keep in the output.
	 * @param string[]|null $languageCodes Language codes of labels, descriptions, and aliases to
	 *  keep in the output.
	 *
	 * @return array Array of entity output with props and langs removed
	 */
	private static function stripUnwantedOutputValues(
		array $entityOutput,
		array $props,
		array $languageCodes = null
	) {
		$entityProps = [];
		$props[] = 'type'; // always return the type so we can demobilize
		foreach ( $props as $prop ) {
			if ( array_key_exists( $prop, $entityOutput ) ) {
				$entityProps[ $prop ] = $entityOutput[ $prop ];
			}
		}
		foreach ( $entityProps as $prop => $value ) {
			if ( ( $prop === 'labels' || $prop === 'descriptions' || $prop === 'aliases' )
				&& $languageCodes !== null
			) {
				$langValues = [];
				foreach ( $languageCodes as $langCode ) {
					if ( array_key_exists( $langCode, $value ) ) {
						$langValues[ $langCode ] = $value[ $langCode ];
					}
				}
				if ( $langValues === [] ) {
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
	 *
	 * @param string $handle
	 * @param string $id
	 * @param array|null $entity
	 */
	public static function registerEntity( $handle, $id, array $entity = null ) {
		self::$activeHandles[ $handle ] = $id;
		self::$activeIds[ $id ] = $handle;
		if ( $entity ) {
			self::$entityOutput[ $handle ] = $entity;
		}
	}

	/**
	 * Unregister the entity after it has been cleared
	 *
	 * @param string $handle
	 * @throws OutOfBoundsException
	 */
	public static function unRegisterEntity( $handle ) {
		unset( self::$activeIds[ self::$activeHandles[ $handle ] ] );
		unset( self::$activeHandles[ $handle ] );
	}

	/**
	 * @return string[] List of currently active (registered) handles, using IDs as keys.
	 */
	public static function getActiveHandles() {
		return self::$activeHandles;
	}

	/**
	 * @return string[] List of currently active (registered) IDs, using handles as keys.
	 */
	public static function getActiveIds() {
		return self::$activeIds;
	}

	/**
	 * Return the id for the entity with the given handle
	 *
	 * @param string $handle String handle of entity to get data for
	 *
	 * @throws OutOfBoundsException
	 * @return null|string id of current handle (if active)
	 */
	public static function getId( $handle ) {
		if ( !array_key_exists( $handle, self::$activeHandles ) ) {
			throw new OutOfBoundsException( "No entity id defined with handle {$handle}" );
		}
		return self::$activeHandles[ $handle ];
	}

	/**
	 * @param string $id string of entityid
	 * @return null|string id of current handle (if active)
	 */
	public static function getHandle( $id ) {
		if ( array_key_exists( $id, self::$activeIds ) ) {
			return self::$activeIds[ $id ];
		}
		return null;
	}

	/**
	 * Applies $idMap to all data in the given data structure, recursively.
	 *
	 * @param mixed &$data
	 * @param string[] &$idMap
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

}
