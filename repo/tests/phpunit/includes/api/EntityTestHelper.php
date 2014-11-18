<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use OutOfBoundsException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class EntityTestHelper {

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @var string[] List of currently active handles and their current ids
	 */
	private static $activeHandles = array();

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @var string[] List of currently active ids and their current handles
	 */
	private static $activeIds;

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @var array[] Handles and any registered default output data
	 */
	private static $entityOutput = array();

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @var array[] Set of pre defined entity data for use in tests
	 */
	private static $entityData = array(
		'Empty' => array(
			"new" => "item",
			"data" => array(),
		),
		'Empty2' => array(
			"new" => "item",
			"data" => array(),
		),
		'StringProp' => array(
			"new" => "property",
			"data" => array(
				'datatype' => 'string'
			),
		),
		'Berlin' => array(
			"new" => "item",
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
						'property' => '%StringProp%',
						'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ),
					),
						'type' => 'statement',
						'rank' => 'normal' )
				),
			)
		),
		'London' => array(
			"new" => "item",
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
			"new" => "item",
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
			"new" => "item",
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
		'Osaka' => array(
			"new" => "item",
			"data" => array(
				"labels" => array(
					array( "language" => "en", "value" => "Osaka" )
				)
			)
		),
		'Leipzig' => array(
			"new" => "item",
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
			"new" => "item",
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
	 * Provides default values for the placeholders used in $entityData.
	 *
	 * @var string[] An associative array mapping placeholders to default values.
	 */
	public static $defaultPlaceholderValues = array(
		'%StringProp%' => 'P56'
	);

	/**
	 * @var Entity[] filled by EntityTestHelper::fillTestEntities
	 */
	private static $testEntities = array();

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
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
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
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
		return array( 'id' => $id, 'data' => '{}', 'clear' => '' );
	}

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
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
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * Get the data of the entity with the given handle we received after creation
	 *
	 * @param string $handle
	 * @param null|array $props array of props we want the output to have
	 * @param null|array $langs array of langs we want the output to have
	 *
	 * @throws OutOfBoundsException
	 * @return mixed
	 */
	public static function getEntityOutput( $handle, $props = null, $langs = null ) {
		if ( !array_key_exists( $handle, self::$entityOutput ) ) {
			throw new OutOfBoundsException( "No entity output defined with handle {$handle}" );
		}
		if ( !is_array( $props ) ) {
			return self::$entityOutput[ $handle ];
		} else {
			return self::stripUnwantedOutputValues( self::$entityOutput[ $handle ], $props, $langs );
		}
	}

	/**
	 * Remove props and langs that are not included in $props or $langs from the $entityOutput array
	 *
	 * @param array $entityOutput Array of entity output
	 * @param array $props Props to keep in the output
	 * @param null|array $langs Languages to keep in the output
	 *
	 * @return array Array of entity output with props and langs removed
	 */
	protected static function stripUnwantedOutputValues( $entityOutput, $props = array(), $langs = null  ) {
		$entityProps = array();
		$props[] = 'type'; // always return the type so we can demobilize
		foreach ( $props as $prop ) {
			if ( array_key_exists( $prop, $entityOutput ) ) {
				$entityProps[ $prop ] = $entityOutput[ $prop ] ;
			}
		}
		foreach ( $entityProps as $prop => $value ) {
			if ( ( $prop == 'aliases' || $prop == 'labels' || $prop == 'descriptions' ) && $langs != null && is_array( $langs ) ) {
				$langValues = array();
				foreach ( $langs as $langCode ) {
					if ( array_key_exists( $langCode, $value ) ) {
						$langValues[ $langCode ] = $value[ $langCode ];
					}
				}
				if ( $langValues === array() ) {
					unset( $entityProps[ $prop ] );
				} else {
					$entityProps[ $prop ] = $langValues;
				}

			}
		}
		return $entityProps;
	}

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * Register the entity after it has been created
	 *
	 * @param string $handle
	 * @param string $id
	 * @param array $entity
	 */
	public static function registerEntity( $handle, $id, $entity = null) {
		self::$activeHandles[ $handle ] = $id;
		self::$activeIds[ $id ] = $handle;
		if ( $entity ) {
			self::$entityOutput[ $handle ] = $entity;
		}
	}

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * Unregister the entity after it has been cleared
	 *
	 * @param string $handle
	 * @throws OutOfBoundsException
	 */
	private static function unRegisterEntity( $handle ) {
		unset( self::$activeIds[ self::$activeHandles[ $handle ] ] );
		unset( self::$activeHandles[ $handle ] );
	}

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @return string[] List of currently active (registered) handles, using IDs as keys.
	 */
	public static function getActiveHandles() {
		return self::$activeHandles;
	}

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @return string[] List of currently active (registered) IDs, using handles as keys.
	 */
	public static function getActiveIds() {
		return self::$activeIds;
	}

	/**
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
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
	 * @deprecated Please override the services in the API and use getTestEntity instead
	 *
	 * @param $id string of entityid
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
	 * @param mixed $data
	 * @param string[] $idMap
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
	 * @since 0.5
	 *
	 * @param EntityId|string $entityId
	 *
	 * @return null|Entity
	 */
	public static function getTestEntity( $entityId ) {
		self::fillTestEntitiesIfEmpty();

		if( $entityId instanceof EntityId ) {
			$key = $entityId->getSerialization();
		} else {
			$key = $entityId;
		}

		if( array_key_exists( $key, self::$testEntities ) ) {
			return self::$testEntities[ $key ];
		} else {
			return null;
			// FIXME? Should this throw an exception?
			// throw new StorageException( 'Thrown by: ' . __CLASS__ . __METHOD__ );
		}
	}

	/**
	 * Fills self::$testEntities with data (only if it is empty)
	 */
	private static function fillTestEntitiesIfEmpty() {
		if( self::$testEntities === array() ) {
			self::fillTestEntities();
		}
	}

	/**
	 * Fills self::$testEntities with data
	 */
	private static function fillTestEntities() {
		$entities = array();

		$entities['Q1'] = Item::newEmpty();
		$entities['Q1']->setId( ItemId::newFromNumber( 1 ) );

		$entities['Q2'] = Item::newEmpty();
		$entities['Q2']->setId( ItemId::newFromNumber( 2 ) );

		$entities['P1'] = Property::newFromType( 'string' );
		$entities['P1']->setId( PropertyId::newFromNumber( 1 ) );

		$entities['Q3'] = Item::newEmpty();
		$entities['Q3']->setId( ItemId::newFromNumber( 3 ) );
		$entities['Q3']->setFingerprint(
			new Fingerprint(
				new TermList(
					array(
						new Term( 'de', 'Berlin' ),
						new Term( 'en', 'Berlin' ),
						new Term( 'nb', 'Berlin' ),
						new Term( 'nn', 'Berlin' ),
					)
				),
				new TermList(
					array(
						new Term( 'de', 'Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland.' ),
						new Term( 'en', 'Capital city and a federated state of the Federal Republic of Germany.' ),
						new Term( 'nb', 'Hovedsted og delstat og i Forbundsrepublikken Tyskland.' ),
						new Term( 'nn', 'Hovudstad og delstat i Forbundsrepublikken Tyskland.' ),
					)
				),
				new AliasGroupList(
					array(
						new AliasGroup( 'de', array( 'Dickes B' ) ),
						new AliasGroup( 'en', array( 'Dickes B' ) ),
						new AliasGroup( 'nl', array( 'Dickes B' ) ),
					)
				)
			)
		);
		$entities['Q3']->addSiteLink( new SiteLink( 'dewiki', 'Berlin' ) );
		$entities['Q3']->addSiteLink( new SiteLink( 'enwiki', 'Berlin' ) );
		$entities['Q3']->addSiteLink( new SiteLink( 'nlwiki', 'Berlin' ) );
		$entities['Q3']->addSiteLink( new SiteLink( 'nnwiki', 'Berlin' ) );
		$claim = new Claim (
			new PropertyValueSnak(
				PropertyId::newFromNumber( 1 ),
				new StringValue( 'imastring1' )
			)
		);
		$claim->setGuid( 'Q3$E9DC0EA4-D0A0-429B-8F4D-048F2B5C9F73' );
		$entities['Q3']->addClaim( $claim );

		$entities['Q4'] = Item::newEmpty();
		$entities['Q4']->setId( ItemId::newFromNumber( 4 ) );
		$entities['Q4']->setFingerprint(
			new Fingerprint(
				new TermList(
					array(
						new Term( 'de', 'London' ),
						new Term( 'en', 'London' ),
						new Term( 'nb', 'London' ),
						new Term( 'nn', 'London' ),
					)
				),
				new TermList(
					array(
						new Term( 'de', 'Hauptstadt Englands und des Vereinigten Königreiches.' ),
						new Term( 'en', 'Capital city of England and the United Kingdom.' ),
						new Term( 'nb', 'Hovedsted i England og Storbritannia.' ),
						new Term( 'nn', 'Hovudstad i England og Storbritannia.' ),
					)
				),
				new AliasGroupList(
					array(
						new AliasGroup( 'de', array( 'City of London', 'Greater London' ) ),
						new AliasGroup( 'en', array( 'City of London', 'Greater London' ) ),
						new AliasGroup( 'nl', array( 'City of London', 'Greater London' ) ),
					)
				)
			)
		);
		$entities['Q4']->addSiteLink( new SiteLink( 'dewiki', 'London' ) );
		$entities['Q4']->addSiteLink( new SiteLink( 'enwiki', 'London' ) );
		$entities['Q4']->addSiteLink( new SiteLink( 'nlwiki', 'London' ) );
		$entities['Q4']->addSiteLink( new SiteLink( 'nnwiki', 'London' ) );

		$entities['Q5'] = Item::newEmpty();
		$entities['Q5']->setId( ItemId::newFromNumber( 5 ) );
		$entities['Q5']->setFingerprint(
			new Fingerprint(
				new TermList(
					array(
						new Term( 'de', 'Oslo' ),
						new Term( 'en', 'Oslo' ),
						new Term( 'nb', 'Oslo' ),
						new Term( 'nn', 'Oslo' ),
					)
				),
				new TermList(
					array(
						new Term( 'de', 'Hauptstadt der Norwegen.' ),
						new Term( 'en', 'Capital city in Norway.' ),
						new Term( 'nb', 'Hovedsted i Norge.' ),
						new Term( 'nn', 'Hovudstad i Noreg.' ),
					)
				),
				new AliasGroupList(
					array(
						new AliasGroup( 'nb', array( 'Christiania', 'Kristiania' ) ),
						new AliasGroup( 'nn', array( 'Christiania', 'Kristiania' ) ),
						new AliasGroup( 'de', array( 'Oslo City' ) ),
						new AliasGroup( 'en', array( 'Oslo City' ) ),
						new AliasGroup( 'nl', array( 'Oslo City' ) ),
					)
				)
			)
		);
		$entities['Q5']->addSiteLink( new SiteLink( 'dewiki', 'Oslo' ) );
		$entities['Q5']->addSiteLink( new SiteLink( 'enwiki', 'Oslo' ) );
		$entities['Q5']->addSiteLink( new SiteLink( 'nlwiki', 'Oslo' ) );
		$entities['Q5']->addSiteLink( new SiteLink( 'nnwiki', 'Oslo' ) );

		$entities['Q6'] = Item::newEmpty();
		$entities['Q6']->setId( ItemId::newFromNumber( 6 ) );
		$entities['Q6']->setFingerprint(
			new Fingerprint(
				new TermList(
					array(
						new Term( 'de', 'Episkopi Cantonment' ),
						new Term( 'en', 'Episkopi Cantonment' ),
						new Term( 'nl', 'Episkopi Cantonment' ),
					)
				),
				new TermList(
					array(
						new Term( 'de', 'Sitz der Verwaltung der Mittelmeerinsel Zypern.' ),
						new Term( 'en', 'The capital of Akrotiri and Dhekelia.' ),
						new Term( 'nl', 'Het bestuurlijke centrum van Akrotiri en Dhekelia.' ),
					)
				),
				new AliasGroupList(
					array(
						new AliasGroup( 'de', array( 'Episkopi' ) ),
						new AliasGroup( 'en', array( 'Episkopi' ) ),
						new AliasGroup( 'nl', array( 'Episkopi' ) ),
					)
				)
			)
		);
		$entities['Q6']->addSiteLink( new SiteLink( 'dewiki', 'Episkopi Cantonment' ) );
		$entities['Q6']->addSiteLink( new SiteLink( 'enwiki', 'Episkopi Cantonment' ) );
		$entities['Q6']->addSiteLink( new SiteLink( 'nlwiki', 'Episkopi Cantonment' ) );

		$entities['Q7'] = Item::newEmpty();
		$entities['Q7']->setId( ItemId::newFromNumber( 7 ) );
		$entities['Q7']->setFingerprint(
			new Fingerprint(
				new TermList(
					array(
						new Term( 'de', 'Leipzig' ),
					)
				),
				new TermList(
					array(
						new Term( 'de', 'Stadt in Sachsen.' ),
						new Term( 'en', 'City in Saxony.' ),
					)
				),
				new AliasGroupList()
			)
		);

		$entities['Q8'] = Item::newEmpty();
		$entities['Q8']->setId( ItemId::newFromNumber( 8 ) );
		$entities['Q8']->setFingerprint(
			new Fingerprint(
				new TermList(
					array(
						new Term( 'de', 'Guangzhou' ),
						new Term( 'yue', "廣州" ),
						new Term( 'zh-cn', "广州市" ),
					)
				),
				new TermList(
					array(
						new Term( 'en', 'Capital of Guangdong.' ),
						new Term( 'zh-hk', "廣東的省會。" ),
					)
				),
				new AliasGroupList()
			)
		);

		self::$testEntities = $entities;
	}

}
