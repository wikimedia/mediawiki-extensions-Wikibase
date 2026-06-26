<template>
	<wbui2025-modal-overlay
		ref="modalOverlayRef"
		:header="$i18n( 'wikibase-addstatement' )"
		:save-button-disabled="!canSubmit"
		:show-progress="showProgress"
		:progress-bar-label="$i18n( 'wikibase-publishing-progress' )"
		@save="submitForm"
		@hide="cancelForm"
	>
		<template #content>
			<div class="wikibase-wbui2025-add-statement-form">
				<div class="wikibase-wbui2025-add-statement-form_property-selector">
					<wikibase-wbui2025-property-lookup
						@update:selected="onPropertySelection"
					>
					</wikibase-wbui2025-property-lookup>
				</div>
			</div>
			<div
				v-if="isDuplicate"
				class="wikibase-wbui2025-add-statement-duplicate-warning"
			>
				<cdx-message type="warning">
					{{ $i18n( 'wikibase-wbui2025-duplicate-statement-warning' ).text() }}
				</cdx-message>
				<cdx-button
					action="progressive"
					size="large"
					@click="goToExistingStatement"
				>
					<cdx-icon :icon="cdxIconEdit"></cdx-icon>
					{{ duplicateStatementEditButtonLabel }}
				</cdx-button>
			</div>
			<template v-for="statementGuid in createdStatementGuids" :key="statementGuid">
				<wikibase-wbui2025-edit-statement
					hide-remove-button
					:statement-id="statementGuid"
				></wikibase-wbui2025-edit-statement>
			</template>
		</template>
	</wbui2025-modal-overlay>
	<wikibase-wbui2025-edit-statement-group
		v-if="showEditExistingGroup"
		:property-id="propertyId"
		:entity-id="entityId"
		@hide="onEditExistingGroupHide"
	></wikibase-wbui2025-edit-statement-group>
</template>

<script>
const { mapState, mapActions } = require( 'pinia' );
const { defineComponent, nextTick } = require( 'vue' );

const { CdxButton, CdxIcon, CdxMessage } = require( '../../../codex.js' );
const { cdxIconEdit } = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025ModalOverlay = require( './modalOverlay.vue' );
const WikibaseWbui2025EditStatement = require( './editStatement.vue' );
const WikibaseWbui2025EditStatementGroup = require( './editStatementGroup.vue' );
const WikibaseWbui2025PropertyLookup = require( './propertyLookup.vue' );
const saveStatementsFormMixin = require( '../mixins/saveStatementsFormMixin.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementModal',
	components: {
		CdxButton,
		CdxIcon,
		CdxMessage,
		Wbui2025ModalOverlay,
		WikibaseWbui2025PropertyLookup,
		WikibaseWbui2025EditStatement,
		WikibaseWbui2025EditStatementGroup
	},
	mixins: [ saveStatementsFormMixin ],
	props: {
		entityId: {
			type: String,
			required: true
		},
		sectionKey: {
			type: String,
			required: true
		}
	},
	emits: [ 'hide' ],
	data: () => ( {
		propertyId: null,
		propertyData: null,
		isDuplicate: false,
		showEditExistingGroup: false,
		formSubmitted: false,
		showProgress: false,
		cdxIconEdit
	} ),
	computed: Object.assign( mapState( wbui2025.store.useEditStatementsStore, {
		createdStatementGuids: 'createdStatementIds',
		fullyParsed: 'isFullyParsed',
		hasChanges: 'hasChanges'
	} ), {
		propertyDatatype() {
			return this.propertyData ? this.propertyData.datatype : null;
		},
		canSubmit() {
			return !this.formSubmitted && !this.isDuplicate && this.fullyParsed && this.hasChanges;
		},
		duplicateStatementEditButtonLabel() {
			if ( !this.isDuplicate ) {
				return '';
			}
			return mw.msg( 'wikibase-wbui2025-duplicate-statement-edit-button', this.propertyData.label );
		}
	} ),
	methods: Object.assign( mapActions( wbui2025.store.useEditStatementsStore, {
		disposeOfEditableStatementStores: 'disposeOfStores',
		initializeEditStatementStoreFromStatementStore: 'initializeFromStatementStore',
		createNewBlankEditableStatement: 'createNewBlankStatement'
	} ), {
		createNewStatement() {
			const statementId = new wikibase.utilities.ClaimGuidGenerator( this.entityId ).newGuid();

			this.createNewBlankEditableStatement(
				statementId,
				this.propertyId,
				this.propertyData ? this.propertyData.datatype : null
			);
		},
		onPropertySelection( propertyId, propertyData ) {
			if ( this.createdStatementGuids && this.createdStatementGuids.length > 0 ) {
				// eslint-disable-next-line vue/no-undef-properties
				this.reset();
			}
			this.isDuplicate = false;
			this.propertyId = propertyId;
			this.propertyData = propertyData;
			if ( !propertyId ) {
				return;
			}
			if ( wbui2025.store.hasStatementsForProperty( propertyId ) ) {
				this.isDuplicate = true;
				return;
			}
			// eslint-disable-next-line vue/no-undef-properties
			this.createNewStatement();
		},
		goToExistingStatement() {
			this.showEditExistingGroup = true;
		},
		onEditExistingGroupHide() {
			this.showEditExistingGroup = false;
			// eslint-disable-next-line vue/no-undef-properties
			this.cancelForm();
		},
		submitForm() {
			const propertyId = this.propertyId;
			// eslint-disable-next-line vue/no-undef-properties
			this.submitFormWithElementRef( this.$refs.modalOverlayRef.$refs.modalOverlayActionsRef )
				.then( ( { success } ) => {
					if ( success ) {
						nextTick( () => {
							wbui2025.util.scrollToStatementWithPropertyId( propertyId );
						} );
					}
				} );
			wbui2025.store.setStatementSectionForPropertyId( this.propertyId, this.sectionKey );
			wbui2025.api.renderPropertyLinkHtml( [ this.propertyId ] )
					.then( ( result ) => wbui2025.store.updatePropertyLinkHtml( result ) );
		},
		reset() {
			this.disposeOfEditableStatementStores();
			this.initializeEditStatementStoreFromStatementStore( [], null );
			this.propertyId = null;
			this.propertyData = null;
			this.isDuplicate = false;
		},
		cancelForm() {
			this.reset();
			this.$emit( 'hide' );
		}
	} ),
	beforeMount() {
		this.initializeEditStatementStoreFromStatementStore( [], null );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-add-statement-form {
	.wikibase-wbui2025-add-statement-form_property-selector {
		padding: @spacing-250 @spacing-100;
		background: @background-color-progressive-subtle;
	}
}

.wikibase-wbui2025-add-statement-duplicate-warning {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: @spacing-100;
	padding: @spacing-100;

	.cdx-button {
		white-space: normal;
		text-align: inherit;
	}
}
</style>
