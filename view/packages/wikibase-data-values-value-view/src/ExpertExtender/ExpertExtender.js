( function( $, Extendable, vv ) {
	'use strict';

	/**
	 * @param jQuery $input
	 * @param object[] extensions An array of extensions for this ExpertExtender.
	 *                            An extension may have any of the following methods:
	 *                            - init( $container )
	 *                            - onInitialShow()
	 *                            - draw()
	 *                            - destroy()
	 */
	vv.ExpertExtender = function( $input, extensions ) {
		this.$input = $input;
		var extendable = this._extendable = new Extendable();

		$.each( extensions, function( k, extension ) {
			extendable.addExtension( extension );
		} );
	};
	$.extend( vv.ExpertExtender.prototype, {
		$input: null,
		_inputextender: null,
		_extendable: null,

		/**
		 * Callback for expert init
		 */
		init: function() {
			this.$input.inputextender( {
				initCallback: $.proxy( this._initExtensions, this ),
				contentAnimationEvents: 'toggleranimation'
			} );
			this._inputextender = this.$input.data( 'inputextender' );
		},

		/**
		 * Callback for expert draw
		 */
		draw: function() {
			if( this._inputextender.extensionIsVisible() ) {
				this._extendable.callExtensions( 'draw' );
			}
		},

		/**
		 * Callback for expert destroy
		 */
		destroy: function() {
			// Since inputextender is created in init, it might not be set
			if( this._inputextender ) {
				this._inputextender.destroy();
				this._inputextender = null;
			}

			this._extendable.callExtensions( 'destroy' );

			this.$input = null;
			this._extendable = null;
		},

		_initExtensions: function( $extender ) {
			var self = this;
			this._extendable.callExtensions( 'init', [ $extender ] );
			this.$input.one( 'inputextenderaftertoggle', function() {
				self._extendable.callExtensions( 'onInitialShow' );
				self._extendable.callExtensions( 'draw' );
			} );
		}
	} );
} ( jQuery, util.Extendable, jQuery.valueview ) );
