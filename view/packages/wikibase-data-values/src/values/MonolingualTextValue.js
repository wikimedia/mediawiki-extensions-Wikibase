( function( dv, util ) {
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
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 * @extends dv.DataValue
 * @since 0.1
 *
 * @param {String} languageCode
 * @param {String} value
 */
dv.MonolingualTextValue = util.inherit( 'DvMonolingualTextValue', PARENT, constructor, {

	/**
	 * @inheritdoc
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getSortKey: function() {
		return this._languageCode + this._text;
	},

	/**
	 * @inheritdoc
	 *
	 * @since 0.1
	 *
	 * @return dv.MonolingualTextValue
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @inheritdoc
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
	 * @inheritdoc
	 *
	 * @since 0.1
	 */
	toJSON: function() {
		return {
			'text': this._text,
			'language': this._languageCode
		};
	},

	/**
	 * Returns the text.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getText: function() {
		return this._text;
	},

	/**
	 * Returns the language code of the values language.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getLanguageCode: function() {
		return this._languageCode;
	}

} );

dv.MonolingualTextValue.newFromJSON = function( json ) {
	return new dv.MonolingualTextValue( json.language, json.text );
};

dv.MonolingualTextValue.TYPE = 'monolingualtext';

dv.registerDataValue( dv.MonolingualTextValue );

}( dataValues, util ) );
