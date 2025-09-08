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
			</div>
		</div>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxButton, CdxIcon, CdxTextInput } = require( '../../codex.js' );
const { cdxIconCheck, cdxIconClose } = require( './icons.json' );

const WikibaseWbui2025ModalOverlay = require( './wikibase.wbui2025.modalOverlay.vue' );
const WikibaseWbui2025PropertyLookup = require( './wikibase.wbui2025.propertyLookup.vue' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddQualifier',
	components: {
		CdxButton,
		CdxIcon,
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
			snakValue: ''
		};
	},
	computed: {
		addButtonDisabled() {
			return this.snakValue === '';
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
