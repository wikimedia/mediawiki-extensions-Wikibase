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
const { defineComponent } = require( 'vue' );
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
	data() {
		return {
			cdxIconCheck,
			cdxIconClose,
			selection: null,
			inputValue: '',
			currentSearchTerm: '',
			menuItems: [],
			menuConfig: {
				visibleItemLimit: 3
			},
			languageCode: mw.config.get( 'wgUserLanguage' )
		};
	},
	computed: {
		headingMessage() {
			// messages that can be used here:
			// * wikibase-statementgrouplistview-add
			// * anything else the parent component passes in
			return mw.msg( this.headingMessageKey );
		},
		addButtonDisabled() {
			return this.selection === null;
		}
	},
	methods: {
		fetchResults( offset = undefined ) {
			const params = {
				action: 'wbsearchentities',
				language: this.languageCode,
				type: 'property',
				search: this.currentSearchTerm
			};
			if ( offset ) {
				params.continue = offset;
			}

			return api.get( params );
		},

		adaptApiResponse( results ) {
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
		},

		async onUpdateInputValue( value ) {
			// internally track the current search term
			this.currentSearchTerm = value;

			// unset search results if there is no search term
			if ( !value ) {
				this.menuItems = [];
				return;
			}

			try {
				const response = await this.fetchResults();
				// make sure the response is still relevant first
				if ( this.currentSearchTerm !== value ) {
					return;
				}
				if ( response.search && response.search.length > 0 ) {
					// format API response into Codex menu items
					this.menuItems = this.adaptApiResponse( response.search );
				} else {
					this.menuItems = [];
				}
			} catch ( _ ) {
				// on error, reset search results
				this.menuItems = [];
			}
		},

		async onLoadMore() {
			const value = this.currentSearchTerm;
			if ( !value ) {
				return;
			}

			try {
				const response = await this.fetchResults( this.menuItems.length );
				// make sure the response is still relevant first
				if ( this.currentSearchTerm !== value ) {
					return;
				}
				if ( response.search && response.search.length > 0 ) {
					const newItems = this.adaptApiResponse( response.search );
					// deduplicate search results
					const seenItemValues = new Set( this.menuItems.map( ( item ) => item.value ) );
					for ( const newItem of newItems ) {
						if ( !seenItemValues.has( newItem.value ) ) {
							this.menuItems.push( newItem );
						}
					}
				}
			} catch ( _ ) {
				// on error, do nothing
			}
		}
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
