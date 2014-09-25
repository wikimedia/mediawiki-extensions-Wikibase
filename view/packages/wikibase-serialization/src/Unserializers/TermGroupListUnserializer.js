/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/* Unserializer for TermGroupList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.TermGroupListUnserializer = util.inherit( 'WbTermGroupListUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.TermGroupList}
	 */
	unserialize: function( serialization ) {
		var termGroups = [],
			termGroupUnserializer = new MODULE.TermGroupUnserializer();

		for( var languageCode in serialization ) {
			termGroups.push( termGroupUnserializer.unserialize( serialization[languageCode] ) );
		}

		return new wb.datamodel.TermGroupList( termGroups );
	}
} );

}( wikibase, util ) );
