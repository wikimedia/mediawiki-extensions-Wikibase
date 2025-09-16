<template>
	<wikibase-wbui2025-modal-overlay>
		<div class="wikibase-wbui2025-add-qualifier">
			<div class="wikibase-wbui2025-add-qualifier-heading">
				<div class="wikibase-wbui2025-add-qualifier-close">
					<cdx-button
						:aria-label="$i18n( 'wikibase-cancel' )"
						weight="quiet"
						@click="$emit( 'hide' )"
					>
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
					</cdx-button>
				</div>
				<h2>{{ $i18n( 'wikibase-addqualifier' ) }}</h2>
			</div>
			<div class="wikibase-wbui2025-add-qualifier-form">
				<cdx-button
					action="progressive"
					:disabled="addButtonDisabled"
					@click="$emit( 'add-qualifier', selectedPropertyId, snakData )"
				>
					<cdx-icon :icon="cdxIconCheck"></cdx-icon>
					{{ $i18n( 'wikibase-add' ) }}
				</cdx-button>
				<wikibase-wbui2025-property-lookup
					@update:selected="onPropertySelection"
				>
				</wikibase-wbui2025-property-lookup>
				<cdx-text-input
					v-if="selectedPropertyDatatype === 'string'"
					v-model.trim="snakValue"
					class="wikibase-wbui2025-add-qualifier-value"
					:placeholder="$i18n( 'wikibase-addqualifier' ).text()"
				>
				</cdx-text-input>
				<cdx-lookup
					v-else-if="isTabularOrGeoShapeDatatype"
					v-model:selected="lookupSelection"
					v-model:input-value="lookupInputValue"
					:menu-items="lookupMenuItems"
					:menu-config="menuConfig"
					class="wikibase-wbui2025-add-qualifier-value"
					@update:input-value="onUpdateInputValue"
					@load-more="onLoadMore"
				>
				</cdx-lookup>
			</div>
		</div>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxButton, CdxIcon, CdxLookup, CdxTextInput } = require( '../../codex.js' );
const { cdxIconCheck, cdxIconClose } = require( './icons.json' );
const supportedDatatypes = require( './supportedDatatypes.json' );
const { searchByDatatype, transformSearchResults } = require( './api/commons.js' );

const WikibaseWbui2025ModalOverlay = require( './wikibase.wbui2025.modalOverlay.vue' );
const WikibaseWbui2025PropertyLookup = require( './wikibase.wbui2025.propertyLookup.vue' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddQualifier',
	components: {
		CdxButton,
		CdxIcon,
		CdxLookup,
		CdxTextInput,
		WikibaseWbui2025ModalOverlay,
		WikibaseWbui2025PropertyLookup
	},
	emits: [ 'hide', 'add-qualifier' ],
	data() {
		return {
			cdxIconCheck,
			cdxIconClose,
			selectedPropertyId: null,
			selectedPropertyDatatype: null,
			snakValue: '',
			lookupMenuItems: [],
			lookupSelection: null,
			lookupInputValue: '',
			menuConfig: {
				visibleItemLimit: 6
			}
		};
	},
	computed: {
		isTabularOrGeoShapeDatatype() {
			return supportedDatatypes.includes( this.selectedPropertyDatatype ) &&
				( this.selectedPropertyDatatype === 'tabular-data' || this.selectedPropertyDatatype === 'geo-shape' );
		},
		addButtonDisabled() {
			return this.snakValue === '';
		},
		snakData() {
			return {
				snaktype: 'value',
				property: this.selectedPropertyId,
				datavalue: {
					value: this.snakValue,
					type: 'string'
				},
				datatype: this.selectedPropertyDatatype
			};
		}
	},
	methods: {
		onPropertySelection( propertyId, propertyData ) {
			this.selectedPropertyId = propertyId;
			this.selectedPropertyDatatype = propertyData && propertyData.datatype;
			this.lookupMenuItems = [];
			this.lookupSelection = null;
			this.lookupInputValue = '';
		},
		fetchLookupResults( searchTerm, offset = 0 ) {
			return searchByDatatype( this.selectedPropertyDatatype, searchTerm, offset );
		},
		onUpdateInputValue( value ) {
			if ( !value ) {
				this.lookupMenuItems = [];
				return;
			}

			this.fetchLookupResults( value )
				.then( ( data ) => {
					if ( this.lookupInputValue !== value ) {
						return;
					}

					if ( !data.query || !data.query.search || data.query.search.length === 0 ) {
						this.lookupMenuItems = [];
						return;
					}

					const results = transformSearchResults( data.query.search );
					this.lookupMenuItems = results;
				} )
				.catch( () => {
					this.lookupMenuItems = [];
				} );
		},
		onLoadMore() {
			if ( !this.lookupInputValue ) {
				return;
			}

			this.fetchLookupResults( this.lookupInputValue, this.lookupMenuItems.length )
				.then( ( data ) => {
					if ( !data.query || !data.query.search || data.query.search.length === 0 ) {
						return;
					}

					const newResults = transformSearchResults( data.query.search );
					this.lookupMenuItems.push( ...newResults );
				} );
		}
	},
	watch: {
		lookupSelection( newSelection ) {
			if ( newSelection && this.isTabularOrGeoShapeDatatype ) {
				this.snakValue = newSelection;
			}
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-add-qualifier {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: @spacing-65;
	width: 100%;
	height: 100%;
}

.wikibase-wbui2025-add-qualifier-heading {
	align-self: stretch;
	padding: @spacing-100 @spacing-100 @spacing-200;
	border-bottom: @border-width-base @border-style-base @border-color-subtle;

	h2 {
		padding: @spacing-0;
		font-family: @font-family-base;
		text-align: center;
	}
}

.wikibase-wbui2025-add-qualifier-close {
	text-align: right;
}

.wikibase-wbui2025-add-qualifier-form {
	padding: @spacing-200 @spacing-100;
	align-self: stretch;
	display: flex;
	flex-direction: column;
	gap: @spacing-150;
	align-items: flex-end;
}

.wikibase-wbui2025-add-qualifier-value {
	align-self: stretch;
}
</style>
