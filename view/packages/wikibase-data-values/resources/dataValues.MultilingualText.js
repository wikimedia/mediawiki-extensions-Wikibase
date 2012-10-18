/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.Value,
	constructor = function( monoLingualValues ) {};

/**
 * Constructor for creating a multilingual text value. A multilingual text is a collection of
 * monolingual text values with the same meaning in different languages.
 *
 * @constructor
 * @extends dv.Value
 * @since 0.2
 *
 * @param {dv.MonolingualText[]} monoLingualValues
 */
dv.MultilingualText = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.Value.getType
	 */
	getType: function() {
		return 'multilingualtext';
	}
} );

}( dataValues, jQuery ) );
