<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<cdx-text-input
			ref="inputElement"
			v-model.trim="textvalue"
			autocapitalize="off"
			:disabled="disabled"
			:class="activeClasses"
			@blur="onBlur"
		></cdx-text-input>

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
const { CdxTextInput } = require( '../../../codex.js' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue = require( './editableNoValueSomeValueSnakValue.vue' );
const WikibaseWbui2025ApiItemLookup = require( './apiItemLookup.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableMonolingualTextSnakValue',
	components: {
		CdxTextInput,
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
		activeClasses() {
			return [ { 'cdx-text-input--status-error': this.inputHadFocus && this.isIncomplete }, this.className ];
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
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

				this.debouncedTriggerParse( newValue );
			},
			immediate: true
		}
	} }
);
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-edit-statement-snak-value {

	.wikibase-wbui2025-snak-value {

		.cdx-text-input {
			width: 100%;
		}

	}

}
</style>
