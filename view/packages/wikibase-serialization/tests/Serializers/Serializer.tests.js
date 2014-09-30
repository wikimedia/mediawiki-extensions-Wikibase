/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.Serializer' );

QUnit.test( 'serialize()', function( assert ) {
	var SomeSerializer = util.inherit( 'WbTestSerializer', wb.serialization.Serializer, {} ),
		someSerializer = new SomeSerializer();

	assert.throws(
		function() {
			someSerializer.serialize( {} );
		},
		'Trying to serialize on a Serializer not having serialize() specified fails.'
	);
} );

}( wikibase, util, QUnit ) );
