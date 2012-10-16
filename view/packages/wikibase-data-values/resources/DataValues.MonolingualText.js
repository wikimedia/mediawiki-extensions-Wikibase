/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.Value,
	constructor = function( languageCode, value ) {};

/**
 * Constructor for creating a monolingual text value. A monolingual text is a string which is
 * dedicated to one specific language.
 *
 * @constructor
 * @extends dv.Value
 * @since 0.2
 *
 * @param {String} languageCode
 * @param {String} value
 */
dv.MonolingualText = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.Value.getType
	 */
	getType: function() {
		return 'string';
	},

	/**
	 * Returns the language code of the language the text is written in.
	 *
	 * @return String
	 */
	getLanguageCode: function() {}
} );

}( dataValues, jQuery ) );
