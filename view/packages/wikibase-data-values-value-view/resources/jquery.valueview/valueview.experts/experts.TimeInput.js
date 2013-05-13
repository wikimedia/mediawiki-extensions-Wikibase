/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, vp, $, vv, Time ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * Valueview expert handling input of time values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.TimeInput = vv.expert( 'timeinput', PARENT, {
		/**
		 * The the input element's node.
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Caches a new value (or null for no value) set by _setRawValue() until draw() displaying
		 * the new value has been called. The use of this, basically, is a structural improvement
		 * which allows moving setting the displayed value to the draw() method which is supposed to
		 * handle all visual manners.
		 * @type {time.Time|null|false}
		 */
		_newValue: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			var self = this;

			this.$input = $( '<input/>', {
				type: 'text',
				'class': this.uiBaseClass + '-input valueview-input'
			} )
			.appendTo( this.$viewPort )
			.timeinput()
			.on( 'timeinputchange', function( event, value ) {
				self._viewNotifier.notify( 'change' );
			} );
		},

		/**
		 * @see Query.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.TimeParser();
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 *
		 * @return {time.Time|null}
		 */
		_getRawValue: function() {
			return ( this._newValue !== false )
				? this._newValue
				: this.$input.data( 'timeinput' ).value();
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 *
		 * @param {time.Time|null} time
		 */
		_setRawValue: function( time ) {
			if( !( time instanceof Time ) || !time.isValid() ) {
				time = null;
			}
			this._newValue = time;
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( time1, time2 ) {
			if( time2 === undefined ) {
				time2 = this._getRawValue();
			}

			if( time1 === null && time2 === null ) {
				return true;
			}

			if( !( time1 instanceof Time ) || !( time2 instanceof Time ) ) {
				return false;
			}

			return time1.isValid() && time2.isValid()
				&& time1.precision() === time2.precision()
				&& time1.iso8601() === time2.iso8601();
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			if( this._viewState.isDisabled() ) {
				this.$input.data( 'timeinput' ).disable();
			} else {
				this.$input.data( 'timeinput' ).enable();
			}

			if( this._newValue !== false ) {
				this.$input.data( 'timeinput' ).value( this._newValue );
				this._newValue = false;
			}
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			this.$input.focus();
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}

	} );

}( dataValues, valueParsers, jQuery, jQuery.valueview, time.Time ) );
