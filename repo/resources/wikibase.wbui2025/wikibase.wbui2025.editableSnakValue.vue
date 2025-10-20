<template>
	<div class="wikibase-wbui2025-edit-statement-snak-value">
		<div class="wikibase-snaktypeselector ui-state-default">
			<cdx-menu-button
				v-model:selected="snakTypeSelection"
				:menu-items="snakTypeMenuItems"
			>
				<span class="ui-icon ui-icon-snaktypeselector wikibase-snaktypeselector" :title="snakTypeSelectionMessage"></span>
			</cdx-menu-button>
		</div>
		<div
			class="wikibase-wbui2025-snak-value"
			:data-snak-hash="hash"
		>
			<cdx-text-input v-if="!isTabularOrGeoShapeDataType && snakTypeSelection === 'value'" v-model="value"></cdx-text-input>
			<cdx-lookup
				v-else-if="isTabularOrGeoShapeDataType"
				v-model:selected="lookupSelection"
				v-model:input-value="lookupInputValue"
				:menu-items="lookupMenuItems"
				:menu-config="menuConfig"
				@update:input-value="onUpdateInputValue"
				@load-more="onLoadMore"
			>
			</cdx-lookup>
			<div v-else class="wikibase-wbui2025-novalue-somevalue-holder">
				<p>{{ snakTypeSelectionMessage }}</p>
			</div>
			<div v-if="removable" class="wikibase-wbui2025-remove-snak">
				<cdx-button :aria-label="$i18n( 'wikibase-remove' )" @click="$emit( 'remove-snak', snakKey )">
					<cdx-icon :icon="cdxIconTrash"></cdx-icon>
				</cdx-button>
			</div>
		</div>
	</div>
</template>

<script>
const { computed, defineComponent } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const { cdxIconTrash } = require( './icons.json' );
const supportedDatatypes = require( './supportedDatatypes.json' );
const { CdxButton, CdxIcon, CdxLookup, CdxMenuButton, CdxTextInput } = require( '../../codex.js' );
const { searchByDatatype, transformSearchResults } = require( './api/commons.js' );
const { useEditSnakStore } = require( './store/editStatementsStore.js' );
const { useParsedValueStore } = require( './store/parsedValueStore.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableSnakValue',
	components: {
		CdxButton,
		CdxIcon,
		CdxLookup,
		CdxMenuButton,
		CdxTextInput
	},
	props: {
		removable: {
			type: Boolean,
			required: false,
			default: false
		},
		snakKey: {
			type: String,
			required: true
		},
		propertyId: {
			type: String,
			required: true
		}
	},
	emits: [ 'remove-snak' ],
	setup( props ) {
	/*
	 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
	 * store - we pass in the snakHash to make a snak-specific store. This forces us to use
	 * the Composition API to initialise the component.
	 */
		const computedProperties = mapWritableState( useEditSnakStore( props.snakKey ), [
			'value',
			'datatype',
			'snaktype',
			'hash'
		] );
		return {
			value: computed( computedProperties.value ),
			datatype: computed( computedProperties.datatype ),
			snaktype: computed( computedProperties.snaktype ),
			hash: computed( computedProperties.hash )
		};
	},
	data() {
		return {
			cdxIconTrash,
			snakTypeMenuItems: [
				{ label: mw.msg( 'wikibase-snakview-snaktypeselector-value' ), value: 'value' },
				{ label: mw.msg( 'wikibase-snakview-variations-novalue-label' ), value: 'novalue' },
				{ label: mw.msg( 'wikibase-snakview-variations-somevalue-label' ), value: 'somevalue' }
			],
			parseValueTimeout: null,
			previousValue: null,
			lookupMenuItems: [],
			lookupSelection: null,
			lookupInputValue: '',
			menuConfig: {
				visibleItemLimit: 6
			}
		};
	},
	computed: {
		isTabularOrGeoShapeDataType() {
			return supportedDatatypes.includes( this.datatype ) && ( this.datatype === 'tabular-data' || this.datatype === 'geo-shape' );
		},
		snakTypeSelection: {
			get() {
				return this.snaktype;
			},
			set( newSnakTypeSelection ) {
				if ( this.snaktype === 'value' ) {
					this.previousValue = this.value;
				}
				if ( newSnakTypeSelection === 'value' ) {
					this.value = this.previousValue;
				}
				this.snaktype = newSnakTypeSelection;
			}
		},
		snakTypeSelectionMessage() {
			if ( this.snakTypeSelection === 'value' ) {
				return mw.msg( 'wikibase-snakview-snaktypeselector-value' );
			}
			const messageKey = 'wikibase-snakview-variations-' + this.snakTypeSelection + '-label';
			// messages that can appear here:
			// * wikibase-snakview-variations-novalue-label
			// * wikibase-snakview-variations-somevalue-label
			return mw.msg( messageKey );
		}
	},
	methods: {
		fetchLookupResults( searchTerm, offset = 0 ) {
			return searchByDatatype( this.datatype, searchTerm, offset );
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
				.catch( ( error ) => {
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
		value: {
			handler( newValue ) {
				if ( this.isTabularOrGeoShapeDataType && this.lookupSelection !== newValue ) {
					this.lookupInputValue = newValue || '';
					this.lookupSelection = newValue || null;
				}

				if ( this.parseValueTimeout !== null ) {
					clearTimeout( this.parseValueTimeout );
				}
				const parsedValueStore = useParsedValueStore();
				this.parseValueTimeout = setTimeout( () => {
					parsedValueStore.getParsedValue( this.propertyId, this.value );
				}, 300 );
			},
			immediate: true
		},
		lookupSelection( newSelection ) {
			if ( newSelection && this.isTabularOrGeoShapeDataType && this.value !== newSelection ) {
				this.value = newSelection;
			}
		}
	} }
);
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

div.wikibase-wbui2025-edit-statement-snak-value {
	justify-content: center;
	width: 100%;
	display: flex;

	div.wikibase-wbui2025-snak-value {
		width: 100%;

		div.cdx-text-input {
			width: 100%;
		}
	}

	.wikibase-wbui2025-remove-snak {
		button.cdx-button {
			color: @color-base;
			border: 0;
		}
	}

	div.wikibase-wbui2025-novalue-somevalue-holder {
		width: 100%;
		display: flex;
		align-items: center;

		p {
			font-family: 'Inter', sans-serif;
			font-weight: 500;
			font-size: 1.125rem;
			line-height: 1.25;
			color: @color-placeholder;
			padding: 0;
			margin: 0;
			align-items: center;
			gap: @spacing-25;
		}
	}

	.wikibase-snaktypeselector {
		position: relative;
		padding: 0;
		display: inline-block;
	}

	div.cdx-text-input {
		width: 100%;

		input {
			width: 100%;
		}
	}

	div.cdx-lookup {
		width: 100%;
	}
}
</style>
