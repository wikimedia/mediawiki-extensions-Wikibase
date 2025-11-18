<template>
	<div class="wikibase-wbui2025-add-statement-button">
		<wbui2025-modal-overlay
			v-if="addStatementModalVisible"
			ref="modalOverlayRef"
			:header="$i18n( 'wikibase-addstatement' )"
			:save-button-disabled="formSubmitted || !fullyParsed || hasChanges !== true"
			:show-progress="showProgress"
			:progress-bar-label="$i18n( 'wikibase-publishing-progress' )"
			@save="submitForm"
			@hide="hide"
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
				<template v-for="statementGuid in createdStatementGuids" :key="statementGuid">
					<wikibase-wbui2025-edit-statement
						hide-remove-button
						:statement-id="statementGuid"
					></wikibase-wbui2025-edit-statement>
				</template>
			</template>
		</wbui2025-modal-overlay>
		<cdx-button
			action="progressive"
			@click="showAddStatementModal"
		>
			<cdx-icon :icon="cdxIconAdd"></cdx-icon>
			{{ $i18n( 'wikibase-addstatement' ) }}
		</cdx-button>
	</div>
</template>

<script>
const { mapState, mapActions } = require( 'pinia' );
const { defineComponent } = require( 'vue' );

const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconAdd } = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025ModalOverlay = require( './modalOverlay.vue' );
const WikibaseWbui2025EditStatement = require( './editStatement.vue' );
const WikibaseWbui2025PropertyLookup = require( './propertyLookup.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementButton',
	components: {
		CdxButton,
		CdxIcon,
		Wbui2025ModalOverlay,
		WikibaseWbui2025PropertyLookup,
		WikibaseWbui2025EditStatement
	},
	props: {
		entityId: {
			type: String,
			required: true
		}
	},
	data: () => ( {
		cdxIconAdd,
		addStatementModalVisible: false,
		propertyId: null,
		propertyData: null,
		formSubmitted: false,
		showProgress: false
	} ),
	computed: Object.assign( mapState( wbui2025.store.useEditStatementsStore, {
		createdStatementGuids: 'createdStatementIds',
		fullyParsed: 'isFullyParsed',
		hasChanges: 'hasChanges'
	} ), {
		propertyDatatype() {
			return this.propertyData ? this.propertyData.datatype : null;
		},
		saveMessage() {
			return mw.config.get( 'wgEditSubmitButtonLabelPublish' )
				? mw.msg( 'wikibase-publish' )
				: mw.msg( 'wikibase-save' );
		}
	} ),
	methods: Object.assign( mapActions( wbui2025.store.useEditStatementsStore, {
		disposeOfEditableStatementStores: 'disposeOfStores',
		initializeEditStatementStoreFromStatementStore: 'initializeFromStatementStore',
		createNewBlankEditableStatement: 'createNewBlankStatement',
		saveChangedStatements: 'saveChangedStatements'
	} ), mapActions( wbui2025.store.useMessageStore, [
		'addStatusMessage'
	] ), {
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
			this.propertyId = propertyId;
			this.propertyData = propertyData;
			// eslint-disable-next-line vue/no-undef-properties
			this.createNewStatement();
		},
		submitForm() {
			const progressTimeout = setTimeout( () => {
				this.showProgress = true;
			}, 300 );
			this.formSubmitted = true;
			if ( this.createdStatementGuids.length === 0 ) {
				return;
			}
			this.saveChangedStatements( this.entityId )
				.then( () => {
					// eslint-disable-next-line vue/no-undef-properties
					this.hide();
					this.addStatusMessage( {
						type: 'success',
						text: mw.msg( 'wikibase-publishing-succeeded' )
					} );
					clearTimeout( progressTimeout );
					this.showProgress = false;
				} )
				.catch( () => {
					this.addStatusMessage( {
						text: mw.msg( 'wikibase-publishing-error' ),
						type: 'error',
						attachTo: this.$refs.modalOverlayRef.$refs.modalOverlayActionsRef
					} );
					this.formSubmitted = false;
					clearTimeout( progressTimeout );
					this.showProgress = false;
				} );
		},
		reset() {
			this.disposeOfEditableStatementStores();
			this.initializeEditStatementStoreFromStatementStore( [], null );
			this.propertyId = null;
			this.propertyData = null;
		},
		hide() {
			this.reset();
			this.addStatementModalVisible = false;
		},
		showAddStatementModal() {
			this.reset();
			this.addStatementModalVisible = true;
		}
	} ),
	beforeMount() {
		this.initializeEditStatementStoreFromStatementStore( [], null );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-add-statement-button {
	margin-top: 1em;
}

.wikibase-wbui2025-add-statement-form {
	.wikibase-wbui2025-add-statement-form_property-selector {
		padding: @spacing-250 @spacing-100;
		background: @background-color-progressive-subtle;
	}
}
</style>
