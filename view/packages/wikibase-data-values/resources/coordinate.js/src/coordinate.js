/**
 * Coordinate detection global routines
 * Original source: http://simia.net/valueparser/coordinate.js
 *
 * VERSION: 0.1
 *
 * @since 0.1
 * @file
 * @ingroup coordinate.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 */
( function( global ) {
	'use strict';

	var coordinate = {},
		_oldCoordinate = global.coordinate;

	global.coordinate = coordinate;

	coordinate.noConflict = function() {
		global.coordinate = _oldCoordinate;
		return coordinate;
	};

	var settings = {};
	settings.north = 'N';
	settings.east = 'E';
	settings.south = 'S';
	settings.west = 'W';
	settings.dot = '.';
	settings.latlongcombinator = ', ';
	settings.degree = '°';
	settings.minute = '\'';
	settings.second = '"';
	settings.precisiontextsdegree = 'to a degree';
	settings.precisiontextsminute = 'to an arcminute';
	settings.precisiontextssecond = 'to an arcsecond';
	settings.precisiontextsdecisecond = 'to a tenth of an arcsecond';
	settings.precisiontextscentisecond = 'to the hundredth of an arcsecond';
	settings.precisiontextsmilisecond = 'to the thousandth of an arcsecond';
	settings.precisiontextsmaximal = 'maximal';

	var isSane = function( text ) {
		return ( text.indexOf( '<' ) === -1 && text.indexOf( '>' ) === -1 );
	};

	var settingText = function( name, text) {
		if( text === undefined ) {
			return settings[name];
		} else if( typeof text !== 'string' ) {
			throw 'Parameter to set should be a string';
		} else if( !isSane( text )) {
			throw 'String ' + text + ' is not considered sane';
		}
		settings[name] = text;
		return settings[name];
	};

	var s = {};
	s.north = function( texts ) { return settingText( 'north', texts ); };
	s.east = function( texts ) { return settingText( 'east', texts ); };
	s.south = function( texts ) { return settingText( 'south', texts ); };
	s.west = function( texts ) { return settingText( 'west', texts ); };
	s.dot = function( texts ) { return settingText( 'dot', texts ); };
	s.latlongcombinator = function( texts ) { return settingText( 'latlongcombinator', texts ); };
	s.degree = function( texts ) { return settingText( 'degree', texts ); };
	s.minute = function( texts ) { return settingText( 'minute', texts ); };
	s.second = function( texts ) { return settingText( 'second', texts ); };
	s.precisiontextsdegree = function( texts ) { return settingText( 'precisiontextsdegree', texts ); };
	s.precisiontextsminute = function( texts ) { return settingText( 'precisiontextsminute', texts ); };
	s.precisiontextssecond = function( texts ) { return settingText( 'precisiontextssecond', texts ); };
	s.precisiontextsdecisecond = function( texts ) { return settingText( 'precisiontextsdecisecond', texts ); };
	s.precisiontextscentisecond = function( texts ) { return settingText( 'precisiontextscentisecond', texts ); };
	s.precisiontextsmilisecond = function( texts ) { return settingText( 'precisiontextsmilisecond', texts ); };
	s.precisiontextsmaximal = function( texts ) { return settingText( 'precisiontextsmaximal', texts ); };

	var precisionText = function( precision ) {
		var text;

		if( Math.abs( precision - 1 ) < 0.0000001 ) {
			text = settings.precisiontextsdegree;
		} else if( Math.abs(precision - 1 / 60 ) < 0.0000001 ) {
			text = settings.precisiontextsminute;
		} else if( Math.abs( precision - 1 / 3600 ) < 0.0000001 ) {
			text = settings.precisiontextssecond;
		} else if( Math.abs( precision - 1 / 36000 ) < 0.0000001 ) {
			text = settings.precisiontextsdecisecond;
		} else if( Math.abs( precision - 1 / 360000 ) < 0.0000001 ) {
			text = settings.precisiontextscentisecond;
		} else if( Math.abs( precision - 1 / 3600000 ) < 0.0000001 ) {
			text = settings.precisiontextsmilisecond;
		} else if( precision === 0 ) {
			text = settings.precisiontextsmaximal;
		} else {
			if( precision < 9e-10 ) {
				precision = 1e-9;
			}
			text = '&plusmn;' + precision + settings.degree;
		}

		return text;
	};

	var precisionTextEarth = function( precision ) {
		var km = 40000 / 360 * precision;

		if( km > 100 ) {
			return Math.round( km / 100 ) * 100 + " km";
		}
		if( km > 10 ) {
			return Math.round( km / 10 ) * 10 + " km";
		}
		if( km > 1 ) {
			return Math.round( km ) + " km";
		}

		var m = km * 1000;

		if( m > 100 ) {
			return Math.round( m / 100 ) * 100 + " m";
		}
		if( m > 10 ) {
			return Math.round( m / 10 ) * 10 + " m";
		}
		if( m > 1 ) {
			return Math.round( m ) + " m";
		}

		var cm = m * 100;

		if( cm > 10 ) {
			return Math.round( cm / 10 ) * 10 + " cm";
		}

		if( cm > 1 ) {
			return Math.round( cm ) + " cm";
		}

		var mm = cm * 10;

		if( mm > 1 ) {
			return Math.round( mm ) + " mm";
		}

		return "1 mm";
	};

	var decimalText = function( latitude, longitude, precision ) {
		return ''
			+ Math.abs( toDecimal( latitude, precision ) )
			+ settings.degree
			+ ' '
			+ ( (latitude < 0) ? settings.south : settings.north )
			+ settings.latlongcombinator
			+ Math.abs( toDecimal( longitude, precision ) )
			+ settings.degree
			+ ' '
			+ ( ( longitude < 0 ) ? settings.west : settings.east );
	};

	var toDecimal = function( value, precision ) {
		var logprecision = Math.max( -9, Math.floor( Math.log( precision ) / Math.LN10 ) );
		return Math.round( value * Math.pow( 10, -1 * logprecision ) ) / Math.pow( 10, -1 * logprecision );
	};

	var degreeText = function( latitude, longitude, precision ) {
		var text = function( number, sign ) {
			if( number === undefined ) {
				return '';
			}
			return number + sign;
		};

		var latdeg = toDegree( latitude, precision ),
			longdeg = toDegree( longitude, precision );

		return ''
			+ text( Math.abs(latdeg.degree ), settings.degree )
			+ text( latdeg.minute, settings.minute )
			+ text( latdeg.second, settings.second )
			+ ( ( latitude < 0 ) ? settings.south : settings.north )
			+ settings.latlongcombinator
			+ text( Math.abs( longdeg.degree ), settings.degree )
			+ text( longdeg.minute, settings.minute )
			+ text( longdeg.second, settings.second )
			+ ( ( longitude < 0 ) ? settings.west : settings.east );
	};

	var toDegree = function( value, precision ) {
		var result = {};
		result.degree = Math.floor( value + 0.00000001 );
		if( precision > 0.9999999999 ) {
			result.minute = undefined;
		} else {
			result.minute = Math.abs( Math.floor( ( value - result.degree + 0.000001 ) * 60 ) );
		}
		if( precision > ( 0.9999999999 / 60 ) ) {
			result.second = undefined;
		} else {
			result.second = ( value - result.degree - result.minute / 60 ) * 3600;
			if( precision > ( 0.9999999999 / 3600 ) ) {
				result.second = Math.abs( Math.round( result.second ) );
			} else if( precision > ( 0.9999999999 / 36000 ) ) {
				result.second = Math.abs( Math.round( result.second * 10 ) / 10 );
			} else if( precision > ( 0.9999999999 / 360000 ) ) {
				result.second = Math.abs( Math.round( result.second * 100 ) / 100 );
			} else {
				result.second = Math.abs( Math.round( result.second * 1000 ) / 1000 );
			}
		}
		return result;
	};

	var precisionlevels = [10, 1, 0.1, 1/60, 0.01, 1/3600, 0.001, 1/36000, 0.0001, 1/360000, 0.00001, 1/3600000, 0.000001];

	var increasePrecision = function( precision ) {
		var index = precisionlevels.indexOf( precision );

		if( ( index === precisionlevels.length - 1 ) || ( index < 0 ) ) {
			var retval = precision / 10;
			return ( retval < 1e-9 ) ? 0 : retval;
		}
		return precisionlevels[index + 1];
	};

	var decreasePrecision = function( precision ) {
		if( precision === 0) {
			return 1e-9;
		}

		var index = precisionlevels.indexOf( precision );

		if( index === 0 ) {
			return 180;
		}
		if( index < 0 ) {
			return Math.min( precision * 10, 180 );
		}

		return precisionlevels[index-1];
	};

	coordinate.decimalText = decimalText;
	coordinate.degreeText = degreeText;
	coordinate.toDecimal = toDecimal;
	coordinate.toDegree = toDegree;
	coordinate.increasePrecision = increasePrecision;
	coordinate.decreasePrecision = decreasePrecision;
	coordinate.precisionText = precisionText;
	coordinate.precisionTextEarth = precisionTextEarth;

	coordinate.settings = {};
	coordinate.settings.north = s.north;
	coordinate.settings.east = s.east;
	coordinate.settings.south = s.south;
	coordinate.settings.west = s.west;
	coordinate.settings.dot = s.dot;
	coordinate.settings.latlongcombinator = s.latlongcombinator;
	coordinate.settings.degree = s.degree;
	coordinate.settings.minute = s.minute;
	coordinate.settings.second = s.second;
	coordinate.settings.precisiontextsdegree = s.precisiontextsdegree;
	coordinate.settings.precisiontextsminute = s.precisiontextsminute;
	coordinate.settings.precisiontextssecond = s.precisiontextssecond;
	coordinate.settings.precisiontextsdecisecond = s.precisiontextsdecisecond;
	coordinate.settings.precisiontextscentisecond = s.precisiontextscentisecond;
	coordinate.settings.precisiontextsmilisecond = s.precisiontextsmilisecond;
	coordinate.settings.precisiontextsmaximal = s.precisiontextsmaximal;

} )( this ); // 'this' is global scope, i.e. 'window' in the browser and 'global' on the server
