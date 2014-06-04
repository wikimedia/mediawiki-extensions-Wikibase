/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( $, ExpertExtender ) {
	'use strict';

	/**
	 * An ExpertExtender module which toggles DOM elements
	 *
	 * @constructor
	 *
	 * @param {util.MessageProvider} messageProvider
	 * @param {jQuery} $subject
	 */
	ExpertExtender.Toggler = function( messageProvider, $subject ) {
		this._messageProvider = messageProvider;
		this.$subject = $subject;
		this.$toggler = $( '<a/>' );
	};

	$.extend( ExpertExtender.Toggler.prototype, {
		/**
		 * @type {util.MessageProvider}
		 */
		_messageProvider: null,

		/**
		 * @type {jQuery}
		 */
		$toggler: null,

		/**
		 * @type {jQuery}
		 */
		$subject: null,

		/**
		 * Callback for the init ExpertExtender event
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			this.$toggler
				.addClass( 'valueview-expertextender-advancedtoggler' )
				.text( this._messageProvider.getMessage( 'valueview-expert-advancedadjustments' ) );
			$extender.append( this.$toggler );
		},

		/**
		 * Callback for the onInitialShow ExpertExtender event
		 */
		onInitialShow: function() {
			this.$toggler.toggler( { $subject: this.$subject } );
			this.$subject.hide();
		},

		/**
		 * Callback for the destroy ExpertExtender event
		 */
		destroy: function() {
			var toggler = this.$toggler.data( 'toggler' );
			if( toggler ) {
				toggler.destroy();
			}
			this.$toggler = null;
			this.$subject = null;
			this._messageProvider = null;
		}
	} );
} ( jQuery, jQuery.valueview.ExpertExtender ) );
