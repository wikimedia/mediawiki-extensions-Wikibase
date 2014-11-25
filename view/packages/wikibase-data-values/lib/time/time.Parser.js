( function( time ) {
	'use strict';

	// TODO: this should probably already return a time.Time instance and time.Time constructor
	//  should not take a string (perhaps just for convenience?).
	// TODO: have a parser per calendar model and one for time.Time itself which is delegating the
	//  parsing to the different calendar model parsers. This will allow to add new calendar models
	//  without touching existing code. Parser code for similar models can still be shared.

	/**
	 * time.js's Time parser.
	 * @class time.Parser
	 * @licence GNU GPL v2+
	 * @author Denny Vrandečić
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @constructor
	 *
	 * @param {Object} [settings]
	 */
	var SELF = time.Parser = function Parser( settings ) {
		this._settings = time.settings;

		for( var key in settings ) {
			if( settings.hasOwnProperty( key ) ) {
				this._settings[key] = settings[key];
			}
		}
	};

	/**
	 * Parser settings.
	 * @property {Object}
	 * @private
	 */
	SELF.prototype._settings = null;

	/**
	 * Parses a string.
	 *
	 * @param {string} text
	 * @return {Object}
	 */
	SELF.prototype.parse = function( text ) {
		// TODO: instead of injecting settings, the parser should properly set up its own tokenizer
		//  instance once such an object is available.

		// Checking if the input text matches an output precision before analyzing the input text
		// character by character:
		var reconverted = this._reconvertOutputString( text );
		if( reconverted !== false ) {
			return reconverted;
		}

		var tokens = tokenize( text, this._settings ),
			retval = {},
			grammars = getGrammars( this._settings.daybeforemonth ),
			result = matchGrammars( grammars, tokens );

		if( result === null ) {
			return null;
		}

		if( result.minus !== undefined ) {
			result.year = result.year * -1;
		}
		if( result.bce !== undefined ) {
			if( result.year < 1 ) {
				return null;
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
		} else if( ( result.year < 1583 ) && ( retval.precision > 10 ) ) {
			retval.calendarname = time.Time.CALENDAR.JULIAN;
		} else {
			retval.calendarname = time.Time.CALENDAR.GREGORIAN;
		}

		delete( retval.bce ); // nothing we want to expose since this is redundant with "year"
		delete( retval.minus );
		return retval;
	};

	/**
	 * Analyzes a string if it is a time value that has been specified in one of the output
	 * precision formats specified in the settings. If so, this method re-converts such an output
	 * string to an object that can be used to instantiate a time.Time object.
	 * @private
	 *
	 * @param {string} string
	 * @return {Object|boolean}
	 */
	SELF.prototype._reconvertOutputString = function( string ) {
		for( var precisionIndex in this._settings.outputprecision ) {
			var regExp = new RegExp(
				'^\\s*([^\\d\\s]*|)\\s*'
				+ this._settings.outputprecision[precisionIndex].replace( /%/, '(\\d+)' )
				+ '\\s*([^\\d\\s]*|)\\s*$'
			);

			if( regExp.test( string ) ) {
				var matches = string.match( regExp ),
					significant = matches[2],
					year = significant * this._settings.outputprecisionFactors[precisionIndex],
					bceIndicators = [
						this._settings.bce[0],
						this._settings.pasttext.replace( /( |%)/g, '' )
					];

				for( var i in bceIndicators ) {
					if( matches[1] === bceIndicators[i] || matches[3] === bceIndicators[i] ) {
						year *= -1;
					}
				}

				return {
					year: year,
					precision: parseInt( precisionIndex, 10 ),
					calendarname: time.Time.CALENDAR.GREGORIAN
				};
			}
		}

		return false;
	};

	/**
	 * Returns an array of grammars which should be used.
	 * @ignore
	 *
	 * @param {boolean} daybeforemonth Whether the day is usually written before the month.
	 * @return {string[]}
	 */
	function getGrammars( daybeforemonth ) {
		var grammars = [
			'y', '-y', 'my', 'm-y', 'yb', 'myb', 'mdy', 'md-y', 'dmy', 'dm-y',
			'mdyb', 'dmyb', 'mdyc', ',md-yc', 'dmyc', 'dm-yc',
			'mdybc', 'dmybc'
		];

		// If the language prefers the day before the month, we have to switch the above grammar
		// priorities (switch the "md" with the "dm" versions of the equivalent grammar).
		if( daybeforemonth ) {
			for( var i in grammars ) {
				grammars[i] = grammars[i]
					.replace( 'md', '@@' )
					.replace( 'dm', 'md' )
					.replace( '@@', 'dm' );
			}
		}
		return grammars.concat( [ 'ymd', '-ymd', 'ym', '-ym' ] );
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

	// TODO: Create a generic tokenizer prototype out of the following helpers which can be used by
	//  different (time) parsers.

	function tokenize( s, settings ) {
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
				var analysis = analyze( token, settings );
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
					result.push( analyze( token, settings ) );
				}
				token = '';
			}
			token += s[i];
		}
		if( token !== '' ) {
			result.push( analyze( token, settings ) );
		}
		return result;
	}

	function analyze( t, settings ) {
		if( !fullMatch( t, /\d{1,11}/ ) ) {
			return testString( t, settings );
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

	function testString( s, settings ) {
		var v = readAsMonth( s, settings );
		if( v !== null ) {
			return {
				'val': v,
				'type': 'month',
				'month': true
			};
		}
		v = readAsBCE( s, settings );
		if( v !== null ) {
			return {
				'val': v,
				'type': 'bce'
			};
		}
		v = readAsCalendar( s, settings );
		if( v !== null ) {
			return {
				'val': v,
				'type': 'calendar'
			};
		}
		return null;
	}

	function readAsMonth( word, settings ) {
		for( var i = 0; i < settings.monthnames.length; i++ ) {
			for( var j = 0; j < settings.monthnames[i].length; j++ ) {
				if( settings.monthnames[i][j].toLowerCase() === word.toLowerCase() ) {
					return i + 1;
				}
			}
		}
		return null;
	}

	function readAsBCE( word, settings ) {
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
	}

	function readAsCalendar( word, settings ) {
		for( var calendarKey in settings.calendarnames ) {
			for( var i = 0; i < settings.calendarnames[calendarKey].length; i++ ) {
				if( settings.calendarnames[calendarKey][i].toLowerCase() === word.toLowerCase() ) {
					return calendarKey;
				}
			}
		}
		return null;
	}

	function fullMatch( str, reg ) {
		var matches = reg.exec( str );
		if( matches === null ) {
			return false;
		}
		return str === matches[0];
	}

}( time ) );
