( function( dv, util ) {
'use strict';

var PARENT = dv.DataValue;

/**
 * Constructor for creating a monolingual text value. A monolingual text is a string which is
 * dedicated to one specific language.
 * @class dataValues.MonolingualTextValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 *
 * @param {String} languageCode
 * @param {String} value
 */
var SELF
	= dv.MonolingualTextValue
	= util.inherit( 'DvMonolingualTextValue', PARENT, function( languageCode, text ) {
		// TODO: validate
		this._languageCode = languageCode;
		this._text = text;
	},
{
	/**
	 * @property {string}
	 * @private
	 */
	_languageCode: null,

	/**
	 * @property {string}
	 * @private
	 */
	_text: null,

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	getSortKey: function() {
		return this._languageCode + this._text;
	},

	/**
	 * @inheritdoc
	 *
	 * @return {dataValues.MonolingualTextValue}
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @inheritdoc
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
	 * @return {Object}
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
	 * @return {string}
	 */
	getText: function() {
		return this._text;
	},

	/**
	 * Returns the language code of the value's language.
	 *
	 * @return {string}
	 */
	getLanguageCode: function() {
		return this._languageCode;
	}

} );

/**
 * @inheritdoc
 *
 * @return {dataValues.MonolingualTextValue}
 */
SELF.newFromJSON = function( json ) {
	return new SELF( json.language, json.text );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='monolingualtext']
 */
SELF.TYPE = 'monolingualtext';

dv.registerDataValue( SELF );

}( dataValues, util ) );
