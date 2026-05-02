( function() {
	'use strict';

	var PARENT = require( './Serializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class TermSerializer
	 * @extends Serializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbTermSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {datamodel.Term} term
		 * @return {Object}
		 *
		 * @throws {Error} if term is not a Term instance.
		 */
		serialize: function( term ) {
			if( !( term instanceof datamodel.Term ) ) {
				throw new Error( 'Not an instance of datamodel.Term' );
			}

			return {
				language: term.getLanguageCode(),
				value: term.getText()
			};
		}
	} );

}() );
