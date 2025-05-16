( function ( mw ) {
	let searchInputWrapper = document.getElementById( 'simpleSearch' );
	if ( searchInputWrapper ) {
		mw.log( 'scopedTypeaheadSearch loaded before full Vector search' );
	} else {
		searchInputWrapper = document.querySelector( '#searchform .cdx-search-input__input-wrapper' );
		if ( searchInputWrapper ) {
			mw.log( 'scopedTypeaheadSearch loaded after full Vector search' );
		} else {
			mw.log.error( 'scopedTypeaheadSearch could not find element to replace' );
			return;
		}
	}

	const Vue = require( 'vue' );
	const App = require( './ScopedTypeaheadSearch.vue' );

	/*
	 * We use the SkinPageReadyConfig to replace Vector's search module with our own.
	 * Load and animate in our search module when the user clicks on the `#searchInput`
	 * element. The onFocus behaviour is managed by Vector - this module is dynamically
	 * loaded and executed on focus.
	 */
	mw.log( 'Loading Scoped Typeahead Search...' );
	Vue.createMwApp( App ).mount( searchInputWrapper );
	document.querySelector( '#searchform .cdx-text-input__input' ).focus();
	const animationContainer = document.querySelector(
		'.vector-search-box .vector-typeahead-search-container .vector-typeahead-search-scope-select'
	);
	animationContainer.classList.add( 'active' );
}( mw ) );
