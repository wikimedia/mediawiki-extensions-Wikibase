<template>
	<div class="vector-typeahead-search-scope-select">
		<cdx-select
			v-model:selected="selection"
			:menu-items="menuItems"
		></cdx-select>
	</div>
	<cdx-typeahead-search
		id="searchform"
		:form-action="baseUrl"
		:search-results="searchResults"
		:search-footer-url="searchFooterUrl"
		:highlight-query="true"
		:visible-item-limit="5"
		:placeholder="$i18n( 'searchsuggest-search' ).text()"
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
			<span v-i18n-html:vector-searchsuggest-containing="[ searchQuery ]"></span>
		</template>
	</cdx-typeahead-search>
</template>

<script>
const { defineComponent, ref, watch, computed } = require( 'vue' );
const { CdxSelect, CdxTypeaheadSearch } = require( '../../codex.js' );
const { entityTypesConfig, namespacesConfig } = require( './scopedTypeaheadSearchConfig.json' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'ScopedTypeaheadSearch',
	components: { CdxTypeaheadSearch, CdxSelect },
	setup() {
		const api = new mw.Api();
		const baseUrl = mw.config.get( 'wgScript' );

		const prefix = ref( '' );
		const selection = ref( 'item' );
		const searchResults = ref( [] );
		const currentSearchTerm = ref( '' );
		// TODO: use the selected language rather than hard-coding english here. T391700
		const languageCode = ref( 'en' );

		// If prefix is set, omit it from what's actually searched for
		const valueToSearch = computed( () => currentSearchTerm.value.slice( prefix.value.length ).trim() );
		const searchNamespace = computed( () => 'ns' + entityTypesConfig[ selection.value ].namespace );
		const searchFooterUrl = computed( () => {
			const params = new URLSearchParams( {
				language: languageCode.value,
				search: valueToSearch.value,
				title: 'Special:Search',
				fulltext: 1
			} );
			params.append( searchNamespace.value, 1 );

			return `${ baseUrl }?${ params.toString() }`;
		} );

		const menuItemData = Object.keys( entityTypesConfig ).map( ( entityType ) => ( {
			// Messages that can be used here:
			// * wikibase-scoped-search-item-scope-name
			// * wikibase-scoped-search-property-scope-name
			// * ... and possibly other messages for additional hook-registered scopes
			value: entityType, label: mw.msg( entityTypesConfig[ entityType ].message )
		} ) );
		const menuItems = [
			{
				label: mw.msg( 'wikibase-scoped-search-search-entities' ),
				description: mw.msg( 'wikibase-scoped-search-search-entities-description' ),
				items: menuItemData
			}
		];

		const prefixToSelection = {};
		for ( const nsAlias of Object.keys( mw.config.get( 'wgNamespaceIds' ) ) ) {
			if ( nsAlias.length === 0 ) {
				continue;
			}
			const nsId = mw.config.get( 'wgNamespaceIds' )[ nsAlias ];
			if ( nsId in namespacesConfig ) {
				prefixToSelection[ nsAlias + ':' ] = namespacesConfig[ nsId ];
			}
		}

		watch( prefix, ( newPrefix ) => {
			if ( newPrefix in prefixToSelection ) {
				selection.value = prefixToSelection[ newPrefix ];
			}
		} );
		watch( selection, ( newSelection ) => {
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

		function fetchResults( offset ) {
			const params = {
				action: 'wbsearchentities',
				limit: '10',
				language: languageCode.value,
				uselang: languageCode.value,
				type: selection.value,
				search: valueToSearch.value
			};
			if ( offset ) {
				params.continue = offset;
			}

			return api.get( params );
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
				prefix.value = '';
				return;
			} else if ( value.endsWith( ':' ) ) {
				if ( value.toLowerCase() in prefixToSelection ) {
					prefix.value = value.toLowerCase();
					searchResults.value = [];
					return;
				}
			} else if ( prefix.value && !( value.toLowerCase().startsWith( prefix.value ) ) ) {
				prefix.value = '';
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
			baseUrl,
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
