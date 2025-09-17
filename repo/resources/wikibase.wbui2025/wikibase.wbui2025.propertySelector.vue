<template>
	<div class="wikibase-wbui2025-property-selector">
		<div class="wikibase-wbui2025-property-selector-heading">
			<h3>{{ headingMessage }}</h3>
			<div class="wikibase-wbui2025-property-selector-heading-buttons">
				<cdx-button
					weight="quiet"
					@click="$emit( 'cancel' )"
				>
					<cdx-icon :icon="cdxIconClose"></cdx-icon>
					{{ $i18n( 'wikibase-cancel' ) }}
				</cdx-button>
				<cdx-button
					action="progressive"
					weight="primary"
					:disabled="addButtonDisabled"
					@click="$emit( 'add', selection )"
				>
					<cdx-icon :icon="cdxIconCheck"></cdx-icon>
					{{ $i18n( 'wikibase-add' ) }}
				</cdx-button>
			</div>
		</div>
		<cdx-lookup
			v-model:selected="selection"
			v-model:input-value="inputValue"
			:menu-items="menuItems"
			:menu-config="menuConfig"
			@update:input-value="onUpdateInputValue"
			@load-more="onLoadMore"
		>
			<template #no-results>
				{{ $i18n( 'wikibase-entityselector-notfound' ) }}
			</template>
		</cdx-lookup>
	</div>
</template>

<script>
const { computed, defineComponent, ref } = require( 'vue' );
const { CdxButton, CdxIcon, CdxLookup } = require( '../../codex.js' );
const { cdxIconCheck, cdxIconClose } = require( './icons.json' );
const { api } = require( './api/api.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025PropertySelector',
	components: {
		CdxButton,
		CdxIcon,
		CdxLookup
	},
	props: {
		headingMessageKey: {
			type: String,
			required: true
		}
	},
	emits: [ 'add', 'cancel' ],
	setup( props ) {
		const selection = ref( null );
		const inputValue = ref( '' );
		const currentSearchTerm = ref( '' );
		const menuItems = ref( [] );
		const menuConfig = {
			visibleItemLimit: 3
		};

		// messages that can be used here:
		// * wikibase-statementgrouplistview-add
		// * anything else the parent component passes in
		const headingMessage = mw.msg( props.headingMessageKey );

		const addButtonDisabled = computed( () => selection.value === null );

		const languageCode = mw.config.get( 'wgUserLanguage' );

		function fetchResults( offset = undefined ) {
			const params = {
				action: 'wbsearchentities',
				language: languageCode,
				uselang: languageCode,
				type: 'property',
				search: currentSearchTerm.value
			};
			if ( offset ) {
				params.continue = offset;
			}

			return api.get( params );
		}

		function adaptApiResponse( results ) {
			return results.map( ( { id, label, url, match, description, display = {} } ) => ( {
				value: id,
				label,
				match: match.type === 'alias' ? `(${ match.text })` : '',
				description,
				// url, TODO: ideally we would include the URL here, but it causes unwanted behavior (T342507)
				language: {
					label: display && display.label && display.label.language,
					match: match.type === 'alias' ? match.language : undefined,
					description: display && display.description && display.description.language
				}
			} ) );
		}

		async function onUpdateInputValue( value ) {
			// internally track the current search term
			currentSearchTerm.value = value;

			// unset search results if there is no search term
			if ( !value ) {
				menuItems.value = [];
				return;
			}

			try {
				const response = await fetchResults();
				// make sure the response is still relevant first
				if ( currentSearchTerm.value !== value ) {
					return;
				}
				if ( response.search && response.search.length > 0 ) {
					// format API response into Codex menu items
					menuItems.value = adaptApiResponse( response.search );
				} else {
					menuItems.value = [];
				}
			} catch ( _ ) {
				// on error, reset search results
				menuItems.value = [];
			}
		}

		async function onLoadMore() {
			const value = currentSearchTerm.value;
			if ( !value ) {
				return;
			}

			try {
				const response = await fetchResults( menuItems.value.length );
				// make sure the response is still relevant first
				if ( currentSearchTerm.value !== value ) {
					return;
				}
				if ( response.search && response.search.length > 0 ) {
					const newItems = adaptApiResponse( response.search );
					// deduplicate search results
					const seenItemValues = new Set( menuItems.value.map( ( item ) => item.value ) );
					for ( const newItem of newItems ) {
						if ( !seenItemValues.has( newItem.value ) ) {
							menuItems.value.push( newItem );
						}
					}
				}
			} catch ( _ ) {
				// on error, do nothing
			}
		}

		return {
			addButtonDisabled,
			cdxIconCheck,
			cdxIconClose,
			inputValue,
			headingMessage,
			menuConfig,
			menuItems,
			onLoadMore,
			onUpdateInputValue,
			selection
		};
	}
} );
</script>

<style lang="less">
.wikibase-wbui2025-property-selector {
	.wikibase-wbui2025-property-selector-heading {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
}
</style>
