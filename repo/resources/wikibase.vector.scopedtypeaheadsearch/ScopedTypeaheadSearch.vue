<template>
	<div class="vector-typeahead-search-scope-select">
		<cdx-select
			v-model:selected="selection"
			:menu-items="menuItems"
		></cdx-select>
	</div>
	<cdx-typeahead-search
		id="typeahead-search-wikidata"
		form-action="https://wikidata.org/w/index.php"
		:search-results="searchResults"
		:search-footer-url="searchFooterUrl"
		:highlight-query="true"
		:visible-item-limit="5"
		placeholder="Search Wikidata"
		@input="onInput"
		@load-more="onLoadMore"
	>
		<template #default>
			<input
				type="hidden"
				name="language"
				:value="languageCode"
			>
			<input
				type="hidden"
				name="title"
				value="Special:Search"
			>
			<input
				type="hidden"
				:name="searchNamespace"
				value="1"
			>
		</template>
		<template #search-footer-text="{ searchQuery }">
			Search Wikidata for pages containing
			<strong class="cdx-typeahead-search__search-footer__query">
				{{ searchQuery }}
			</strong>
		</template>
	</cdx-typeahead-search>
</template>

<script>
const { defineComponent, ref, watch } = require( 'vue' );
const { CdxSelect, CdxTypeaheadSearch } = require( '../../codex.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'ScopedTypeaheadSearch',
	components: { CdxTypeaheadSearch, CdxSelect },
	setup() {
		const prefix = ref( '' );
		const selection = ref( 'item' );
		const searchResults = ref( [] );
		const searchFooterUrl = ref( '' );
		const currentSearchTerm = ref( '' );
		const searchNamespace = ref( 'ns0' );
		const languageCode = ref( 'en' );

		const menuItems = [
			{
				label: 'Search entities',
				description: 'Find different types of Wikidata entries',
				items: [
					{ label: 'Items', value: 'item' },
					{ label: 'Properties', value: 'property' },
					{ label: 'Lexemes', value: 'lexeme' },
					{ label: 'Entity schemas', value: 'entity-schema' }
				]
			}
		];
		const prefixToSelection = {
			'P:': 'property',
			'L:': 'lexeme',
			'E:': 'entity-schema'
		};
		const selectionToNamespace = {
			item: 'ns0',
			property: 'ns120',
			lexeme: 'ns146',
			'entity-schema': 'ns640'
		};

		watch( prefix, ( newPrefix ) => {
			if ( prefixToSelection[ newPrefix ] ) {
				selection.value = prefixToSelection[ newPrefix ];
			}
		} );
		watch( selection, ( newSelection ) => {
			searchNamespace.value = selectionToNamespace[ newSelection ];

			// Clear prefix if it doesn't match
			// This happens if the user changes the selection after typing a prefix.
			// The "prefix" text should now be treated as a normal search term
			if ( prefix.value && prefixToSelection[ prefix.value ] !== newSelection ) {
				prefix.value = '';
			}

			fetchResults().then( ( data ) => {
				searchResults.value = data.search && data.search.length > 0 ?
					adaptApiResponse( data.search ) :
					[];
			} );
		} );
		watch( [ prefix, currentSearchTerm, searchNamespace ], () => {
			const valueToSearch = currentSearchTerm.value.slice( prefix.value.length );

			searchFooterUrl.value = `https://www.wikidata.org/w/index.php?language=${ languageCode.value }&search=${ encodeURIComponent( valueToSearch ) }&title=Special%3ASearch&fulltext=1&${ searchNamespace.value }=1`;
		} );

		function fetchResults( offset ) {
			// If a prefix is active, omit it from what we actually search for (if the length is 0, this has no effect)
			const valueToSearch = currentSearchTerm.value.slice( prefix.value.length );

			const params = new URLSearchParams( {
				origin: '*',
				action: 'wbsearchentities',
				format: 'json',
				limit: '10',
				props: 'url',
				language: languageCode.value,
				uselang: languageCode.value,
				type: selection.value,
				search: valueToSearch
			} );
			if ( offset ) {
				params.set( 'continue', `${ offset }` );
			}

			return fetch( `https://www.wikidata.org/w/api.php?${ params.toString() }` )
          .then( ( response ) => response.json() );
		}

		/**
		 * Format search results for consumption by TypeaheadSearch.
		 */
		function adaptApiResponse( pages ) {
			return pages.map( ( { id, label, url, match, description, display = {} } ) => ( {
				value: id,
				label,
				match: match.type === 'alias' ? `(${ match.text })` : '',
				description,
				url,
				language: {
					label: display && display.label && display.label.language,
					match: match.type === 'alias' ? match.language : undefined,
					description: display && display.description && display.description.language
				}
			} ) );
		}

		function onInput( value ) {
			// Internally track the current search term.
			currentSearchTerm.value = value;

			// Unset search results and the search footer URL if there is no value.
			if ( !value || value === '' ) {
				searchResults.value = [];
				searchFooterUrl.value = '';
				return;
			} else if ( value.length === 2 ) {
				if ( prefixToSelection[ value ] ) {
					prefix.value = value;
					searchResults.value = [];
					searchFooterUrl.value = '';
					return;
				}
			}

			fetchResults().then( ( data ) => {
				// Make sure this data is still relevant first.
				if ( currentSearchTerm.value === value ) {
					// If there are results, format them into an array of
					// SearchResults to be passed into TypeaheadSearch for
					// display as a menu of search results
					searchResults.value = data.search && data.search.length > 0 ?
						adaptApiResponse( data.search ) :
						[];
				}
			} ).catch( () => {
				// On error, reset search results and search footer URL.
				searchResults.value = [];
				searchFooterUrl.value = '';
			} );
		}

		function deduplicateResults( results ) {
			const seen = new Set( searchResults.value.map( ( result ) => result.value ) );
			return results.filter( ( result ) => !seen.has( result.value ) );
		}

		function onLoadMore() {
			if ( !currentSearchTerm.value ) {
				return;
			}

			fetchResults( searchResults.value.length ).then( ( data ) => {
				const results = data.search && data.search.length > 0 ?
					adaptApiResponse( data.search ) :
					[];

				const deduplicatedResults = deduplicateResults( results );
				searchResults.value.push( ...deduplicatedResults );
			} );
		}

		return {
			selection,
			searchResults,
			searchFooterUrl,
			searchNamespace,
			languageCode,
			menuItems,
			onInput,
			onLoadMore
		};

	}
} );
</script>
