/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for TermGroup objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.TermGroupUnserializer = util.inherit( 'WbTermGroupUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.TermGroup}
	 */
	unserialize: function( serialization ) {
		if( !serialization.length ) {
			throw new Error( 'Unable to unserialize empty serialization to TermGroup' );
		}

		var languageCode = serialization[0].language,
			terms = [];

		for( var i = 0; i < serialization.length; i++ ) {
			terms.push( serialization[i].value );
		}

		return new wb.datamodel.TermGroup( languageCode, terms );
	}
} );

}( wikibase, util ) );
