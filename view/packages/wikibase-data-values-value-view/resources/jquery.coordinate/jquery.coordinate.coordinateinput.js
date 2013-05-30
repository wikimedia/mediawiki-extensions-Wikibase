/**
 * Input element that interprets coordinate values.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @event update: Triggered whenever the widget's value is updated.
 *        (1) {jQuery.Event}
 *        (2) {coordinate.Coordinate|null} New value (null for no or an invalid value) the widget's
 *            value has been changed to.
 *
 * @dependency jQuery.Widget
 * @dependency jQuery.eachchange
 * @dependency coordinate.Coordinate
 */
( function( $, Coordinate ) {
	'use strict';

	$.widget( 'coordinate.coordinateinput', {
		/**
		 * Caches the widget's current value.
		 * @type {coordinate.Coordinate|null}
		 */
		_value: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this;

			this.element.addClass( this.widgetName );

			this.element.eachchange( function( event, oldValue ) {
				var value = self._parse();
				if( value !== self._value ) {
					self._value = value;
					self._trigger( 'update', null, [self._value] );
				}
			} );
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			this.element.removeClass( this.widgetName );
			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * Parses the current input value.
		 *
		 * @return {coordinate.Coordinate|null} Coordinate object when parsing was successful.
		 */
		_parse: function() {
			var coordinateValue;

			try {
				coordinateValue = new Coordinate( this.element.val() );
			} catch( e ) {
				return null;
			}

			return ( coordinateValue.isValid() ) ? coordinateValue : null;
		},

		/**
		 * Sets/Gets the widget's value.
		 *
		 * @param {coordinate.Coordinate} [value]
		 * @return {coordinate.Coordinate|null}
		 */
		value: function( value ) {
			if( value === undefined ) {
				return this._value;
			}

			if( value !== null && ( !( value instanceof Coordinate ) || !value.isValid() ) ) {
				throw new Error( 'Cannot set value: Neither valid Coordinate object nor \'null\' ' +
					'given.' );
			}

			if( value === null ) {
				this.element.val( '' );
			} else {
				this.element.val( value.degreeText() );
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

} )( jQuery, coordinate.Coordinate );
