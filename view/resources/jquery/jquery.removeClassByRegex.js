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

			var newClasses = '';

			subject.attr( 'class' ).split( /\s+/ ).forEach( function ( i, className ) {
				// check for each class whether it matches...
				if ( !className.match( classNameRegex ) ) {
					// ...if not, we re-add it
					newClasses += ' ' + className;
				}
			} );

			// override classes:
			subject.attr( 'class', newClasses.trim() );
		} );

		return this;
	};

}() );
