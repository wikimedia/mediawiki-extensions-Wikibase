/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @param {string} lang
 * @param {string[]} array
 * @returns {*}
 */
function serializeLanguageArray( lang, array ) {
	return $.map( array, function( value ) {
		return serializeLanguageValue( lang, value );
	} );
}

/**
 * @param {string} lang
 * @param {string} value
 * @return {Object}
 */
function serializeLanguageValue( lang, value ) {
	return {
		language: lang,
		value: value
	};
}

/**
 * Serializer for multilingual values.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 1.2
 */
MODULE.MultilingualSerializer = util.inherit( 'WbMultilingualSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {Object} unserialized
	 * @return {Object}
	 */
	serialize: function( unserialized ) {
		var serialization = {};

		for( var lang in unserialized ) {
			serialization[lang] = $.isArray( unserialized[lang] )
				? serializeLanguageArray( lang, unserialized[lang] )
				: serializeLanguageValue( lang, unserialized[lang] );
		}

		return serialization;
	}
} );

}( wikibase, util, jQuery ) );
