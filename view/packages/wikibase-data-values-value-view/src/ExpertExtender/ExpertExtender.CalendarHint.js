/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( $, ExpertExtender, Time ) {
	'use strict';

	/**
	 * An ExpertExtender module for showing a hint about the used calendar model
	 *
	 * @constructor
	 *
	 * @param {util.MessageProvider} messageProvider
	 * @param {function} getUpstreamValue A getter for the current DataValue instance in use
	 * @param {function} setUpstreamCalendar A setter for the current calendar name used
	 */
	ExpertExtender.CalendarHint = function( messageProvider, getUpstreamValue, setUpstreamCalendar ) {
		this._messageProvider = messageProvider;
		this._getUpstreamValue = getUpstreamValue;
		this._setUpstreamCalendar = setUpstreamCalendar;

		this.$calendarhint = $( '<div/>' );
	};

	$.extend( ExpertExtender.CalendarHint.prototype, {
		/**
		 * @type {util.MessageProvider}
		 */
		_messageProvider: null,

		/**
		 * @type {function}
		 */
		_getUpstreamValue: null,

		/**
		 * @type {function}
		 */
		_setUpstreamCalendar: null,

		/**
		 * @type {jQuery}
		 */
		$calendarhint: null,

		/**
		 * @type {string}
		 */
		_otherCalendar: null,

		/**
		 * The common prefix for CSS classes and message keys
		 *
		 * @type {string}
		 */
		_prefix: 'valueview-expertextender-calendarhint',

		/**
		 * Callback for the init ExpertExtender event
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			var self = this;

			this.$calendarhint
				.addClass( this._prefix )
				.append( $( '<span/>' ).addClass( this._prefix + '-message' ) )
				.append(
					$( '<a/>' )
					.addClass( this._prefix + '-switch ui-state-default' )
					.attr( 'href', 'javascript:void(0);' )
					.on( 'click', function( event ) {
						self._setUpstreamCalendar( self._otherCalendar );
					} )
				)
				.hide();

			$extender.append( this.$calendarhint );
		},

		/**
		 * Callback for the ExpertExtender draw event
		 */
		draw: function() {
			var value = this._getUpstreamValue();
			if( !value ) {
				return;
			}

			// Are we in the interesting range
			if( !( value.year() > 1581 && value.year() < 1930 && value.precision() > 10 ) ) {
				this.$calendarhint.hide();
				return;
			}

			var msg = this._messageProvider.getMessage(
				this._prefix + '-' + value.calendar().toLowerCase()
			);

			if( !msg ) {
				return;
			}

			this.$calendarhint.children( '.' + this._prefix + '-message' ).text( msg );

			this._otherCalendar = ( value.calendar() === Time.CALENDAR.GREGORIAN )
				? Time.CALENDAR.JULIAN
				: Time.CALENDAR.GREGORIAN;

			msg = this._messageProvider.getMessage(
				this._prefix + '-switch-' + this._otherCalendar.toLowerCase()
			);
			if( msg ) {
				this.$calendarhint.children( '.' + this._prefix + '-switch' ).html( msg );
			}

			this.$calendarhint.show();
		},

		/**
		 * Callback for the ExpertExtender destroy event
		 */
		destroy: function() {
			this._messageProvider = null;
			this._getUpstreamValue = null;
			this._setUpstreamCalendar = null;

			this.$calendarhint.remove();
			this.$calendarhint = null;

			this._otherCalendar = null;
		}
	} );
}( jQuery, jQuery.valueview.ExpertExtender, time.Time ) );
