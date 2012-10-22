/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( monoLingualValues ) {};

/**
 * Constructor for creating a multilingual text value. A multilingual text is a collection of
 * monolingual text values with the same meaning in different languages.
 *
 * @constructor
 * @extends dv.Value
 * @since 0.1
 *
 * @param {dv.MonolingualTextValue[]} monoLingualValues
 */
dv.MultilingualTextValue = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.DataValue.getType
	 */
	getType: function() {
		return 'multilingualtext';
	}
} );

}( dataValues, jQuery ) );
