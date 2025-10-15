<template>
	<wikibase-wbui2025-modal-overlay>
		<div class="wikibase-wbui2025-add-reference">
			<div class="wikibase-wbui2025-add-reference-heading">
				<div class="wikibase-wbui2025-add-reference-close">
					<cdx-button
						:aria-label="$i18n( 'wikibase-cancel' )"
						weight="quiet"
						@click="$emit( 'hide' )"
					>
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
					</cdx-button>
				</div>
				<h2>{{ $i18n( 'wikibase-addreference' ) }}</h2>
			</div>
			<div class="wikibase-wbui2025-add-reference-form">
				<cdx-button
					action="progressive"
					:disabled="addButtonDisabled"
					@click="$emit( 'add-reference', selectedPropertyId, snakData )"
				>
					<cdx-icon :icon="cdxIconCheck"></cdx-icon>
					{{ $i18n( 'wikibase-add' ) }}
				</cdx-button>
				<wikibase-wbui2025-property-lookup
					ref="propertyLookup"
					@update:selected="onPropertySelection"
				>
				</wikibase-wbui2025-property-lookup>
				<cdx-text-input
					v-if="selectedPropertyDatatype === 'string'"
					ref="textInput"
					v-model.trim="snakValue"
					class="wikibase-wbui2025-add-reference-value"
					:placeholder="$i18n( 'wikibase-addreference' ).text()"
				>
				</cdx-text-input>
			</div>
		</div>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
const { defineComponent, nextTick } = require( 'vue' );
const { CdxButton, CdxIcon, CdxTextInput } = require( '../../codex.js' );
const { cdxIconClose, cdxIconCheck } = require( './icons.json' );

const WikibaseWbui2025ModalOverlay = require( './wikibase.wbui2025.modalOverlay.vue' );
const WikibaseWbui2025PropertyLookup = require( './wikibase.wbui2025.propertyLookup.vue' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddReference',
	components: {
		CdxButton,
		CdxIcon,
		CdxTextInput,
		WikibaseWbui2025ModalOverlay,
		WikibaseWbui2025PropertyLookup
	},
	emits: [ 'hide', 'add-reference' ],
	data() {
		return {
			cdxIconClose,
			cdxIconCheck,
			selectedPropertyId: null,
			selectedPropertyDatatype: null,
			snakValue: ''
		};
	},
	computed: {
		addButtonDisabled() {
			return !( this.selectedPropertyId && this.snakValue );
		},
		snakData() {
			return {
				snaktype: 'value',
				property: this.selectedPropertyId,
				datavalue: {
					value: this.snakValue,
					type: this.selectedPropertyDatatype
				},
				datatype: this.selectedPropertyDatatype
			};
		}
	},
	methods: {
		onPropertySelection( propertyId, propertyData ) {
			this.selectedPropertyId = propertyId;
			this.selectedPropertyDatatype = propertyData.datatype;
			nextTick( () => {
				this.$refs.textInput.focus();
			} );
		}
	},
	mounted() {
		nextTick( () => {
			this.$refs.propertyLookup.focus();
		} );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-add-reference {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: @spacing-65;
	width: 100%;
	height: 100%;
}

.wikibase-wbui2025-add-reference-heading {
	align-self: stretch;
	padding: @spacing-100 @spacing-100 @spacing-200;
	border-bottom: @border-width-base @border-style-base @border-color-subtle;

	h2 {
		padding: @spacing-0;
		font-family: @font-family-base;
		text-align: center;
		color: @color-emphasized;
	}
}

.wikibase-wbui2025-add-reference-close {
	text-align: right;
}

.wikibase-wbui2025-add-reference-form {
	padding: @spacing-200 @spacing-100;
	align-self: stretch;
	display: flex;
	flex-direction: column;
	gap: @spacing-150;
	align-items: flex-end;
}

.wikibase-wbui2025-add-reference-value {
	align-self: stretch;
}
</style>
