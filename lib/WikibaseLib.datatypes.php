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

return [
	'PT:commonsMedia'      => [ 'value-type' => 'string' ],
	'PT:geo-shape'         => [ 'value-type' => 'string' ],
	'PT:globe-coordinate'  => [ 'value-type' => 'globecoordinate' ],
	'PT:monolingualtext'   => [ 'value-type' => 'monolingualtext' ],
	'PT:quantity'          => [ 'value-type' => 'quantity' ],
	'PT:string'            => [ 'value-type' => 'string' ],
	'PT:tabular-data'      => [ 'value-type' => 'string' ],
	'PT:time'              => [ 'value-type' => 'time' ],
	'PT:url'               => [ 'value-type' => 'string' ],
	'PT:external-id'       => [ 'value-type' => 'string' ],
	'PT:wikibase-item'     => [ 'value-type' => 'wikibase-entityid' ],
	'PT:wikibase-property' => [ 'value-type' => 'wikibase-entityid' ],
];
