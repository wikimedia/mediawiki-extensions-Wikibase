/**
 * Entity initialization.
 * Unserializes the entity passed to JavaScript via mw.config variable.
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author: H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	var EntityInitializer = wb.EntityInitializer = function( configVarName ) {
		this._configVarName = configVarName;
	};

	$.extend( EntityInitializer.prototype, {

		_configVarName: null,

		_value: undefined,

		getEntity: function() {
			var self = this;
			var promise = $.Deferred();

			if( typeof this._value === 'undefined' ){
				this._getFromConfig().done( function( value ) {
					self._value = value;
					promise.resolve( self._value );
				} ).fail( $.proxy( promise.reject, promise ) );
			} else {
				promise.resolve( this._value );
			}

			return promise;
		},

		_getFromConfig: function() {
			var self = this;
			var promise = $.Deferred();

			mw.hook( 'wikipage.content' ).add( function() {
				var serializedEntity = mw.config.get( self._configVarName );

				if( serializedEntity === null ) {
					promise.reject();
					return;
				}

				var entityJSON = JSON.parse( serializedEntity ),
					unserializerFactory = new wb.serialization.SerializerFactory(),
					entityUnserializer = unserializerFactory.newUnserializerFor( wb.datamodel.Entity );

				promise.resolve( entityUnserializer.unserialize( entityJSON ) );
				entityJSON = null;
			} );

			return promise;
		}
	} );

} )( jQuery, mediaWiki, wikibase, void 0 );
