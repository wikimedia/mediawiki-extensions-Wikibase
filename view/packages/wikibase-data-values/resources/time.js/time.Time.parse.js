/**
 * time.js's Time parser.
 *
 * @author Denny Vrandečić
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
time.Time.parse = ( function( time ) {
	'use strict';

	var settings = time.settings;

	function parse( text ) {
		var tokens = tokenize( text ),
			retval = {},
			result = matchGrammars( [
				'y', '-y', 'my', 'm-y', 'yb', 'myb', 'mdy', 'md-y', 'dmy', 'dm-y',
				'mdyb', 'dmyb', 'mdyc', ',md-yc', 'dmyc', 'dm-yc',
				'mdybc', 'dmybc', 'ymd', '-ymd', 'ym', '-ym'
			], tokens );

		if( result === null ) {
			return null;
		}

		if( result.minus !== undefined ) {
			result.year = result.year * -1;
		}
		if( result.bce !== undefined ) {
			if( result.year < 1 ) {
				return;
			}
			retval.bce = result.bce;
			if( result.bce ) {
				result.year = -1 * (result.year - 1);
			}
		}
		if( result.year !== undefined ) {
			retval.year = result.year;
			var temp = result.year;
			if( retval.bce ) {
				temp -= 1;
			}
			if( result.year < 1 ) {
				retval.bce = true;
			}
			retval.precision = 9;
			if( (temp < -1500) || (temp > 5000) ) {
				while( temp % 10 === 0 ) {
					temp /= 10;
					retval.precision -= 1;
				}
			}
		}
		if( result.month !== undefined ) {
			retval.month = result.month;
			retval.precision = 10;
		}
		if( result.day !== undefined ) {
			retval.day = result.day;
			retval.precision = 11;
		}

		// No proper precision available:
		if( retval.precision === -1 ) {
			return null;
		}

		if( result.calendar !== undefined ) {
			retval.calendarname = result.calendar;
		} else if( (result.year < 1583) && (retval.precision > 10) ) {
			retval.calendarname = 'Julian';
		} else {
			retval.calendarname = 'Gregorian';
		}

		return retval;
	}

	function matchGrammars( grammars, tokens ) {
		var result = null;
		for( var i = 0; i < grammars.length; i++ ) {
			result = matchGrammar( grammars[i], tokens );
			if( result !== null ) {
				return result;
			}
		}
		return null;
	}

	function matchGrammar( grammar, tokens ) {
		var result = {};
		if( grammar.length !== tokens.length ) {
			return null;
		}

		for( var i = 0; i < grammar.length; i++ ) {
			if( tokens[i] === null ) {
				return null;
			}
			if( grammar[i] === 'y' ) {
				if( (tokens[i].type === 'number') || (tokens[i].type === 'year') ) {
					result.year = tokens[i].val;
					continue;
				} else {
					return null;
				}
			}
			if( grammar[i] === 'm' ) {
				if( ( ( tokens[i].type === 'number' ) || (tokens[i].type === 'month') )
					&& tokens[i].month
					) {
					result.month = tokens[i].val;
					continue;
				} else {
					return null;
				}
			}
			if( grammar[i] === 'd' ) {
				if( ((tokens[i].type === 'number') || (tokens[i].type === 'day')) && tokens[i].day ) {
					result.day = tokens[i].val;
					continue;
				} else {
					return null;
				}
			}
			if( grammar[i] === 'c' ) {
				if( tokens[i].type === 'calendar' ) {
					result.calendar = tokens[i].val;
					continue;
				} else {
					return null;
				}
			}
			if( grammar[i] === 'b' ) {
				if( tokens[i].type === 'bce' ) {
					result.bce = tokens[i].val;
					continue;
				} else {
					return null;
				}
			}
			if( grammar[i] === '-' ) {
				if( tokens[i].type === 'minus' ) {
					if( grammar[ i + 1 ] === 'y' ) {
						result.minus = true;
					}
					continue;
				} else {
					return null;
				}
			}
			return null;
		}
		return result;
	}

	function tokenize( s ) {
		var result = [],
			token = '',
			minus = {
				'type': 'minus',
				'val': '-'
			};

		for( var i = 0; i < s.length; i++ ) {
			if( /[\s,\.\/-]/.test( s[i] ) ) {
				if( token === '' ) {
					if( s[i] === '-' ) {
						result.push( minus );
					}
					continue;
				}
				var analysis = analyze( token );
				if( analysis !== null ) {
					result.push( analysis );
					token = '';
					continue;
				}
				if( s[i] === '-' ) {
					result.push( analysis );
					result.push( minus );
					token = '';
					continue;
				}
				token += s[i];
				continue;
			}
			if( fullMatch( token, /\d+/ ) && !/\d/.test( s[i] ) ) {
				if( token !== '' ) {
					result.push( analyze( token ) );
				}
				token = '';
			}
			token += s[i];
		}
		if( token !== '' ) {
			result.push( analyze( token ) );
		}
		return result;
	}

	function analyze( t ) {
		if( !fullMatch( t, /\d{1,11}/ ) ) {
			return testString( t );
		}
		var v = parseInt( t, 10 ),
			day = (t > 0) && (t < 32),
			month = (t > 0) && (t < 13),
			type = 'number';

		if( !day && !month ) {
			type = 'year';
		}
		return {
			'val': v,
			'type': type,
			'month': month,
			'day': day
		};
	}

	function testString( s ) {
		var v = readAsMonth( s );
		if( v !== null ) {
			return {
				'val': v,
				'type': 'month',
				'month': true
			};
		}
		v = readAsBCE( s );
		if( v !== null ) {
			return {
				'val': v,
				'type': 'bce'
			};
		}
		v = readAsCalendar( s );
		if( v !== null ) {
			return {
				'val': v,
				'type': 'calendar'
			};
		}
		return null;
	}

	function readAsMonth( word ) {
		for( var i = 0; i < settings.monthnames.length; i++ ) {
			for( var j = 0; j < settings.monthnames[i].length; j++ ) {
				if( settings.monthnames[i][j].toLowerCase() === word.toLowerCase() ) {
					return i + 1;
				}
			}
		}
		return null;
	};

	function readAsBCE( word ) {
		for( var i = 0; i < settings.bce.length; i++ ) {
			if( settings.bce[i].toLowerCase() === word.toLowerCase() ) {
				return true;
			}
		}
		for( var i = 0; i < settings.ace.length; i++ ) {
			if( settings.ace[i].toLowerCase() === word.toLowerCase() ) {
				return false;
			}
		}
		return null;
	};

	function readAsCalendar( word ) {
		for( var i = 0; i < settings.calendarnames.length; i++ ) {
			for( var j = 0; j < settings.calendarnames[i].length; j++ ) {
				if( settings.calendarnames[i][j].toLowerCase() === word.toLowerCase() ) {
					return settings.calendarnames[i][0];
				}
			}
		}
		return null;
	};

	function fullMatch( str, reg ) {
		var matches = reg.exec( str );
		if( matches === null ) {
			return false;
		}
		return str === matches[0];
	}

	return parse; // expose time.parse

}( time ) );
