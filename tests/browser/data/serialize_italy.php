<?php

function getWikidataEntity( $entityId ) {
	$raw = file_get_contents( "http://www.wikidata.org/w/api.php?action=wbgetentities&ids=$entityId&format=json" );
	$decoded = json_decode( $raw );
	$entity = $decoded->entities->$entityId;
	foreach( array( 'pageid', 'ns', 'title', 'lastrevid', 'id', 'modified' ) as $k ) {
		unset( $entity->$k );
	}
	return $entity;
}

$italy = getWikidataEntity( "Q38" );
$properties = array();
$newClaims = array();

foreach( $italy->claims as $claimList ) {
	foreach( $claimList as $claim ) {
		unset( $claim->id );
		if( !isset( $properties[ $claim->mainsnak->property ] ) ) {
			$properties[ $claim->mainsnak->property ] = $claim->mainsnak->datavalue->{'type'}; //FIXME: datatype
		}
		// FIXME: Add them instead of unsetting
		unset( $claim->qualifiers );
		unset( $claim->{'qualifiers-order'} );
		unset( $claim->references );

		// FIXME: Handle wikibase-entityid datavalues

		$newClaims[] = $claim;
	}
}
$italy->claims = $newClaims;

foreach( $properties as $id => $type ) {
	$properties[$id] = getWikidataEntity( $id );
}

echo json_encode( array(
	'entity' => $italy,
	'properties' => $properties
) );
