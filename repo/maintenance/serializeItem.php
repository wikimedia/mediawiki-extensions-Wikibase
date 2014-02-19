<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class SerializeItem extends \Maintenance {

  public function __construct() {
    parent::__construct();

		$this->addDescription( 'Dump an item together with all used properties as JSON.' );

		$this->addArg( 'itemId', 'The item which should be dumped', true );
		$this->addOption( 'apiUrl', 'The URL of the API entry point that should be used. Defaults to the API of wikidata.org.', false, true );
  }

	public function finalSetup() {
		parent::finalSetup();
		$this->mApiUrl = $this->getOption( 'apiUrl' );
		if ( !$this->mApiUrl ) {
			$this->mApiUrl = "http://www.wikidata.org/w/api.php";
		}
	}

  public function execute() {
		$itemId = $this->getArg( );
		$item = $this->getEntity( $itemId );

		$this->properties = array();

		$this->flattenAndHandlePropIdKeyedMap( $item->claims, function( $claim ) {
			unset( $claim->id );

			$claim->mainsnak->property = $this->mapOldPropertyId( $claim->mainsnak->property );

			if( isset( $claim->qualifiers ) ) {
				$this->flattenAndHandlePropIdKeyedMap( $claim->qualifiers, function( $qualifier ) {
					$qualifier->property = $this->mapOldPropertyId( $qualifier->property );
					return $qualifier;
				} );
			}

			if( isset( $claim->{'qualifiers-order'} ) ) {
				$claim->{'qualifiers-order'} = array_map( array( $this, 'mapOldPropertyId' ), $claim->{'qualifiers-order'} );
			}

			if ( isset( $claim->references ) ) {
				$claim->references = array_map( function( $reference ) {
					unset( $reference->hash );

					$this->flattenAndHandlePropIdKeyedMap( $reference->snaks, function( $snak ) {
						$snak->property = $this->mapOldPropertyId( $snak->property );
						return $snak;
					} );

					$reference->{'snaks-order'} = array_map( array( $this, 'mapOldPropertyId' ), $reference->{'snaks-order'} );

					return $reference;
				}, $claim->references );
			}

			// FIXME: Handle wikibase-entityid datavalues

			return $claim;
		} );

		$this->properties = array_keys( $this->properties );
		$this->properties = array_combine(
			array_map( array( $this, 'mapOldPropertyId' ), $this->properties ),
			array_map( array( $this, 'getEntity' ), $this->properties )
		);

		echo json_encode( array(
			'entity' => $item,
			'properties' => $this->properties
		) );
	}

	protected function getEntity( $entityId ) {
		$raw = file_get_contents( "$this->mApiUrl?action=wbgetentities&ids=$entityId&format=json" );
		$decoded = json_decode( $raw );
		$entity = $decoded->entities->$entityId;

		foreach( array( 'pageid', 'ns', 'title', 'lastrevid', 'id', 'modified' ) as $k ) {
			unset( $entity->$k );
		}

		return $entity;
	}

	protected function mapOldPropertyId( $prop ) {
		return 'old' . $prop;
	}

	protected function flattenAndHandlePropIdKeyedMap( &$map, $cb ) {
		$newList = array();
		foreach( $map as $property => $list ) {
			if( !isset( $this->properties[ $property ] ) ) {
				$this->properties[ $property ] = true;
			}
			foreach( $list as $item ) {
				$newList[] = $cb( $item );
			}
		}
		$map = $newList;
	}
}

$maintClass = "Wikibase\SerializeItem";
require_once RUN_MAINTENANCE_IF_MAIN;
