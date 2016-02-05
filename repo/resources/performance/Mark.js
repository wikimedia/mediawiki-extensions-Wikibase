( function( wb, performance ) {
	"use strict";

	var MODULE = wb.performance;

	/**
	 * Wikibase performance mark
	 *
	 * @class wikibase.performance.Marks
	 * @licence GNU GPL v2+
	 *
	 * @author Jonas Kress
	 * @static
	 */
	var SELF = MODULE.Mark = function( nameString ) {
		if( !performance ){
			return;
		}

		performance.mark( nameString );
	}


}( wikibase, window.performance ) );
