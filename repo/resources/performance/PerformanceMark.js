( function( wb ) {
	'use strict';

	var MODULE = wb.performance;

	/**
	 * Wikibase performance performanceMark
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
	 * @property {int}
	 **/
	SELF.prototype.duration = null;

	/**
	 * @property {int[]}
	 **/
	SELF.prototype.durations = null;

}( wikibase ) );
