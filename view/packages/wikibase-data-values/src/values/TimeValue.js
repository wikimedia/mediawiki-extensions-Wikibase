( function( dv, util, $ ) {
	'use strict';

var PARENT = dv.DataValue;

/**
 * `DataValue` for time values.
 * @class dataValues.TimeValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} iso8601
 * @param {Object} [options]
 * @param {string} [options.calendarModel=dataValues.TimeValue.CALENDARS.GREGORIAN.uri]
 *        Wikidata URL of the calendar model.
 * @param {number} [options.precision=dataValues.TimeValue.PRECISIONS.DAY]
 * @param {number} [options.before=0]
 * @param {number} [options.after=0]
 * @param {number} [options.timezone=0]
 *
 * @throws {Error} if `iso8601` is not a valid ISO 8601 string.
 */
var SELF = dv.TimeValue = util.inherit( 'DvTimeValue', PARENT, function( iso8601, options ) {
	this._time = {};

	try {
		var matches = /^([+-]?\d+)-(\d+)-(\d+)T(\d{2}):(\d{2}):(\d{2})Z$/.exec( iso8601 );
		this._time.year = parseInt( matches[1], 10 );
		this._time.month = parseInt( matches[2], 10 );
		this._time.day = parseInt( matches[3], 10 );
		this._time.hour = parseInt( matches[4], 10 );
		this._time.minute = parseInt( matches[5], 10 );
		this._time.second = parseInt( matches[6], 10 );
	} catch( e ) {
		throw new Error( 'Unable to process supposed ISO8601 string' );
	}

	this._options = {
		calendarModel: SELF.CALENDARS.GREGORIAN.uri,
		precision: SELF.getPrecisionById( 'DAY' ),
		before: 0,
		after: 0,
		timezone: 0
	};

	var self = this;

	$.each( options || {}, function( key, value ) {
		self._setOption( key, value );
	} );
}, {
	/**
	 * @property {Object}
	 * @private
	 */
	_time: null,

	/**
	 * @property {Object}
	 * @private
	 */
	_options: null,

	/**
	 * @protected
	 *
	 * @param {string} key
	 * @param {*} value
	 *
	 * @throws {Error} if a value to set is not specified properly.
	 */
	_setOption: function( key, value ) {
		if( key === 'calendarModel' && SELF.getCalendarModelTextByUri( value ) === null ) {
			throw new Error( 'Setting ' + key + ': No valid calendar model URI provided' );
		}
		if( $.inArray( key, ['precision', 'before', 'after', 'timezone'] ) !== -1
			&& typeof value !== 'number'
		) {
			throw new Error( 'Setting ' + key + ': Expected "number" type' );
		}
		if( key === 'precision' && ( value < 0 || value > SELF.PRECISIONS.length ) ) {
			throw new Error( 'Setting ' + key + ': No valid precision provided' );
		}

		this._options[key] = value;
	},

	/**
	 * @since 0.7
	 *
	 * @param {string} key
	 * @return {*}
	 */
	getOption: function( key ) {
		return this._options[key];
	},

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this._getISO8601();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {Object}
	 */
	getValue: function() {
		return this.toJSON();
	},

	/**
	 * @since 0.7
	 *
	 * @return {number}
	 */
	getYear: function() {
		return this._time.year;
	},

	/**
	 * @since 0.7
	 *
	 * @return {number}
	 */
	getMonth: function() {
		return this._time.month;
	},

	/**
	 * @since 0.7
	 *
	 * @return {number}
	 */
	getDay: function() {
		return this._time.day;
	},

	/**
	 * @since 0.7
	 *
	 * @return {number}
	 */
	getHour: function() {
		return this._time.hour;
	},

	/**
	 * @since 0.7
	 *
	 * @return {number}
	 */
	getMinute: function() {
		return this._time.minute;
	},

	/**
	 * @since 0.7
	 *
	 * @return {number}
	 */
	getSecond: function() {
		return this._time.second;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( value ) {
		if( !( value instanceof SELF ) ) {
			return false;
		}

		var valueJSON = value.toJSON(),
			match = true;

		$.each( this.toJSON(), function( key, value ) {
			if( valueJSON[key] !== value ) {
				match = false;
			}
			return match;
		} );

		return match;
	},

	/**
	 * Returns date/time as ISO8601 string.
	 * @private
	 *
	 * @return {string}
	 */
	_getISO8601: function() {
		return ( ( this._time.year < 0 ) ? '-' : '+' )
			+ pad( this._time.year, 11 ) + '-'
			+ pad( this._time.month, 2 ) + '-'
			+ pad( this._time.day, 2 ) + 'T'
			+ pad( this._time.hour, 2 ) + ':'
			+ pad( this._time.minute, 2 ) + ':'
			+ pad( this._time.second, 2 ) + 'Z';
	},

	/**
	 * @inheritdoc
	 *
	 * @return {Object}
	 */
	toJSON: function() {
		return {
			after: this._options.after,
			before: this._options.before,
			calendarmodel: this._options.calendarModel,
			precision: this._options.precision,
			time: this._getISO8601(),
			timezone: this._options.timezone
		};
	}

} );

/**
 * @inheritdoc
 *
 * @return {dataValues.TimeValue}
 */
SELF.newFromJSON = function( json ) {
	return new SELF( json.time, {
		after: json.after,
		before: json.before,
		calendarModel: json.calendarmodel,
		precision: json.precision,
		timezone: json.timezone
	} );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='time']
 * @static
 */
SELF.TYPE = 'time';

// TODO: Inject configurations...
/**
 * Calendar configuration.
 * @property {Object}
 * @static
 * @since 0.7
 */
SELF.CALENDARS = {
	GREGORIAN: {
		text: 'Gregorian',
		uri: 'http://www.wikidata.org/entity/Q1985727'
	},
	JULIAN: {
		text: 'Julian',
		uri: 'http://www.wikidata.org/entity/Q1985786'
	}
};

/**
 * Retrieves a calendar model text by its URI.
 * @static
 * @since 0.7
 *
 * @param {string} uri
 * @return {string|null}
 */
SELF.getCalendarModelTextByUri = function( uri ) {
	var text = null;

	$.each( SELF.CALENDARS, function( id, calendar ) {
		if( calendar.uri === uri ) {
			text = calendar.text;
		}
		return text === null;
	} );

	return text;
};

/**
 * Precision configuration.
 * @property {Object}
 * @static
 * @since 0.7
 */
SELF.PRECISIONS = [
	{ id: 'GY', text: 'billion years' },
	{ id: 'MY100', text: 'hundred million years' },
	{ id: 'MY10', text: 'ten million years' },
	{ id: 'MY', text: 'million years' },
	{ id: 'KY100', text: '100,000 years' },
	{ id: 'KY10', text: '10,000 years' },
	{ id: 'KY', text: 'millenium' },
	{ id: 'YEAR100', text: 'century' },
	{ id: 'YEAR10', text: 'decade' },
	{ id: 'YEAR', text: 'year' },
	{ id: 'MONTH', text: 'month' },
	{ id: 'DAY', text: 'day' },
	{ id: 'HOUR', text: 'hour' },
	{ id: 'MINUTES', text: 'minute' },
	{ id: 'SECOND', text: 'second' }
];

/**
 * Retrieves a precision value by its ID.
 * @static
 * @since 0.7
 *
 * @param {string} id
 * @return {number|null}
 */
SELF.getPrecisionById = function( id ) {
	for( var i = 0; i < SELF.PRECISIONS.length; i++ ) {
		if( SELF.PRECISIONS[i].id === id ) {
			return parseInt( i, 10 );
		}
	}
	return null;
};

/**
 * @ignore
 *
 * @param {number} number
 * @param {number} digits
 * @return {string}
 */
function pad( number, digits ) {
	return ( ( 1e12 + Math.abs( number ) ).toString() ).slice( -digits );
}

dv.registerDataValue( SELF );

}( dataValues, util, jQuery ) );
