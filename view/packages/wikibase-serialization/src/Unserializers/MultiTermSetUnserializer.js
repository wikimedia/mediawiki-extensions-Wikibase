/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/* Unserializer for MultiTermSet objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.MultiTermSetUnserializer = util.inherit( 'WbMultiTermSetUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.MultiTermSet}
	 */
	unserialize: function( serialization ) {
		var multiTerms = [],
			multiTermUnserializer = new MODULE.MultiTermUnserializer();

		for( var languageCode in serialization ) {
			multiTerms.push( multiTermUnserializer.unserialize( serialization[languageCode] ) );
		}

		return new wb.datamodel.MultiTermSet( multiTerms );
	}
} );

}( wikibase, util ) );
