/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( monoLingualValues ) {
		this.texts = monoLingualValues;
	};

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
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getType: function() {
		return 'multilingualtext';
	},

	/**
	 * @see dv.DataValue.getSortKey
	 *
	 * @since 0.1
	 *
	 * @return String|Number
	 */
	getSortKey: function() {
		return this.texts.length < 1 ? '' : this.texts[0].getSortKey();
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
		if ( !( value instanceof dv.MultilingualTextValue ) ) {
			return false;
		}

		var
			a = this.toJSON(),
			b = value.toJSON();

		return !( a > b || b < a );
	},

	/**
	 * @see dv.DataValue.toJSON
	 *
	 * @since 0.1
	 *
	 * @return Object
	 */
	toJSON: function( value ) {
		var
			texts = [],
			i;

		for ( i in this.texts ) {
			texts[this.texts[i].getLanguageCode()] = this.texts[i].getText();
		}

		return texts;
	},

	/**
	 * Returns the text.
	 *
	 * @since 0.1
	 *
	 * @return Array
	 */
	getTexts: function() {
		return this.texts;
	}

} );

}( dataValues, jQuery ) );
