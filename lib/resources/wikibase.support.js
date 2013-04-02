/**
 * Browser feature detection routines.
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	// HELPERS for filling fields of wb.support. See related wb.support field for documentations:

	function supportsConstructorDeepExtend() {
		function Test() { this.foo = ''; }
		Test.prototype = { bar: '' };

		var derivate = new Test(),
			extended = $.extend( true, {}, { member: derivate } );

		return extended.member instanceof Test;
	}

	/**
	 * An Object holding information about whether certain features are supported in the user's
	 * browser.
	 *
	 * @since 0.4
	 * @type Object
	 */
	wb.support = {
		/**
		 * Checking instanceof in IE8 will fail after having performed a deep extend on an object
		 * containing a constructor with a custom prototype.
		 *
		 * @since 0.4
		 * @type boolean
		*/
		constructorDeepExtend: supportsConstructorDeepExtend()
	};

}( mediaWiki, wikibase, jQuery ) );
