<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<cdx-lookup
			ref="inputElement"
			v-model:selected="lookupSelection"
			v-model:input-value="lookupInputValue"
			autocapitalize="off"
			:class="activeClasses"
			:menu-items="lookupMenuItems"
			:menu-config="menuConfig"
			@update:input-value="onUpdateInputValue"
			@load-more="onLoadMore"
			@blur="onBlur"
		>
		</cdx-lookup>
	</wikibase-wbui2025-editable-no-value-some-value-snak-value>
</template>

<script>
const { computed, defineComponent, ref } = require( 'vue' );
const { mapWritableState, mapState } = require( 'pinia' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const { CdxLookup } = require( '../../../codex.js' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue = require( './editableNoValueSomeValueSnakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableLookupSnakValue',
	components: {
		CdxLookup,
		WikibaseWbui2025EditableNoValueSomeValueSnakValue
	},
	props: {
		removable: {
			type: Boolean,
			required: false,
			default: false
		},
		disabled: {
			type: Boolean,
			required: true
		},
		snakKey: {
			type: String,
			required: true
		},
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		}
	},
	emits: [ 'remove-snak' ],
	setup( props ) {
	/*
	 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
	 * store - we pass in the snakHash to make a snak-specific store. This forces us to use
	 * the Composition API to initialise the component.
	 */
		const editSnakStoreGetter = wbui2025.store.useEditSnakStore( props.snakKey );
		const computedProperties = mapWritableState( editSnakStoreGetter, [
			'textvalue',
			'selectionvalue',
			'datatype'
		] );
		const valueStrategy = editSnakStoreGetter().valueStrategy;
		const computedEditSnakStoreGetters = mapState( editSnakStoreGetter, [
			'isIncomplete'
		] );
		const inputHadFocus = ref( false );
		return {
			textvalue: computed( computedProperties.textvalue ),
			selectionvalue: computed( computedProperties.selectionvalue ),
			datatype: computed( computedProperties.datatype ),
			valueStrategy,
			debouncedTriggerParse: mw.util.debounce( valueStrategy.triggerParse.bind( valueStrategy ), 300 ),
			isIncomplete: computed( computedEditSnakStoreGetters.isIncomplete ),
			inputHadFocus
		};
	},
	data() {
		return {
			lookupMenuItems: [],
			lookupSelection: null,
			lookupInputValue: '',
			menuConfig: {
				visibleItemLimit: 6
			}
		};
	},
	computed: {
		activeClasses() {
			return [ { 'cdx-text-input--status-error': this.inputHadFocus && this.isIncomplete }, this.className ];
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.textInput.focus();
		},

		fetchLookupResults( searchTerm, offset = 0 ) {
			if ( offset > 0 ) {
				return wbui2025.store.snakValueStrategyFactory.searchByDatatype( this.datatype, searchTerm, offset );
			} else {
				return wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced( this.datatype, searchTerm, offset );
			}
		},

		onUpdateInputValue( value ) {
			this.selectionvalue = null;
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
		},
		onBlur() {
			this.inputHadFocus = true;
		}
	},
	watch: {
		textvalue: {
			handler( newValue ) {
				if ( newValue === undefined ) {
					return;
				}
				if ( this.lookupSelection !== newValue ) {
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

				this.debouncedTriggerParse( newValue );
			},
			immediate: true
		},
		lookupSelection( newSelection ) {
			if ( newSelection && this.textvalue !== newSelection ) {
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

	div.cdx-lookup {
		width: 100%;
	}

}
</style>
