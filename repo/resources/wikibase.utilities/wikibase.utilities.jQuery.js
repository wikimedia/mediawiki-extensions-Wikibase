/**
 * JavasSript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.utilities.jQuery.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Collection of jQuery extensions of the Wikibase extension
 * @var Object
 */
window.wikibase.utilities.jQuery = window.wikibase.utilities.jQuery || {};

( function( $ ) {

	/**
	 * Helper function to remove all css classes matching a regular expression.
	 *
	 * @param subject jQuery
	 * @param RegExp classNameRegex
	 */
	$.fn.removeClassByRegex = function( classNameRegex ) {
		this.each( function() {
			var subject = $( this );
			if( ! subject.attr( 'class' ) ) {
				return
			}
			var newClasses = '';
			$.each( subject.attr( 'class' ).split( ' ' ),function( i, className ) {
				// check for each class whether it matches...
				if( ! className.match( classNameRegex ) ) {
					// ...if not, we re-add it
					newClasses += ' ' + className
				}
			} );

			// override classes:
			subject.attr( 'class', newClasses );
		} );
	}

} )( jQuery );