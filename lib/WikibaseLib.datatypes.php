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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

return array(
	'commonsMedia'      => array( 'value-type' => 'string' ),
	'globe-coordinate'  => array( 'value-type' => 'globecoordinate' ),
	'monolingualtext'   => array( 'value-type' => 'monolingualtext' ),
	'quantity'          => array( 'value-type' => 'quantity' ),
	'string'            => array( 'value-type' => 'string' ),
	'time'              => array( 'value-type' => 'time' ),
	'url'               => array( 'value-type' => 'string' ),
	'wikibase-item'     => array( 'value-type' => 'wikibase-entityid' ),
	'wikibase-property' => array( 'value-type' => 'wikibase-entityid' ),
);
