/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * @constructor
 * @since 2.0
 */
var SELF = MODULE.StrategyProvider = function WbSerializationStrategyProvider() {
	this._strategies = [];
};

$.extend( SELF.prototype, {
	/**
	 * @type {Object[]}
	 */
	_strategies: null,

	/**
	 * @param {*} strategy
	 * @param {string} key
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
	 * @param {string} key
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
	 * @param {string} key
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
