/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for ReferenceList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.ReferenceListDeserializer = util.inherit( 'WbReferenceListDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.ReferenceList}
	 */
	deserialize: function( serialization ) {
		var references = [],
			referenceDeserializer = new MODULE.ReferenceDeserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			references.push( referenceDeserializer.deserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.ReferenceList( references );
	}
} );

}( wikibase, util ) );
