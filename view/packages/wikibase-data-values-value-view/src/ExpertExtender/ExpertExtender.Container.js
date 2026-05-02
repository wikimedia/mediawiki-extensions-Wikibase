( function( $, ExpertExtender ) {
	'use strict';

	/**
	 * An `ExpertExtender` module which wraps another module in a container.
	 *
	 * @class jQuery.valueview.ExpertExtender.Container
	 * @since 0.6
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {jQuery} $container
	 * @param {Object} child An ExpertExtender module
	 */
	ExpertExtender.Container = function( $container, child ) {
		this.$container = $container;
		this._child = child;
	};

	$.extend( ExpertExtender.Container.prototype, {
		/**
		 * @property {jQuery}
		 * @private
		 * @readonly
		 */
		$container: null,

		/**
		 * @property {Object}
		 * @private
		 */
		_child: null,

		/**
		 * Callback for the `init` `ExpertExtender` event.
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			$extender.append( this.$container );
			this._callChild( 'init', [ this.$container ] );
		},

		/**
		 * Callback for the `onInitialShow` `ExpertExtender` event.
		 */
		onInitialShow: function() {
			this._callChild( 'onInitialShow' );
		},

		/**
		 * Callback for the `draw` `ExpertExtender` event.
		 */
		draw: function() {
			this._callChild( 'draw' );
		},

		/**
		 * Callback for the `destroy` `ExpertExtender` event.
		 */
		destroy: function() {
			this.$container = null;
			this._callChild( 'destroy' );
			this._child = null;
		},

		/**
		 * A helper function for calling the child just like `util.Extendable` does.
		 *
		 * @private
		 *
		 * @param {string} method
		 * @param {*[]} [args]
		 */
		_callChild: function( method, args ) {
			if ( this._child[method] ) {
				this._child[method].apply( this._child, args || [] );
			}
		}
	} );
}( jQuery, jQuery.valueview.ExpertExtender ) );
