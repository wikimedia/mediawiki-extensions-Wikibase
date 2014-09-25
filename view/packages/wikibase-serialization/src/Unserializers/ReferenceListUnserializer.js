/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for ReferenceList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ReferenceListUnserializer = util.inherit( 'WbReferenceListUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.ReferenceList}
	 */
	unserialize: function( serialization ) {
		var references = [],
			referenceUnserializer = new MODULE.ReferenceUnserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			references.push( referenceUnserializer.unserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.ReferenceList( references );
	}
} );

}( wikibase, util ) );
