/**
 * time.js by Denny Vrandečić
 * Source: http://simia.net/valueparser/time.js
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandečić
 */
(function( window ) {

	var time = {};
	var _oldTime = window.time;
	window.time = time;

	time.noConflict = function() {
		window.time = _oldTime;
		return time;
	};

	var settings = {};

	settings.bce = ['BCE', 'BC', 'B.C.', 'before Common Era', 'before Christ'];
	settings.ace = ['CE', 'AD', 'A.D.', 'Anno Domini', 'Common Era'];
	settings.pasttext = '% ago';
	settings.futuretext = 'in %';
	settings.calendarnames = [];
	settings.calendarnames[0] = ['Gregorian', 'G', 'GD', 'GC', 'NS', 'N.S.', 'New Style', 'Gregorian calendar', 'Gregorian date'];
	settings.calendarnames[1] = ['Julian', 'J', 'JD', 'JC', 'OS', 'O.S.', 'Old Style', 'Julian calendar', 'Julian date'];
	settings.monthnames = [];
	settings.monthnames[0]  = ['January', 'Jan'];
	settings.monthnames[1]  = ['February', 'Feb'];
	settings.monthnames[2]  = ['March', 'Mar'];
	settings.monthnames[3]  = ['April', 'Apr'];
	settings.monthnames[4]  = ['May'];
	settings.monthnames[5]  = ['June', 'Jun'];
	settings.monthnames[6]  = ['July', 'Jul'],
	settings.monthnames[7]  = ['August', 'Aug'];
	settings.monthnames[8]  = ['September', 'Sep'];
	settings.monthnames[9]  = ['October', 'Oct'];
	settings.monthnames[10] = ['November', 'Nov'];
	settings.monthnames[11] = ['December', 'Dec'];
	settings.precisiontexts = [];
	settings.precisiontexts[0]  = 'billion years';
	settings.precisiontexts[1]  = 'hundred million years';
	settings.precisiontexts[2]  = 'ten million years';
	settings.precisiontexts[3]  = 'million years';
	settings.precisiontexts[4]  = '100,000 years';
	settings.precisiontexts[5]  = '10,000 years';
	settings.precisiontexts[6]  = 'millenium';
	settings.precisiontexts[7]  = 'century';
	settings.precisiontexts[8]  = 'decade';
	settings.precisiontexts[9]  = 'year';
	settings.precisiontexts[10] = 'month';
	settings.precisiontexts[11] = 'day';
	settings.precisiontexts[12] = 'hour';
	settings.precisiontexts[13] = 'minute';
	settings.precisiontexts[14] = 'second';
	settings.outputprecision = [];
	settings.outputprecision[0] = '% billion years';
	settings.outputprecision[1] = '%00 million years';
	settings.outputprecision[2] = '%0 million years';
	settings.outputprecision[3] = '% million years';
	settings.outputprecision[4] = '%00,000 years';
	settings.outputprecision[5] = '%0,000 years';
	settings.outputprecision[6] = '%. millenium';
	settings.outputprecision[7] = '%. century';
	settings.outputprecision[8] = '%0s';

	var maxPrecision = function() { return 14; };

	var isSane = function( text ) {
		if (text.indexOf('<') > -1) return false;
		if (text.indexOf('>') > -1) return false;
		return true;
	};

	var settingArray = function( name, texts ) {
		if (texts === undefined) return settings[name].slice(0);
		if (!Array.isArray(texts)) throw 'Parameter to set should be an array of strings';
		if (texts.length === 0) throw 'Parameter to set should be an array of strings';
		for (var i=0; i<texts.length; i++) {
			if (typeof texts[i] !== 'string') throw 'Parameter to set should be an array of strings';
			if (!isSane(texts[i])) throw 'String ' + texts[i] + ' is not considered sane';
		}
		settings[name] = texts.slice(0);
		return settings[name].slice(0);
	};
	var bce = function( texts ) { return settingArray( 'bce', texts ); };
	var ace = function( texts ) { return settingArray( 'ace', texts ); };
	var precisionTexts = function( texts ) {
		if (texts.length !== 1+maxPrecision()) throw 'Incorrect number of strings';
		return settingArray( 'precisiontexts', texts );
	};
	var outputPrecision = function( texts ) {
		if (texts.length !== settins.outputprecision.length) throw 'Incorrect number of strings';
		return settingArray( 'outputprecision', texts );
	};

	var settingText = function( name, text) {
		if (text === undefined) return settings[name];
		if (typeof text !== 'string') throw 'Parameter to set should be a string';
		if (!isSane(text)) throw 'String ' + text + ' is not considered sane';
		settings[name] = text;
		return settings[name];
	};
	var pastText = function( texts ) { return settingText( 'pasttext', texts ); };
	var futureText = function( texts ) { return settingText( 'futuretext', texts ); };

	var settingArrayOfArrays = function( name, index, maxindex, texts ) {
		if (!((index >= 0) && (index <= maxindex))) throw 'Index out of range';
		if (texts === undefined) return settings[name][index].slice(0);
		if (!Array.isArray(texts)) throw 'Parameter to set should be an array of strings';
		if (texts.length === 0) throw 'Parameter to set should be an array of strings';
		for (var i=0; i<texts.length; i++) {
			if (typeof texts[i] !== 'string') throw 'Parameter to set should be an array of strings';
			if (!isSane(texts[i])) throw 'String ' + texts[i] + ' is not considered sane';
		}
		settings[name][index] = texts.slice(0);
		return settings[name][index].slice(0);
	};
	var calendarNames = function( index, texts ) { return settingArrayOfArrays( 'calendarnames', index, 1, texts); };
	var monthNames = function( index, texts ) { return settingArrayOfArrays( 'monthnames', index, 11, texts); };

	var pad = function(number,digits) { return (1e12 + Math.abs(number) + '').slice(-digits); };

	var Time = function( inputtext, precision ) {
		var inputprecision = precision;

		this.getInputtext = function() { return inputtext; };

		var result = parse(inputtext);
		if (result === null) result = {};

		var bce = (result.bce !== undefined) ? result.bce : false;
		var year = (result.year !== undefined) ? result.year : null;
		var month = (result.month !== undefined) ? result.month : 1;
		var day = (result.day !== undefined) ? result.day : 1;
		var hour = (result.hour !== undefined) ? result.hour : 0;
		var minute = (result.minute !== undefined) ? result.minute : 0;
		var second = (result.second !== undefined) ? result.second : 0;
		var utcoffset = '+00:00';
		var calendarname = (result.calendarname !== undefined) ? result.calendarname : 'Gregorian';

		this.year = function() { return year; };
		this.month = function() { return month; };
		this.day = function() { return day; };
		this.utcoffset = function() { return utcoffset; };

		var precision = (inputprecision !== undefined)? inputprecision : result.precision;
		this.precision = function() { return precision; };
		this.precisionText = function() { return precisionText( precision ); };
		var before = 0;
		var after = 0;
		this.before = function() { return before; };
		this.after = function() { return after; };

		this.gregorian = function() {
			if (calendarname === 'Gregorian') {
				return { 'year' : year, 'month' : month, 'day' : day };
			} else if (calendarname === 'Julian') {
				return julianToGregorian(year, month, day);
			}
		};
		this.julian = function() {
			if (calendarname === 'Julian') {
				return { 'year' : year, 'month' : month, 'day' : day };
			} else if (calendarname === 'Gregorian') {
				if (year !== null) {
					return gregorianToJulian(year, month, day);
				}
			}
			return null;
		};
		this.jdn = function() {
			if (year===null) return null;
			if (calendarname === 'Gregorian') {
				return gregorianToJulianDay(year, month, day);
			} else {
				return julianToJulianDay(year, month, day);
			}
		};

		this.calendarText = function() { return calendarname; };
		this.calendarURI = function() {
			if (calendarname === 'Gregorian') {
				return 'http://wikidata.org/id/Q1985727';
			} else if (calendarname === 'Julian') {
				return 'http://wikidata.org/id/Q1985786';
			}
		}

		this.iso8601 = function() {
			var g = this.gregorian();
			return ((g.year<0)?'-':'+') + pad(g.year, 11) + '-' + pad(g.month, 2)
				 + '-' + pad(g.day, 2) + 'T' + pad(hour, 2) + ':' + pad(minute, 2)
				 + ':' + pad(second, 2) + 'Z';
		};

		this.text =  function() { return getTextFromDate(precision, year, month, day); };
		this.gregorianText = function() {
			var result = this.gregorian();
			return getTextFromDate(precision, result.year, result.month, result.day);
		};
		this.julianText = function() {
			var result = this.julian();
			if (result === null) return '';
			return getTextFromDate(precision, result.year, result.month, result.day);
		};
	};

	var julianToJulianDay = function(year, month, day) {
		// based on en.wikipedia.org/wiki/Julian_day_number
		var a = Math.floor((14-month)/12);
		var y = year + 4800 - a;
		var m = month + 12 * a - 3;
		return day + Math.floor((153*m + 2)/5) + 365*y + Math.floor(y/4) - 32083;
	};

	var gregorianToJulianDay = function(year, month, day) {
		// based on en.wikipedia.org/wiki/Julian_day_number
		var a = Math.floor((14-month)/12);
		var y = year + 4800 - a;
		var m = month + 12 * a - 3;
		return day + Math.floor((153*m + 2)/5) + 365*y + Math.floor(y/4) - Math.floor(y/100) + Math.floor(y/400) - 32045;
	};

	var julianDayToJulian = function(jdn) {
		// based on http://www.tondering.dk/claus/cal/julperiod.php
		var result = {};
		var b = 0;
		var c = jdn + 32082;

		var d = Math.floor((4*c + 3) / 1461);
		var e = c - Math.floor((1461*d)/4);
		var m = Math.floor((5*e + 2) / 153);

		result.year = 100*b + d - 4800 + Math.floor(m/10);
		result.month = m + 3 - 12*Math.floor(m/10);
		result.day = e - Math.floor((153*m + 2) / 5) + 1;
		return result;
	};

	var julianDayToGregorian = function(jdn) {
		// based on http://www.tondering.dk/claus/cal/julperiod.php
		var result = {};
		var a = jdn + 32044;
		var b = Math.floor((4*a + 3) / 146097);
		var c = a - Math.floor((146097*b)/4);

		var d = Math.floor((4*c + 3) / 1461);
		var e = c - Math.floor((1461*d)/4);
		var m = Math.floor((5*e + 2) / 153);

		result.year = 100*b + d - 4800 + Math.floor(m/10);
		result.month = m + 3 - 12*Math.floor(m/10);
		result.day = e - Math.floor((153*m + 2) / 5) + 1;
		return result;
	};

	var julianToGregorian = function(year, month, day) {
		var julianday = julianToJulianDay(year, month, day);
		return julianDayToGregorian(julianday);
	};

	var gregorianToJulian = function(year, month, day) {
		var julianday = gregorianToJulianDay(year, month, day);
		return julianDayToJulian(julianday);
	};

	var readAsMonth = function(word) {
		for(var i=0; i<settings.monthnames.length; i++) {
			for(var j=0; j<settings.monthnames[i].length; j++) {
				if (settings.monthnames[i][j].toLowerCase() === word.toLowerCase()) {
					return i+1;
				}
			}
		}
		return null;
	};

	var readAsBCE = function(word) {
		for(var i=0; i<settings.bce.length; i++) {
			if (settings.bce[i].toLowerCase() === word.toLowerCase()) {
				return true;
			}
		}
		for(var i=0; i<settings.ace.length; i++) {
			if (settings.ace[i].toLowerCase() === word.toLowerCase()) {
				return false;
			}
		}
		return null;
	};

	var readAsCalendar = function(word) {
		for (var i=0; i<settings.calendarnames.length; i++) {
			for (var j=0; j<settings.calendarnames[i].length; j++) {
				if (settings.calendarnames[i][j].toLowerCase() === word.toLowerCase()) {
					return settings.calendarnames[i][0];
				}
			}
		}
		return null;
	};

	var testString = function(s) {
		var v = readAsMonth(s);
		if (v !== null) {
			return { 'val' : v, 'type' : 'month', 'month' : true };
		}
		v = readAsBCE(s);
		if (v !== null) {
			return { 'val' : v, 'type' : 'bce' };
		}
		v = readAsCalendar(s);
		if (v !== null) {
			return { 'val' : v, 'type' : 'calendar' };
		}
		return null;
	};

	var fullMatch = function(str, reg) {
		var matches = reg.exec(str);
		if (matches === null) return false;
		return str === matches[0];
	};

	var analyze = function(t) {
		if (fullMatch(t, /\d{1,11}/)) {
			var v = parseInt(t);
			var day = (t > 0) && (t < 32);
			var month = (t > 0) && (t < 13);
			var type = 'number';
			if (!day && !month) type = 'year';
			return { 'val' : v, 'type' : type, 'month' : month, 'day' : day };
		} else {
			return testString(t);
		}
	};

	var tokenize = function(s) {
		var result = [];
		var token = '';
		var minus = { 'type' : 'minus', 'val' : '-' };
		for (var i = 0; i < s.length; i++) {
			if (/[\s,\.\/-]/.test(s[i])) {
				if (token === '') {
					if (s[i] === '-') result.push(minus);
					continue;
				}
				var analysis = analyze(token);
				if (analysis !== null) {
					result.push(analysis);
					token = '';
					continue;
				}
				if (s[i] === '-') {
					result.push(analysis);
					result.push(minus);
					token = '';
					continue;
				}
				token += s[i];
				continue;
			}
			if (fullMatch(token, /\d+/) && !/\d/.test(s[i])) {
				if (token!=='') result.push(analyze(token));
				token = '';
			}
			token += s[i];
		}
		if (token !== '') result.push(analyze(token));
		return result;
	};

	var matchGrammar = function(grammar, tokens) {
		var result = {};
		if (grammar.length !== tokens.length) return null;

		for (var i = 0; i < grammar.length; i++) {
			if (tokens[i] === null) return null;
			if (grammar[i] === 'y') {
				if ((tokens[i].type === 'number') || (tokens[i].type === 'year')) {
					result.year = tokens[i].val;
					continue;
				} else return null;
			}
			if (grammar[i] === 'm') {
				if (((tokens[i].type === 'number') || (tokens[i].type === 'month')) && tokens[i].month) {
					result.month = tokens[i].val;
					continue;
				} else return null;
			}
			if (grammar[i] === 'd') {
				if (((tokens[i].type === 'number') || (tokens[i].type === 'day')) && tokens[i].day) {
					result.day = tokens[i].val;
					continue;
				} else return null;
			}
			if (grammar[i] === 'c') {
				if (tokens[i].type === 'calendar') {
					result.calendar = tokens[i].val;
					continue;
				} else return null;
			}
			if (grammar[i] === 'b') {
				if (tokens[i].type === 'bce') {
					result.bce = tokens[i].val;
					continue;
				} else return null;
			}
			if (grammar[i] === '-') {
				if (tokens[i].type === 'minus') {
					if (grammar[i+1] === 'y') {
						result.minus = true;
					}
					continue;
				} else return null;
			}
			return null;
		}
		return result;
	};

	var matchGrammars = function(grammars, tokens) {
		var result = null;
		for (var i = 0; i < grammars.length; i++) {
			result = matchGrammar(grammars[i], tokens);
			if (result !== null) return result;
		}
		return null;
	};

	var parse = function(text) {
		var tokens = tokenize(text);
		var retval = {};
		var result = matchGrammars([
				'y', '-y', 'my', 'm-y', 'yb', 'myb', 'mdy', 'md-y', 'dmy', 'dm-y',
				'mdyb', 'dmyb', 'mdyc', ',md-yc', 'dmyc', 'dm-yc',
				'mdybc', 'dmybc', 'ymd', '-ymd', 'ym', '-ym'
			], tokens);

		if (result === null) return null;

		if (result.minus !== undefined) {
			result.year = result.year*-1;
		}
		if (result.bce !== undefined) {
			if (result.year < 1) return;
			retval.bce = result.bce;
			if (result.bce) result.year = -1*(result.year - 1);
		}
		if (result.year !== undefined) {
			retval.year = result.year;
			var temp = result.year;
			if (retval.bce) temp -= 1;
			if (result.year < 1) retval.bce = true;
			retval.precision = 9;
			if ((temp < -1500) || (temp > 5000)) {
				while (temp % 10 === 0) {
					temp /= 10;
					retval.precision -= 1;
				}
			}
		}
		if (result.month !== undefined) {
			retval.month = result.month;
			retval.precision = 10;
		}
		if (result.day !== undefined) {
			retval.day = result.day;
			retval.precision = 11;
		}
		if (result.calendar !== undefined) {
			retval.calendarname = result.calendar;
		} else if ((result.year < 1583) && (retval.precision > 10)) {
			retval.calendarname = 'Julian';
		} else {
			retval.calendarname = 'Gregorian';
		}

		return retval;
	};

	var writeApproximateYear = function(year, precision) {
		var significant = Math.floor((Math.abs(year)-1)/Math.pow(10, 9-precision))+1;
		var text = settings.outputprecision[precision].replace('%', significant);
		if (precision < 6) {
			if (year < 0) {
				text = settings.pasttext.replace('%', text);
			} else {
				text = settings.futuretext.replace('%', text);
			}
		} else {
			if (year < 1) {
				text += ' ' + settings.bce[0];
			}
		}
		return text;
	};

	var writeYear = function(year) {
		if (year < 0) {
			return -1*(year-1) + ' ' + settings.bce[0];
		}
		if (year === 0) {
			return '1 ' + settings.bce[0];
		}
		return year;
	};

	var writeMonth = function(year, month) {
		return settings.monthnames[month-1][0] + ' ' + writeYear(year);
	};

	var writeDay = function(year, month, day) {
		return settings.monthnames[month-1][0] + ' ' + day + ', ' + writeYear(year);
	};

	var getTextFromDate = function(precision, year, month, day) {
		var retval = '';
		if (year === null) return '';
		if (precision < 9) return writeApproximateYear(year, precision);
		switch (precision) {
			case  9 : return writeYear(year);
			case 10 : return writeMonth(year, month);
			case 11 : return writeDay(year, month, day);
			default : return writeDay(year, month, day) + '  (time not implemented yet)';
		}
	};

	var precisionText = function( acc ) {
		if ((acc > maxPrecision()) || (acc < 0)) return undefined;
		return settings.precisiontexts[acc];
	};

	time.Time = Time;

	time.julianToGregorian = julianToGregorian;
	time.gregorianToJulian = gregorianToJulian;
	time.julianToJulianDay = julianToJulianDay;
	time.gregorianToJulianDay = gregorianToJulianDay;
	time.julianDayToGregorian = julianDayToGregorian;
	time.julianDayToJulian = julianDayToJulian;

	time.writeApproximateYear = writeApproximateYear;
	time.writeYear = writeYear;
	time.writeMonth = writeMonth;
	time.writeDay = writeDay;
	time.getTextFromDate = getTextFromDate;
	time.precisionText = precisionText;
	time.maxPrecision = maxPrecision;

	time.settings = {};
	time.settings.bce = bce;
	time.settings.ace = ace;
	time.settings.pastText = pastText;
	time.settings.futureText = futureText;
	time.settings.calendarNames = calendarNames;
	time.settings.monthNames = monthNames;
	time.settings.precisionTexts = precisionTexts;
	time.settings.outputPrecision = outputPrecision;

})(window);
