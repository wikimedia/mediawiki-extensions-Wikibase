/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * @param {Object[]} array
 * @return {*[]}
 */
function extractValuesFromObjectArray( array ) {
	return $.map( array, function( valueObj ) {
		return valueObj.value;
	} );
}

/**
 * Unserializer for multilingual values.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 1.2
 */
MODULE.MultilingualUnserializer = util.inherit( 'WbMultilingualUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {Object}
	 */
	unserialize: function( serialization ) {
		var unserialized = {};

		if( !serialization ) {
			return unserialized;
		}

		for( var lang in serialization ) {
			unserialized[lang] = $.isArray( serialization[lang] )
				? extractValuesFromObjectArray( serialization[lang] )
				: serialization[lang].value;
		}

		return unserialized;
	}
} );

}( wikibase, util, jQuery ) );
