( function( $, Extendable, vv ) {
	'use strict';

	/**
	 * @class jQuery.valueview.ExpertExtender
	 * @since 0.6
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {jQuery} $input
	 * @param {Object[]} [extensions=[]] An array of extensions for this ExpertExtender.
	 *        An extension may have any of the following methods:
	 *        - init( $container )
	 *        - onInitialShow()
	 *        - draw()
	 *        - destroy()
	 */
	vv.ExpertExtender = function( $input, extensions ) {
		this.$input = $input;
		extensions = extensions || [];

		var extendable = this._extendable = new Extendable();

		$.each( extensions, function( k, extension ) {
			extendable.addExtension( extension );
		} );
	};
	$.extend( vv.ExpertExtender.prototype, {
		/**
		 * @property {jQuery}
		 * @private
		 * @readonly
		 */
		$input: null,

		/**
		 * @property {jQuery.ui.inputextender}
		 * @private
		 */
		_inputextender: null,

		/**
		 * @property {util.Extendable}
		 * @private
		 */
		_extendable: null,

		/**
		 * Callback for expert `init`.
		 */
		init: function() {
			this.$input.inputextender( {
				initCallback: this._initExtensions.bind( this ),
				contentAnimationEvents: 'toggleranimation'
			} );
			this._inputextender = this.$input.data( 'inputextender' );
		},

		/**
		 * Callback for expert `draw`.
		 */
		draw: function() {
			if ( this._inputextender.extensionIsVisible() ) {
				this._extendable.callExtensions( 'draw' );
			}
		},

		/**
		 * Callback for expert `destroy`.
		 */
		destroy: function() {
			// Since inputextender is created in init, it might not be set
			if ( this._inputextender ) {
				this._inputextender.destroy();
				this._inputextender = null;
			}

			this._extendable.callExtensions( 'destroy' );

			this.$input = null;
			this._extendable = null;
		},

		/**
		 * @private
		 *
		 * @param {jQuery} $extender
		 */
		_initExtensions: function( $extender ) {
			var self = this;
			this._extendable.callExtensions( 'init', [ $extender ] );
			this.$input.one( 'inputextenderaftertoggle', function() {
				self._extendable.callExtensions( 'onInitialShow' );
				self._extendable.callExtensions( 'draw' );
			} );
		}
	} );
}( jQuery, util.Extendable, jQuery.valueview ) );
