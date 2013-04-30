/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, vp, $, vv, Time ) {
	'use strict';

	// TODO: for now, we only serve a plain input. Later this expert should use a widget dedicated
	//  to time input and therefore not require to inherit from the StringValue expert anymore.
	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert handling input of time values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.StringValue
	 */
	vv.experts.TimeInput = vv.expert( 'timeinput', PARENT, {
		/**
		 * @see Query.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.TimeParser();
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 */
		_getRawValue: function() {
			if( this._newValue !== false ) {
				return this._newValue
			}
			var time = new Time( $.trim( this.$input.val() ) );

			return time.isValid() ? time : null;
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
		 * @see jQuery.valueview.experts.StringValue.draw
		 */
		draw: function() {
			// Little hack for abusing inheritance. Should go away since the whole expert is just
			// temporary for now.
			if( this._newValue !== false ) {
				this._newValue = this._newValue === null ? '' : this._newValue.text();
			}

			PARENT.prototype.draw.call( this );
		}
	} );

}( dataValues, valueParsers, jQuery, jQuery.valueview, time.Time ) );
