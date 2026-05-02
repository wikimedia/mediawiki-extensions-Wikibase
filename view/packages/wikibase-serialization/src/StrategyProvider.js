( function() {
	'use strict';

	/**
	 * @class StrategyProvider
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	var SELF = function WbSerializationStrategyProvider() {
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
		 *
		 * @throws {Error} if a strategy for the provided key is registered already.
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
		 *
		 * @throws {Error} if no strategy is registered for the key.
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

	module.exports = SELF;

}() );
