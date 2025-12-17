<template>
	<cdx-lookup
		ref="cdxLookup"
		v-model:selected="selection"
		v-model:input-value="inputValue"
		class="wikibase-wbui2025-property-lookup"
		:menu-items="menuItems"
		:menu-config="menuConfig"
		:placeholder="$i18n( 'wikibase-snakview-property-input-placeholder' ).text()"
		@update:input-value="onUpdateInputValue"
		@update:selected="$emit( 'update:selected', selection, selectionData )"
		@load-more="onLoadMore"
	>
		<template #no-results>
			{{ $i18n( 'wikibase-entityselector-notfound' ) }}
		</template>
	</cdx-lookup>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxLookup } = require( '../../../codex.js' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const supportedDatatypes = require( '../supportedDatatypes.json' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025PropertyLookup',
	components: {
		CdxLookup
	},
	emits: [ 'update:selected' ],
	data() {
		return {
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
		selectionData() {
			if ( this.selection ) {
				return this.menuItems.find( ( item ) => item.value === this.selection );
			}
			return null;
		}
	},
	methods: {
		fetchResultsImmediate( offset = undefined ) {
			const params = {
				action: 'wbsearchentities',
				language: this.languageCode,
				type: 'property',
				search: this.currentSearchTerm
			};
			if ( offset ) {
				params.continue = offset;
			}

			return wbui2025.api.api.get( params );
		},
		fetchResultsDebounced: mw.util.debounce( function ( resolve, reject, offset = undefined ) {
			this.fetchResultsImmediate( offset ).then( resolve, reject );
		}, 300 ),
		fetchResults( offset = undefined ) {
			return new Promise( ( resolve, reject ) => {
				this.fetchResultsDebounced( resolve, reject, offset );
			} );
		},
		adaptApiResponse( results ) {
			const datatypeSupported = ( datatype ) => supportedDatatypes.includes( datatype );
			const getSupportingText = ( datatype ) => {
				if ( datatypeSupported( datatype ) ) {
					return null;
				}
				return mw.msg( 'wikibase-addstatement-property-not-supported-on-mobile' );
			};
			return results.map( ( { id, label, datatype, url, match, description, display = {} } ) => ( {
				value: id,
				label,
				match: match.type === 'alias' ? `(${ match.text })` : '',
				description,
				// url, TODO: ideally we would include the URL here, but it causes unwanted behavior (T342507)
				language: {
					label: display && display.label && display.label.language,
					match: match.type === 'alias' ? match.language : undefined,
					description: display && display.description && display.description.language
				},
				supportingText: getSupportingText( datatype ),
				disabled: !datatypeSupported( datatype ),
				datatype
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
				const response = await this.fetchResultsImmediate( this.menuItems.length );
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
		},
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.cdxLookup.$refs.textInput.focus();
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-property-lookup {
	min-width: @size-1600;
	width: 100%;
}
</style>
