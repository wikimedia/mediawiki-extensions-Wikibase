<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for generating a JSON dump of an item together with the used properties.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */

class SerializeItem extends \Maintenance {

	private $mApiUrl;
	private $properties;
	private $schema = array(
		'attributes' => array(
			'claims' => array(
				'type' => 'ByPropertyIdArray',
				'members' => array(
					'remove' => array( 'id' ),
					'attributes' => array(
						'mainsnak' => array(
							'attributes' => array(
								'property' => array( 'type' => 'PropertyId' )
							)
						),
						'qualifiers' => array(
							'type' => 'ByPropertyIdArray',
							'members' => array(
								'attributes' => array(
									'property' => array( 'type' => 'PropertyId' )
								)
							)
						),
						'qualifiers-order' => array(
							'type' => 'Array',
							'members' => array( 'type' => 'PropertyId' )
						),
						'references' => array(
							'type' => 'Array',
							'members' => array(
								'remove' => array( 'hash' ),
								'attributes' => array(
									'snaks' => array(
										'type' => 'ByPropertyIdArray',
										'members' => array(
											'attributes' => array(
												'property' => array( 'type' => 'PropertyId' )
											)
										)
									),
									'snaks-order' => array(
										'type' => 'Array',
										'members' => array( 'type' => 'PropertyId' )
									)
								)
							)
						)
					)
				)
			)
		)
	);

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Dump an item together with all used properties as JSON ' .
			'in the external JSON format.' );

		$this->addArg( 'itemId', 'The item which should be dumped', true );
		$this->addOption( 'apiUrl', 'The URL of the API entry point that should be used. ' .
			'Defaults to the API of wikidata.org.', false, true );
	}

	public function finalSetup() {
		parent::finalSetup();
		$this->mApiUrl = $this->getOption( 'apiUrl', 'http://www.wikidata.org/w/api.php' );
	}

	public function execute() {
		$itemId = $this->getArg( );
		$item = $this->getEntity( $itemId );

		$this->properties = array();

		$item = $this->handleObject( $item, $this->schema );

		$this->properties = array_keys( $this->properties );
		$this->properties = array_combine(
			array_map( array( $this, 'handlePropertyId' ), $this->properties ),
			array_map( array( $this, 'getEntity' ), $this->properties )
		);

		echo json_encode( array(
			'entity' => $item,
			'properties' => $this->properties
		) );
	}

	protected function getEntity( $entityId ) {
		$raw = file_get_contents( "{$this->mApiUrl}?action=wbgetentities&ids=$entityId&format=json" );
		$decoded = json_decode( $raw );
		if( $decoded->success !== 1 ) {
			die( "Invalid JSON returned for $entityId" );
		}

		$entity = $decoded->entities->$entityId;

		foreach( array( 'pageid', 'ns', 'title', 'lastrevid', 'id', 'modified' ) as $k ) {
			unset( $entity->$k );
		}

		return $entity;
	}

	// FIXME: This might use the DataModel classes
	// FIXME: Handle wikibase-entityid datavalues
	protected function handleObject( $item, $schema ) {
		if( isset( $schema[ 'type' ] ) ) {
			switch( $schema[ 'type' ] ) {
			case 'ByPropertyIdArray':
				$item = $this->handleByPropertyIdArray( $item );
				$item = $this->handleArray( $item, $schema );
				break;
			case 'Array':
				$item = $this->handleArray( $item, $schema );
				break;
			case 'PropertyId':
				$item = $this->handlePropertyId( $item );
				break;
			}
		}
		if( isset( $schema[ 'remove' ] ) ) {
			foreach( $schema[ 'remove' ] as $k ) {
				unset( $item->$k );
			}
		}
		if( isset( $schema[ 'attributes' ] ) ) {
			foreach( $schema[ 'attributes' ] as $k => $v ) {
				if( isset( $item->$k ) ) {
					$item->$k = $this->handleObject( $item->$k, $v );
				}
			}
		}

		return $item;
	}

	protected function handlePropertyId( $prop ) {
		return 'old' . $prop;
	}

	protected function handleByPropertyIdArray( $array ) {
		$newList = array();
		foreach( $array as $property => $list ) {
			if( !isset( $this->properties[ $property ] ) ) {
				$this->properties[ $property ] = true;
			}
			foreach( $list as $item ) {
				$newList[] = $item;
			}
		}
		return $newList;
	}

	protected function handleArray( $array, $schema ) {
		$memberSchema = $schema[ 'members' ];
		$array = array_map( function( $member ) use ($memberSchema) {
			return $this->handleObject( $member, $memberSchema );
		}, $array );
		return $array;
	}
}

$maintClass = "Wikibase\SerializeItem";
require_once RUN_MAINTENANCE_IF_MAIN;
