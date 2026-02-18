<template>
	<cdx-lookup
		ref="inputElement"
		v-model:selected="lookupSelection"
		v-model:input-value="lookupInputValue"
		autocapitalize="off"
		:class="activeClasses"
		:menu-items="lookupMenuItems"
		:menu-config="menuConfig"
		@input="onInput"
		@load-more="onLoadMore"
		@blur="onBlur"
	>
	</cdx-lookup>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxLookup } = require( '../../../codex.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025ApiItemLookup',
	components: {
		CdxLookup
	},
	props: {
		lookupSource: {
			type: Object,
			required: true
		},
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		}
	},
	emits: [ 'update:inputValue' ],
	setup( props ) {
		return {
			lookupMenuItems: props.lookupSource.lookupMenuItems,
			lookupSelection: props.lookupSource.lookupSelection,
			lookupInputValue: props.lookupSource.lookupInputValue
		};
	},
	data() {
		return {
			inputHadFocus: false,
			menuConfig: {
				visibleItemLimit: 6
			}
		};
	},
	computed: {
		isIncomplete() {
			return this.lookupSource.isIncomplete();
		},
		activeClasses() {
			return [ { 'cdx-text-input--status-error': this.inputHadFocus && this.isIncomplete }, this.className ];
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.textInput.focus();
		},
		onInput( value ) {
			// TODO: T416977 The connection between input changes on CdxLookup's CdxTextInput and
			// changes to the lookupInputValue should be established by the v-model:inputValue
			// property on CdxLookup. This works if the component is loaded when the form opens,
			// but seems to break if we open the editForm with the snak having 'novalue' and switch
			// to 'somevalue'.
			// eslint-disable-next-line vue/no-mutating-props
			this.lookupSource.lookupInputValue.value = value;
		},
		onLoadMore() {
			this.lookupSource.onLoadMore();
		},
		onBlur() {
			this.inputHadFocus = true;
		}
	} }
);
</script>
