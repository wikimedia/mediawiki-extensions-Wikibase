/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
( function( $, wb ) {
	'use strict';

	// make this usable without base utilities
	wb.utilities = wb.utilities || {};

	/**
	 * Collection of jQuery extensions of the Wikibase extension
	 * @var Object
	 */
	wb.utilities.jQuery = wb.utilities.jQuery || {};

	/**
	 * Helper function to remove all css classes matching a regular expression.
	 *
	 * @since 0.1
	 *
	 * @param {RegExp} classNameRegex
	 * @return jQuery
	 */
	$.fn.removeClassByRegex = function( classNameRegex ) {
		this.each( function() {
			var subject = $( this );
			if( ! subject.attr( 'class' ) ) {
				return;
			}

			var newClasses = '';

			$.each( subject.attr( 'class' ).split( /\s+/ ), function( i, className ) {
				// check for each class whether it matches...
				if( ! className.match( classNameRegex ) ) {
					// ...if not, we re-add it
					newClasses += ' ' + className;
				}
			} );

			// override classes:
			subject.attr( 'class', $.trim( newClasses ) );
		} );

		return this;
	};

}( jQuery, wikibase ) );
