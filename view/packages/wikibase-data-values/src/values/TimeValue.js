( function( dv, util, $, Time ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * Constructor for creating a data value representing time.
 * @class dataValues.TimeValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @constructor
 *
 * @param {string} value
 *
 * @throws {Error} if value is not a time.Time object.
 */
var SELF = dv.TimeValue = util.inherit( 'DvTimeValue', PARENT, function( value ) {
	if( !( value instanceof Time ) ) {
		throw new Error( 'The given value has to be a time.Time object' );
	}
	this._value = value;
}, {
	/**
	 * @property {time.Time}
	 * @private
	 */
	_value: null,

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this.getValue().iso8601();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {time.Time}
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( value ) {
		if ( !( value instanceof SELF ) ) {
			return false;
		}
		return this.getValue().equals( value.getValue() );
	},

	/**
	 * @inheritdoc
	 *
	 * @return {Object}
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
 * @inheritdoc
 *
 * @return {dataValues.TimeValue}
 *
 * @throws {Error} if detected calendar model is unknown.
 */
SELF.newFromJSON = function( json ) {
	// TODO: not good to do it this way, there are some lost information, e.g. before/after and UTC
	//  offset!
	//  Could simply fix this by creating a second Time object where we use those info as well as
	//  the first Time object's year(), month(), day() etc. The Time constructor currently only
	//  takes a string for parsing though which is very bad as well.
	var gregorianTime = Time.newFromIso8601( json.time, json.precision ),
		finalTime;

	// TODO: The following steps dependent on the calendar model are ugly but consequent with how
	//  time.Time currently represents all calendar models.

	// NOTE: Having the calendar model as url to Wikidata was a specified data model requirement.

	// Time instance should be defined as Gregorian:
	if( json.calendarmodel === 'http://www.wikidata.org/entity/Q1985727' ) {
		finalTime = gregorianTime;
	}
	// Time instance should be defined as Julian:
	else if( json.calendarmodel === 'http://www.wikidata.org/entity/Q1985786' ) {
		// Since the data value saves the time as Gregorian, we first have to transform that back
		// into Julian.
		// NOTE: As of May 15 2013 we decided that this is nonsense and that we should always store
		//  the time in its native format dependent on the calendar model.
		finalTime = new Time(
			$.extend( gregorianTime.julian(), {
				precision: gregorianTime.precision(),
				calendarname: Time.CALENDAR.JULIAN
			} )
			// todo: consider other fields, e.g. 'before', 'after' and 'utc'
		);
	}
	else {
		throw new Error( 'Unknown calendar model "' + json.calendarmodel + '"' );
	}
	return new SELF( finalTime );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='time']
 * @static
 */
SELF.TYPE = 'time';

dv.registerDataValue( SELF );

}( dataValues, util, jQuery, time.Time ) );
