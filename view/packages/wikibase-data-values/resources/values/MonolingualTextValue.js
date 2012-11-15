/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( languageCode, text ) {
		// TODO: validate
		this._languageCode = languageCode;
		this._text = text;
	};

/**
 * Constructor for creating a monolingual text value. A monolingual text is a string which is
 * dedicated to one specific language.
 *
 * @constructor
 * @extends dv.DataValue
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
	 * @return String
	 */
	getSortKey: function() {
		return this._languageCode + this._text;
	},

	/**
	 * @see dv.DataValue.getValue
	 *
	 * @since 0.1
	 *
	 * @return dv.MonolingualTextValue
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @see dv.DataValue.equals
	 *
	 * @since 0.1
	 */
	equals: function( value ) {
		if ( !( value instanceof dv.MonolingualTextValue ) ) {
			return false;
		}

		return this.getText() === value.getText() && this._languageCode === value.getLanguageCode();
	},

	/**
	 * @see dv.DataValue.toJSON
	 *
	 * @since 0.1
	 */
	toJSON: function() {
		return this._text;
	},

	/**
	 * Returns the text.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getText: function() {
		return this._text;
	},

	/**
	 * Returns the language code of the values language.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getLanguageCode: function() {
		return this._languageCode;
	}

} );

}( dataValues, jQuery ) );
