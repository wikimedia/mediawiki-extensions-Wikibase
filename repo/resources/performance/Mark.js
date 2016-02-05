( function( wb, performance, $ ) {
	'use strict';

	var MODULE = wb.performance;

	var MARK_START = '::START';
	var MARK_END = '::END';

	/**
	 * Records a performance mark, which is a time value and a name
	 *
	 * @class wikibase.performance.Mark
	 * @licence GNU GPL v2+
	 *
	 * @author Jonas Kress
	 */
	var SELF = MODULE.Mark = function() {
	};

	/**
	 * @private
	 */
	SELF.prototype._mark = function( name ) {
		if ( !performance ) {
			return;
		}

		performance.mark( name );
	};

	/**
	 * Sets a start mark
	 *
	 * @param {string} name
	 */
	SELF.prototype.addStart = function( name ) {
		this._mark( name + MARK_START );
	};

	/**
	 * Sets an end mark
	 *
	 * @param {string} name
	 */
	SELF.prototype.addEnd = function( name ) {
		this._mark( name + MARK_END );
	};

	/**
	 * Get all entries that have a start and end mark
	 *
	 * @return {Object} List of performance marks with mark name as key
	 */
	SELF.prototype.getAllMarks = function() {
		var marks = {},
			totals = {};

		$.each( performance.getEntriesByType( 'mark' ), function() {
			var markName = this.name.replace( MARK_START, '' ).replace( MARK_END, '' );

			if ( !marks[markName] ) {
				marks[markName] = this;

			} else {
				if ( !totals[markName] ) {
					totals[markName] = { name: markName, durationTotal: 0, durations: [] };
				}
				var duration = this.startTime - marks[markName].startTime;
				totals[markName].durationTotal += duration;
				totals[markName].durations.push( duration );

				delete marks[markName];
			}
		} );

		return totals;
	};

}( wikibase, window.performance, jQuery ) );
