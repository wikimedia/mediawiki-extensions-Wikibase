<?php

/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoDataTypes
 * resp. $wgWBClientDataTypes. The basic definition contains only the 'value-type' field.
 *
 * @note: When adding data types here, also add the corresponding information to
 * repo/Wikibase.datatypes.php and client/WikibaseClient.datatypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */

return array(
	'PT:commonsMedia'      => array( 'value-type' => 'string' ),
	'PT:geo-shape'         => array( 'value-type' => 'string' ),
	'PT:globe-coordinate'  => array( 'value-type' => 'globecoordinate' ),
	'PT:monolingualtext'   => array( 'value-type' => 'monolingualtext' ),
	'PT:quantity'          => array( 'value-type' => 'quantity' ),
	'PT:string'            => array( 'value-type' => 'string' ),
	'PT:tabular-data'      => array( 'value-type' => 'string' ),
	'PT:time'              => array( 'value-type' => 'time' ),
	'PT:url'               => array( 'value-type' => 'string' ),
	'PT:external-id'       => array( 'value-type' => 'string' ),
	'PT:wikibase-item'     => array( 'value-type' => 'wikibase-entityid' ),
	'PT:wikibase-property' => array( 'value-type' => 'wikibase-entityid' ),
);
