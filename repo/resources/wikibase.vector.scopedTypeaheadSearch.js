( function ( mw ) {
	'use strict';

	/* eslint-disable no-console */

	let searchInputWrapper = document.getElementById( 'simpleSearch' );
	if ( searchInputWrapper ) {
		console.log( ' scopedTypeaheadSearch loaded before full Vector search' );
	} else {
		searchInputWrapper = document.querySelector( '#searchform .cdx-search-input__input-wrapper' );
		if ( searchInputWrapper ) {
			console.log( 'scopedTypeaheadSearch loaded after full Vector search' );
		} else {
			console.error( 'scopedTypeaheadSearch could not find element to replace' );
			return;
		}
	}
	searchInputWrapper.replaceWith( 'ScopedTypeaheadSearch will go here (T338483)' );

	/* eslint-enable */
}( mw ) );
