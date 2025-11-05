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
			<cdx-text-input v-if="!valueStrategy.isLookupDatatype() && snakTypeSelection === 'value'" v-model.trim="textvalue"></cdx-text-input>
			<cdx-lookup
				v-else-if="valueStrategy.isLookupDatatype()"
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
				<cdx-button
					weight="quiet"
					:aria-label="$i18n( 'wikibase-remove' )"
					@click="$emit( 'remove-snak', snakKey )"
				>
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
const { CdxButton, CdxIcon, CdxLookup, CdxMenuButton, CdxTextInput } = require( '../../codex.js' );
const { useEditSnakStore } = require( './store/editStatementsStore.js' );
const { snakValueStrategyFactory } = require( './store/snakValueStrategies.js' );

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
		}
	},
	emits: [ 'remove-snak' ],
	setup( props ) {
	/*
	 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
	 * store - we pass in the snakHash to make a snak-specific store. This forces us to use
	 * the Composition API to initialise the component.
	 */
		const editSnakStoreGetter = useEditSnakStore( props.snakKey );
		const computedProperties = mapWritableState( editSnakStoreGetter, [
			'textvalue',
			'selectionvalue',
			'datatype',
			'snaktype',
			'hash'
		] );
		return {
			textvalue: computed( computedProperties.textvalue ),
			selectionvalue: computed( computedProperties.selectionvalue ),
			datatype: computed( computedProperties.datatype ),
			snaktype: computed( computedProperties.snaktype ),
			hash: computed( computedProperties.hash ),
			valueStrategy: editSnakStoreGetter().getValueStrategy()
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
			lookupInputValue: null,
			menuConfig: {
				visibleItemLimit: 6
			}
		};
	},
	computed: {
		snakTypeSelection: {
			get() {
				return this.snaktype;
			},
			set( newSnakTypeSelection ) {
				if ( this.snaktype === 'value' ) {
					this.previousValue = this.textvalue;
				}
				if ( newSnakTypeSelection === 'value' ) {
					this.textvalue = this.previousValue;
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
			return snakValueStrategyFactory.searchByDatatype( this.datatype, searchTerm, offset );
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

					this.lookupMenuItems = this.valueStrategy.transformSearchResults( data );
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
				.then( ( data ) => this.lookupMenuItems.push( ...this.valueStrategy.transformSearchResults( data ) ) );
		}
	},
	watch: {
		textvalue: {
			handler( newValue ) {
				if ( newValue === undefined ) {
					return;
				}
				if ( this.valueStrategy.isLookupDatatype() && this.lookupSelection !== newValue ) {
					if ( this.valueStrategy.isEntityDatatype() ) {
						if ( this.lookupSelection === null ) {
							this.lookupMenuItems = [
								{
									value: '__init__',
									label: newValue
								}
							];
							this.lookupInputValue = newValue;
							this.lookupSelection = '__init__';
						} else {
							this.lookupInputValue = newValue;
						}
					} else {
						this.lookupSelection = newValue || null;
						this.lookupInputValue = newValue || '';
					}
				}

				if ( this.parseValueTimeout !== null ) {
					clearTimeout( this.parseValueTimeout );
				}
				this.parseValueTimeout = setTimeout( () => {
					if ( this.valueStrategy.isEntityDatatype() ) {
						this.valueStrategy.getParsedValue();
					} else {
						this.valueStrategy.getParsedValue( newValue );
					}
				}, 300 );
			},
			immediate: true
		},
		lookupSelection( newSelection ) {
			if ( newSelection && this.valueStrategy.isLookupDatatype() && this.textvalue !== newSelection ) {
				if ( this.valueStrategy.isEntityDatatype() ) {
					const lookupItem = this.lookupMenuItems.find( ( item ) => item.value === newSelection );
					this.textvalue = lookupItem.label || lookupItem.value;
					this.selectionvalue = newSelection;
					this.lookupInputValue = this.textvalue;
				} else {
					this.textvalue = newSelection;
				}
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
