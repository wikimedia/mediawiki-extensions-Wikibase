( function( wb, $ ) {
	'use strict';

	var MODULE = wb.performance;

	/**
	 * Wikibase performance statistic
	 *
	 * @class wikibase.performance.Statistic
	 * @licence GNU GPL v2+
	 *
	 * @author Jonas Kress
	 * @constructor
	 * @param {PerformanceMarks[]} performanceMarks
	 */
	var SELF = MODULE.Statistic = function( performanceMarks ) {

		this._marks = performanceMarks;
	};

	/**
	 * @property {PerformanceMark[]}
	 * @private
	 **/
	SELF.prototype._marks = null;

	/**
	 * Get HTML
	 * @return {jQuery}
	 **/
	SELF.prototype.getHtml = function() {
		var self = this,
			$div = $( '<div/>' );

		$.each( this._marks, function( key, value ) {

			var durationTotal = self._formatDuration( value.duration ),
				durationsMinMaxAvg = self._getMinAverageMax( value.durations ).map( self._formatDuration ),
				durations = value.durations;

			var text = key + ': ' + durationTotal,
				title = null;

			if ( durations.length > 1 ) {
				text = key + ' (' + durations.length + '): ' + durationTotal;
				title = 'Min: ' + durationsMinMaxAvg[0] + '\n'
					+ 'Avg: ' + durationsMinMaxAvg[1] + '\n'
					+ 'Max: ' + durationsMinMaxAvg[2];
			}

			$div.prepend( $( '<div/>' ).text( text )
										.attr( 'title', title  ) );
		} );

		return $div;
	};

	SELF.prototype._formatDuration = function( duration ) {
		return ( Math.round( duration ) / 1000 ) + 's';
	};

	/**
	 * Get HTML
	 * @private
	 * @return {int[]} [Minimum, Average, Maximum]
	 **/
	SELF.prototype._getMinAverageMax = function( array ) {
		var max = array[0],
			min = array[0],
			sum = 0;

		$.each( array, function() {
			if ( this > max ) {
				max = this;
			}
			if ( this < min ) {
				min = this;
			}
			sum += this;
		} );

		var avg = sum / array.length;
		return [min, avg, max];
	};

}( wikibase, jQuery ) );
