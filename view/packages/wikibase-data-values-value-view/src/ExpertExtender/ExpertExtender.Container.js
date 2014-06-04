/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( $, ExpertExtender ) {
	'use strict';

	/**
	 * An ExpertExtender module which wraps another module in a container
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
		 * @type {jQuery}
		 */
		$container: null,

		/**
		 * @type {Object}
		 */
		_child: null,

		/**
		 * Callback for the init ExpertExtender event
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			$extender.append( this.$container );
			this._callChild( 'init', [ this.$container ] );
		},

		/**
		 * Callback for the onInitialShow ExpertExtender event
		 */
		onInitialShow: function() {
			this._callChild( 'onInitialShow' );
		},

		/**
		 * Callback for the draw ExpertExtender event
		 */
		draw: function() {
			this._callChild( 'draw' );
		},

		/**
		 * Callback for the destroy ExpertExtender event
		 */
		destroy: function() {
			this.$container = null;
			this._callChild( 'destroy' );
			this._child = null;
		},

		/**
		 * A helper function for calling the child just like util.Extendable does
		 *
		 * @param {string} method
		 * @param [Array] args
		 */
		_callChild: function( method, args ) {
			var m = this._child[ method ];
			m && m.apply( this._child, args || [] );
		}
	} );
} ( jQuery, jQuery.valueview.ExpertExtender ) );
