( function( dv, util ) {
'use strict';

var PARENT = dv.DataValue;

/**
 * Constructor for creating a multilingual text value. A multilingual text is a collection of
 * monolingual text values with the same meaning in different languages.
 * @class dataValues.MultilingualTextValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @constructor
 *
 * @param {dataValues.MonolingualTextValue[]} monoLingualValues
 */
var SELF
	= dv.MultilingualTextValue
	= util.inherit( 'DvMultilingualTextValue', PARENT, function( monoLingualValues ) {
		// TODO: validate
		this._texts = monoLingualValues;
	},
{
	/**
	 * @property {dataValues.MonolingualTextValue[]}
	 * @private
	 */
	_texts: null,

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this._texts.length < 1 ? '' : this._texts[0].getSortKey();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {dataValues.MultilingualTextValue}
	 */
	getValue: function() {
		return this;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( value ) {
		if ( !( value instanceof dv.MultilingualTextValue ) ) {
			return false;
		}

		var a = this.toJSON(),
			b = value.toJSON();

		return !( a > b || b < a );
	},

	/**
	 * @inheritdoc
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		var texts = {};

		for ( var i in this._texts ) {
			texts[ this._texts[i].getLanguageCode() ] = this._texts[i].getText();
		}

		return texts;
	},

	/**
	 * Returns the text in all languages available.
	 *
	 * @return {string[]}
	 */
	getTexts: function() {
		return this._texts;
	}

} );

/**
 * @inheritdoc
 * @return {dataValues.MultilingualTextValue}
 */
SELF.newFromJSON = function( json ) {
	var monolingualValues = [];

	for ( var languageCode in json ) {
		if ( json.hasOwnProperty( languageCode ) ) {
			monolingualValues.push(
				new dv.MonolingualTextValue( languageCode, json[languageCode] )
			);
		}
	}

	return new SELF( monolingualValues );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='multilingualtext']
 * @static
 */
SELF.TYPE = 'multilingualtext';

dv.registerDataValue( SELF );

}( dataValues, util ) );
