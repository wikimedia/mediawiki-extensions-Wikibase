/**
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 *
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, $, Time ) {
	'use strict';

	var PARENT = dv.DataValue,
		constructor = function( value ) {
			if( !( value instanceof Time ) ) {
				throw new Error( 'The given value has to be a time.Time object' );
			}
			if( !value.isValid() ) {
				throw new Error( 'The given time value has to represent a valid time' );
			}

			this._value = value;
		};

	/**
	 * Constructor for creating a data value representing time.
	 *
	 * @constructor
	 * @extends dv.DataValue
	 * @since 0.1
	 *
	 * @param {String} value
	 */
	var SELF = dv.TimeValue = dv.util.inherit( 'DvTimeValue', PARENT, constructor, {
		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @since 0.1
		 *
		 * @return String
		 */
		getSortKey: function() {
			return this.getValue().iso8601();
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @since 0.1
		 *
		 * @return time.Time
		 */
		getValue: function() {
			return this._value;
		},

		/**
		 * @see dv.DataValue.equals
		 *
		 * @since 0.1
		 */
		equals: function( value ) {
			if ( !( value instanceof SELF ) ) {
				return false;
			}

			var ownTime = this.getValue(),
				otherTime = value.getValue();

			// no need to check for isValid() since constructor won't allow invalid Time values

			return ownTime.precision() === otherTime.precision()
				&& ownTime.iso8601() === otherTime.iso8601();
		},

		/**
		 * @see dv.DataValue.toJSON
		 *
		 * @since 0.1
		 */
		toJSON: function() {
			var time = this.getValue();

			return {
				time: time.iso8601(),
				timezone: 0, // TODO timezone (offset in minutes)
				before: 0, // TODO
				after: 0, // TODO
				precision: time.precision(),
				calendarmodel: time.calendarURI()
			};
		}

	} );

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	SELF.newFromJSON = function( json ) {
		// TODO: not good to do it this way, there are some lost information, e.g. the calendar
		//  model as well as before/after and UTC offset!
		//  Could simply fix this by creating a second Time object where we use those infos as well
		//  as the first Time object's year(), month(), day() etc. The Time constructor currently
		//  only takes a string for parsing though, which is very bad as well.
		var time = Time.newFromIso8601( json.time, json.precision );
		return new SELF( time );
	};

	/**
	 * @see dv.DataValue.TYPE
	 */
	SELF.TYPE = 'time';

	dv.registerDataValue( SELF );

}( dataValues, jQuery, time.Time ) );
