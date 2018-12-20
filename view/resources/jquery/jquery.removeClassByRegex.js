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

			var newClasses = subject.attr( 'class' )
				.split( /\s+/ )
				.filter( function ( className ) {
					// Check for each class whether it matches
					return !classNameRegex.test( className );
				} )
				.join( ' ' );

			// override classes:
			subject.attr( 'class', newClasses );
		} );

		return this;
	};

}() );
