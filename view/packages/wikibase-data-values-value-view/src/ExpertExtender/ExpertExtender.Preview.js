( function( $, ExpertExtender, PrefixingMessageProvider ) {
	'use strict';

	/**
	 * An `ExpertExtender` module which shows a preview of a value.
	 *
	 * @class jQuery.valueview.ExpertExtender.Preview
	 * @since 0.6
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {Function} getUpstreamValue A getter for the current formatted upstream value
	 * @param {util.MessageProvider} messageProvider
	 */
	ExpertExtender.Preview = function( getUpstreamValue, messageProvider ) {
		this._getUpstreamValue = getUpstreamValue;

		messageProvider = new PrefixingMessageProvider(
			'valueview-preview-',
			messageProvider
		);

		var $preview = $( '<div/>' ).preview( {
			messageProvider: messageProvider
		} );
		this._preview = $preview.data( 'preview' );
	};

	$.extend( ExpertExtender.Preview.prototype, {
		/**
		 * @property {Function}
		 * @private
		 */
		_getUpstreamValue: null,

		/**
		 * @property {jQuery.ui.preview}
		 * @private
		 */
		_preview: null,

		/**
		 * Callback for the `init` `ExpertExtender` event.
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			$extender.append( this._preview.element );
		},

		/**
		 * Callback for the draw ExpertExtender event
		 */
		draw: function() {
			this.update( this._getUpstreamValue() );
		},

		/**
		 * Callback for the `destroy` `ExpertExtender` event.
		 */
		destroy: function() {
			if ( this._preview ) {
				this._preview.destroy();
				this._preview.element.remove();
				this._preview = null;
			}

			this._getUpstreamValue = null;
		},

		/**
		 * Public method for setting the preview's value.
		 *
		 * @param {string} value HTML to show
		 */
		update: function( value ) {
			this._preview.update( value );
		},

		/**
		 * Public method for replacing the preview with a spinner.
		 */
		showSpinner: function() {
			if ( this._preview ) {
				this._preview.showSpinner();
			}
		}
	} );
}( jQuery, jQuery.valueview.ExpertExtender, util.PrefixingMessageProvider ) );
