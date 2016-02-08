( function( wb, performance ) {
	'use strict';

	var MODULE = wb.performance;

	var MARK_START = '::START';
	var MARK_END = '::END';


	/**
	 * Wikibase performance mark
	 *
	 * @class wikibase.performance.Mark
	 * @licence GNU GPL v2+
	 *
	 * @author Jonas Kress
	 * @static
 	 * @param {string} name
	 */
	var SELF = MODULE.Mark = function( name ) {
		SELF._mark( name );
	};

	/**
	 * @private
	 * @static
	 **/
	SELF._mark = function( name ) {
		if ( !performance ) {
			return;
		}

		performance.mark( name );
	};

	/**
	 * Sets a start mark
	 *
	 * @static
 	 * @param {string} name
	 **/
	SELF.addStart = function( name ) {
		this._mark( name + MARK_START );
	};

	/**
	 * Sets an end mark
	 *
	 * @static
 	 * @param {string} name
	 **/
	SELF.addEnd = function( name ) {
		this._mark( name + MARK_END );
	};

	/**
	 * Get entries
	 *
	 * @static
	 * @return {PerformanceMark[]}
	 **/
	SELF.getAllMarks = function() {
		var marks = {}, totals = {}, PerformanceMark = wb.performance.PerformanceMark;

		$.each( window.performance.getEntriesByType( 'mark' ), function() {
			var markName = this.name.replace( MARK_START, '' ).replace( MARK_END, '' );

			if( !marks[markName] ){
				marks[markName] = this;

			} else {
				if( !totals[ markName ] ){
					totals[ markName ] = new PerformanceMark();
					totals[ markName ].name = markName;
				}
				var duration = (this.startTime - marks[markName].startTime);
				totals[ markName ].duration += duration
				totals[ markName ].durations.push( duration );

				delete marks[markName];
			}
		} );

		return totals;
	};


}( wikibase, window.performance ) );
