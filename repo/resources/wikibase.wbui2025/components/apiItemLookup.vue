<template>
	<!-- Use @input here instead of v-model:input because v-model breaks when switching
			value types. see T416977 -->
	<cdx-lookup
		ref="inputElement"
		v-model:selected="lookupSelection"
		:input-value="lookupInputValue"
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
