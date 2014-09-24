/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/* Unserializer for TermList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.TermListUnserializer = util.inherit( 'WbTermListUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.TermList}
	 */
	unserialize: function( serialization ) {
		var terms = [],
			termUnserializer = new MODULE.TermUnserializer();

		for( var languageCode in serialization ) {
			terms.push( termUnserializer.unserialize( serialization[languageCode] ) );
		}

		return new wb.datamodel.TermList( terms );
	}
} );

}( wikibase, util ) );
