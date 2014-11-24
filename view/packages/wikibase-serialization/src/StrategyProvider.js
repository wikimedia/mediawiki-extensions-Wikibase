( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * @class wikibase.serialization.StrategyProvider
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
var SELF = MODULE.StrategyProvider = function WbSerializationStrategyProvider() {
	this._strategies = [];
};

$.extend( SELF.prototype, {
	/**
	 * @property {Object[]}
	 * @private
	 */
	_strategies: null,

	/**
	 * @param {*} strategy
	 * @param {*} key
	 */
	registerStrategy: function( strategy, key ) {
		if( this._hasStrategyFor( key ) ) {
			throw new Error( 'Strategy for "' + key + '" is registered already' );
		}

		this._strategies.push( {
			key: key,
			strategy: strategy
		} );
	},

	/**
	 * @private
	 *
	 * @param {*} key
	 * @return {boolean}
	 */
	_hasStrategyFor: function( key ) {
		for( var i = 0; i < this._strategies.length; i++ ) {
			if( key === this._strategies[i].key ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * @param {*} key
	 * @return {*}
	 */
	getStrategyFor: function( key ) {
		for( var i = 0; i < this._strategies.length; i++ ) {
			if( key === this._strategies[i].key ) {
				return this._strategies[i].strategy;
			}
		}

		throw new Error( 'No strategy registered for "' + key + '"' );
	}
} );

}( wikibase, jQuery ) );
