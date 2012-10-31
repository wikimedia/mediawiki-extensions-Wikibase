/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( languageCode, value ) {
		this.languageCode = languageCode;
		this.text = value;
	};

/**
 * Constructor for creating a monolingual text value. A monolingual text is a string which is
 * dedicated to one specific language.
 *
 * @constructor
 * @extends dv.Value
 * @since 0.1
 *
 * @param {String} languageCode
 * @param {String} value
 */
dv.MonolingualTextValue = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.DataValue.getType
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getType: function() {
		return 'monolingualtext';
	},

	/**
	 * @see dv.DataValue.getSortKey
	 *
	 * @since 0.1
	 *
	 * @return String|Number
	 */
	getSortKey: function() {
		return this.languageCode + this.text;
	},

	/**
	 * @see dv.DataValue.getValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @see dv.DataValue.equals
	 *
	 * @since 0.1
	 *
	 * @return Boolean
	 */
	equals: function( value ) {
		if ( !( value instanceof dv.MonolingualTextValue ) ) {
			return false;
		}

		return this.text === value.getText() && this.languageCode === value.getLanguageCode();
	},

	/**
	 * @see dv.DataValue.toJSON
	 *
	 * @since 0.1
	 *
	 * @return Object
	 */
	toJSON: function( value ) {
		return this.value;
	},

	/**
	 * Returns the text.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	getText: function() {
		return this.text;
	},

	/**
	 * Returns the language code of the values language.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getLanguageCode: function() {
		return this.languageCode;
	}

} );

}( dataValues, jQuery ) );
