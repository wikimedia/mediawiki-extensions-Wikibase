( function ( mw ) {
	const searchContainer = document.querySelector( '.vector-typeahead-search-container' );
	if ( !searchContainer ) {
		mw.log.error( 'scopedTypeaheadSearch could not find element to replace' );
		return;
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
	Vue.createMwApp( App ).mount( searchContainer );
	document.querySelector( '#searchform .cdx-text-input__input' ).focus();
	const animationContainer = document.querySelector(
		'.vector-search-box .vector-typeahead-search-container .vector-typeahead-search-scope-select'
	);
	animationContainer.classList.add( 'active' );
}( mw ) );
