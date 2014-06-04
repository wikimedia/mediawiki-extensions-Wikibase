/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( $, ExpertExtender, mw, MessageProvider ) {
	'use strict';

	/**
	 * An ExpertExtender module which shows a preview of a value
	 *
	 * @constructor
	 *
	 * @param {function} getUpstreamValue A getter for the current formatted upstream value
	 */
	ExpertExtender.Preview = function( getUpstreamValue ) {
		this._getUpstreamValue = getUpstreamValue;

		var messageProvider = null;
		if( mw && mw.msg ) {
			messageProvider = new MessageProvider( {
				messageGetter: mw.msg,
				prefix: 'valueview-preview-'
			} );
		}

		var $preview = $( '<div/>' ).preview( {
			messageProvider: messageProvider
		} );
		this._preview = $preview.data( 'preview' );
	};

	$.extend( ExpertExtender.Preview.prototype, {
		/**
		 * @type {function}
		 */
		_getUpstreamValue: null,

		/**
		 * @type {jQuery.ui.preview}
		 */
		_preview: null,

		/**
		 * Callback for the init ExpertExtender event
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ){
			$extender.append( this._preview.element );
		},

		/**
		 * Callback for the draw ExpertExtender event
		 */
		draw: function( ){
			this.update( this._getUpstreamValue() );
		},

		/**
		 * Callback for the destroy ExpertExtender event
		 */
		destroy: function( ){
			this._preview.destroy();
			this._preview.element.remove();

			this._preview = null;
			this._getUpstreamValue = null;
		},

		/**
		 * Public method for setting the preview's value
		 *
		 * @param {string} value HTML to show
		 */
		update: function( value ){
			this._preview.update( value );
		},

		/**
		 * Public method for replacing the preview with a spinner
		 */
		showSpinner: function() {
			this._preview && this._preview.showSpinner();
		}
	} );
} ( jQuery, jQuery.valueview.ExpertExtender, mediaWiki, util.MessageProvider ) );
