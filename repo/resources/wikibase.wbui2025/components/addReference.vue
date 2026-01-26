<template>
	<wikibase-wbui2025-modal-overlay
		:header="$i18n( 'wikibase-addreference' )"
		minimal-style
		hide-footer
		@hide="$emit( 'hide' )">
		<template #content>
			<div class="wikibase-wbui2025-add-reference-form">
				<cdx-button
					action="progressive"
					:disabled="addButtonDisabled"
					@click="submitSnakData"
				>
					<cdx-icon :icon="cdxIconCheck"></cdx-icon>
					{{ $i18n( 'wikibase-add' ) }}
				</cdx-button>
				<wikibase-wbui2025-property-lookup
					ref="propertyLookup"
					@update:selected="onPropertySelection"
				>
				</wikibase-wbui2025-property-lookup>
				<wikibase-wbui2025-editable-any-datatype-snak-value
					v-if="snakKey"
					ref="snakInput"
					:snak-key="snakKey"
					:removable="false"
					class-name="wikibase-wbui2025-add-reference-value"
				></wikibase-wbui2025-editable-any-datatype-snak-value>
			</div>
		</template>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
const { defineComponent, nextTick } = require( 'vue' );
const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconCheck } = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

const WikibaseWbui2025ModalOverlay = require( './modalOverlay.vue' );
const WikibaseWbui2025PropertyLookup = require( './propertyLookup.vue' );
const WikibaseWbui2025EditableAnyDatatypeSnakValue = require( './editableAnyDatatypeSnakValue.vue' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddReference',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025EditableAnyDatatypeSnakValue,
		WikibaseWbui2025ModalOverlay,
		WikibaseWbui2025PropertyLookup
	},
	props: {
		statementId: {
			type: String,
			required: true
		}
	},
	emits: [ 'hide', 'reference-added' ],
	data() {
		return {
			cdxIconCheck,
			snakKey: null
		};
	},
	computed: {
		addButtonDisabled() {
			if ( !this.snakKey ) {
				return true;
			}
			return !wbui2025.store.useEditSnakStore( this.snakKey )().valueStrategy.peekDataValue();
		}
	},
	methods: {
		async onPropertySelection( propertyId, propertyData ) {
			if ( this.snakKey ) {
				wbui2025.store.useEditSnakStore( this.snakKey )().dispose();
			}
			if ( !propertyId || !propertyData ) {
				this.snakKey = null;
				return;
			}
			this.snakKey = wbui2025.store.generateNextSnakKey();
			nextTick( () => {
				this.$refs.snakInput.focus();
			} );
			return wbui2025.store.useEditSnakStore( this.snakKey )().initializeWithSnak( {
				property: propertyId,
				snaktype: 'value',
				datatype: propertyData.datatype,
				datavalue: {
					value: '',
					type: 'string'
				}
			} );
		},
		submitSnakData() {
			wbui2025.store.useEditStatementStore( this.statementId )().addReference( this.snakKey )
					.then( () => this.$emit( 'reference-added' ) );
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
