( function( wb ) {
	'use strict';

	var MODULE = wb.performance;

	/**
	 * Wikibase performance performanceMark represents one marking point
	 *
	 * @class wikibase.performance.PerformanceMark
	 * @licence GNU GPL v2+
	 *
	 * @author Jonas Kress
	 * @constructor
	 * @param {string} name
	 */
	var SELF = MODULE.PerformanceMark = function( name ) {
		this.name = '';
		this.duration = 0;
		this.durations = [];
	};

	/**
	 * @property {string}
	 **/
	SELF.prototype.name = null;

	/**
	 * @property {number}
	 **/
	SELF.prototype.durationTotal = null;

	/**
	 * @property {number[]}
	 **/
	SELF.prototype.durations = null;

}( wikibase ) );
