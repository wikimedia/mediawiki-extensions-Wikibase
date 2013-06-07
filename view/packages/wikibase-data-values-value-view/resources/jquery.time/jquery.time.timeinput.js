/**
 * Input element that interprets time values.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @event update: Triggered whenever the widget's value is updated.
 *        (1) {jQuery.Event}
 *        (2) {time.Time|null} New value (null for no or an invalid value) the widget's value has
 *            been changed to.
 *
 * @dependency jQuery.ui.Widget
 * @dependency jQuery.eachchange
 * @dependency time.Time
 */
( function( $, Time ) {
	'use strict';

	$.widget( 'time.timeinput', {
		/**
		 * Default options.
		 * @type {Object}
		 */
		options: {
			mediaWiki: null
		},

		/**
		 * Caches the widget's current value.
		 * @type {time.Time|null}
		 */
		_value: null,

		/**
		 * @see jQuery.ui.autocomplete._create
		 */
		_create: function() {
			var self = this;

			this.element.addClass( this.widgetName );

			this.element.eachchange( function( event, oldValue ) {
				var value;

				try {
					value = self._parse();
				} catch( e ) {
					value = null;
				}

				if( value !== self._value ) {
					self._value = value;
					self._trigger( 'update', null, [self._value] );
				}
			} );
		},

		/**
		 * @see jQuery.ui.Widget.destroy
		 */
		destroy: function() {
			this.element.removeClass( this.widgetName );
			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * Parses the current input value.
		 *
		 * @return {time.Time|null} Time object when parsing was successful.
		 *
		 * @throws {Error} When no time.Time object could be instantiated.
		 */
		_parse: function() {
			return new Time( this.element.val() );
		},

		/**
		 * Sets/Gets the widget's value.
		 *
		 * @param {time.Time} [value]
		 * @returns {time.Time|null}
		 */
		value: function( value ) {
			if( value === undefined ) {
				return this._value;
			}

			if( value !== null && ( !( value instanceof Time ) ) ) {
				throw new Error( 'Cannot set value: Neither time.Time object nor \'null\' given.' );
			}

			if( value === null ) {
				this.element.val( '' );
			} else {
				var options = {};

				if( this.options.mediaWiki ) {
					options.format = this.options.mediaWiki.user.options.get( 'date' );
				}

				this.element.val( value.text( options ) );
			}

			this._value = value;
			return this._value;
		},

		/**
		 * Disables the widget.
		 */
		disable: function() {
			this.element.prop( 'disabled', true ).addClass( 'ui-state-disabled' );
		},

		/**
		 * Enables the widget.
		 */
		enable: function() {
			this.element.prop( 'disabled', false ).removeClass( 'ui-state-disabled' );
		}

	} );

} )( jQuery, time.Time );
