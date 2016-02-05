( function( wb, performance ) {
	"use strict";

	var MODULE = wb.performance;

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
	MODULE.Mark = function( name ) {
		if( !performance ){
			return;
		}

		performance.mark( name );
	}

}( wikibase, window.performance ) );
