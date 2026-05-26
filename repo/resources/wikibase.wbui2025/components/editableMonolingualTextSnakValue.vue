<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<cdx-text-area
			ref="inputElement"
			v-model="textvalue"
			autocapitalize="off"
			autosize
			rows="1"
			:disabled="disabled"
			:class="className"
			:status="status"
			@blur="onBlur"
			@input="removeNewLines"
			@keydown.enter.prevent
		></cdx-text-area>

		<template #secondary-input>
			<div>
				<p>{{ $i18n( 'wikibase-monolingualtextcode-mandatory' ) }}:</p>
			</div>
			<wikibase-wbui2025-api-item-lookup
				:lookup-source="lookupSource"
				:class-name="className"
			></wikibase-wbui2025-api-item-lookup>
		</template>
	</wikibase-wbui2025-editable-no-value-some-value-snak-value>
</template>

<script>
const { computed, defineComponent, ref, watch } = require( 'vue' );
const { mapState, mapWritableState, storeToRefs } = require( 'pinia' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const { CdxTextArea } = require( '../../../codex.js' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue = require( './editableNoValueSomeValueSnakValue.vue' );
const WikibaseWbui2025ApiItemLookup = require( './apiItemLookup.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableMonolingualTextSnakValue',
	components: {
		CdxTextArea,
		WikibaseWbui2025ApiItemLookup,
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
			'textvalue'
		] );
		const computedEditSnakStoreGetters = mapState( editSnakStoreGetter, [
			'isIncomplete'
		] );
		const valueStrategy = editSnakStoreGetter().valueStrategy;
		const inputHadFocus = ref( false );
		const { monolingualtextlanguagecode, monolingualtextlanguagecodeText } = storeToRefs( editSnakStoreGetter() );
		const monolingualtextlanguageText = ref( monolingualtextlanguagecodeText.value );

		const lookupSource = new wbui2025.api.ApiLookupSource(
			monolingualtextlanguageText.value,
			monolingualtextlanguagecode.value,
			'monolingualtext',
			wbui2025.api.transformLanguageSearchResults
		);

		lookupSource.lookupSelection = monolingualtextlanguagecode;
		lookupSource.lookupInputValue = monolingualtextlanguageText;
		lookupSource.setupWatches();
		watch( lookupSource.lookupMenuItems,
			( newVal ) => {
				for ( const menuItem of newVal ) {
					if ( menuItem.value === monolingualtextlanguagecode.value ) {
						lookupSource.lookupInputValue.value = newVal[ 0 ].label;
						return;
					}
				}
			},
			{
				once: true
			}
		);
		watch(
			lookupSource.lookupSelection,
			( newVal ) => {
				editSnakStoreGetter().updateMonolingualTextLanguageCode( newVal );
				editSnakStoreGetter().valueStrategy.triggerParse();
			},
			{ deep: true }
		);

		return {
			textvalue: computed( computedProperties.textvalue ),
			debouncedTriggerParse: mw.util.debounce( valueStrategy.triggerParse.bind( valueStrategy ), 300 ),
			isIncomplete: computed( computedEditSnakStoreGetters.isIncomplete ),
			lookupSource,
			inputHadFocus
		};
	},
	computed: {
		status() {
			if ( this.inputHadFocus && this.isIncomplete ) {
				/* We don't want to show the text input as error if it has a value and no language
				 * is currently set. Because no language is set, the parse of any string will fail,
				 * and `isIncomplete` will be set, but we will ignore this until a language has
				 * been selected.
				 */
				if ( this.textvalue !== '' && this.lookupSource.lookupSelection.value === null ) {
					return 'default';
				}
				// If the language has been set, we treat parse errors as issue with this text field.
				return 'error';
			}
			return 'default';
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		},

		onBlur() {
			this.inputHadFocus = true;
		},

		removeNewLines( inputEvent ) {
			this.textvalue = inputEvent.target.value.replace( /\r?\n/g, '' );
		}
	},
	watch: {
		textvalue: {
			handler( newValue ) {
				if ( newValue === undefined ) {
					return;
				}

				this.debouncedTriggerParse( newValue );
			},
			immediate: true
		}
	},
	mounted() {
		if ( this.textvalue ) {
			// when editing an existing value, fix the height to show the full text
			const textarea = this.$refs.inputElement.$refs.textarea;
			textarea.style.height = `${ textarea.scrollHeight }px`;
		}
	} }
);
</script>
