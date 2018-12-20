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
		this.each( function () {
			var subject = $( this );
			if ( !subject.attr( 'class' ) ) {
				return;
			}

			// Replace classes
			subject.attr( 'class', function ( index, classes ) {
				return classes
					.split( /\s+/ )
					.filter( function ( className ) {
						// Check for each class whether it matches the regexp
						return !classNameRegex.test( className );
					} )
					.join( ' ' );
			} );
		} );

		return this;
	};

}() );
