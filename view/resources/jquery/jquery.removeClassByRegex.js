/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 */
( function () {
	'use strict';

	/**
	 * Removes CSS classes according to a regular expression.
	 *
	 * @param {RegExp} classNameRegex
	 * @return {jQuery}
	 */
	$.fn.removeClassByRegex = function ( classNameRegex ) {
		this.attr( 'class', ( index, classes ) => {
			if ( !classes ) {
				// If nothing is returned the current value is not changed.
				return;
			}
			return classes
				.split( /\s+/ )
				// Check for each class whether it matches the regexp.
				.filter( ( className ) => !classNameRegex.test( className ) )
				.join( ' ' );
		} );

		return this;
	};

}() );
