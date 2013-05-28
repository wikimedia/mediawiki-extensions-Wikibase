/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vp, $ ) {
	'use strict';

	var PARENT = vp.ValueParser,
		constructor = function( options ) {
			if ( !options.prefixmap ) {
				throw new Error( 'EntityIdParser: Prefix map required for initialization.' );
			}
			PARENT.call( this, options );
		};

	/**
	 * Constructor for an entity id parser.
	 *
	 * @constructor
	 * @extends vp.ValueParser
	 * @since 0.4
	 */
	wb.EntityIdParser = vp.util.inherit( 'WbEntityIdParser', PARENT, constructor, {

		/**
		 * @see vp.ValueParser.parse
		 * @since 0.4
		 *
		 * @param {string} rawValue
		 * @return {$.Promise}
		 */
		parse: function( rawValue ) {
			var deferred = $.Deferred(),
				entityType = null,
				numericId = null;

			$.each( this._options.prefixmap, function( prefix, type ) {
				if ( rawValue.substr( 0, prefix.length ) === prefix ) {
					numericId = rawValue.substr( prefix.length );
					if ( ( /^\d+$/ ).test( numericId ) ) {
						numericId = parseInt( numericId, 10 );
						entityType = type;
						return false;
					}
				}
			} );

			if ( entityType ) {
				deferred.resolve( new wb.EntityId( entityType, numericId ) );
			} else {
				// TODO: Use a proper Error object to transport detailed information about the failure.
				deferred.reject( 'parsererror' );
			}

			return deferred.promise();
		}
	} );

}( wikibase, valueParsers, jQuery ) );
