<template>
	<wikibase-wbui2025-modal-overlay
		ref="modalOverlayRef"
		modal-class="wikibase-wbui2025-edit-statement-group-modal"
		:header="$i18n( 'wikibase-statementgrouplistview-edit', editableStatementGuids.length )"
		:save-button-disabled="!canSubmit"
		:show-progress="showProgress"
		:progress-bar-label="$i18n( 'wikibase-publishing-progress' )"
		@save="submitForm"
		@hide="cancelForm">
		<template v-if="editStatementDataLoaded" #subtitle>
			<div
				class="wikibase-wbui2025-property-name"
				:class="{ 'wikibase-wbui2025-property-name--deleted': isDeletedProperty }"
				v-html="propertyLinkHtml"
			></div>
			<span
				v-if="isDeletedProperty"
				class="wikibase-wbui2025-deleted-property-label"
			>{{ $i18n( 'wikibase-wbui2025-deleted-property-label' ) }}</span>
		</template>
		<template v-if="editStatementDataLoaded" #content>
			<div class="wikibase-wbui2025-edit-form-body">
				<template
					v-for="statementGuid in editableStatementGuidsWithoutNewStatement"
					:key="statementGuid"
				>
					<wikibase-wbui2025-edit-statement
						:statement-id="statementGuid"
						@remove="removeStatement"
					></wikibase-wbui2025-edit-statement>
				</template>
				<div class="wikibase-wbui2025-add-value">
					<cdx-button
						action="progressive"
						@click="startAddValue"
					>
						<cdx-icon :icon="cdxIconAdd"></cdx-icon>
						{{ $i18n( 'wikibase-statementlistview-add' ) }}
					</cdx-button>
				</div>
			</div>
		</template>
	</wikibase-wbui2025-modal-overlay>
	<wikibase-wbui2025-add-statement-value
		v-if="showAddValueModal"
		:statement-id="newStatementId"
		@add="finishAddValue"
		@cancel="cancelAddValue"
	></wikibase-wbui2025-add-statement-value>
</template>

<script>
const { nextTick } = require( 'vue' );
const { mapState, mapActions } = require( 'pinia' );
const { defineComponent } = require( 'vue' );
const {
	CdxButton,
	CdxIcon
} = require( '../../../codex.js' );
const {
	cdxIconAdd
} = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

const saveStatementsFormMixin = require( '../mixins/saveStatementsFormMixin.js' );
const WikibaseWbui2025EditStatement = require( './editStatement.vue' );
const WikibaseWbui2025ModalOverlay = require( './modalOverlay.vue' );
const WikibaseWbui2025AddStatementValue = require( './addStatementValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditStatementGroup',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025EditStatement,
		WikibaseWbui2025ModalOverlay,
		WikibaseWbui2025AddStatementValue
	},
	mixins: [ saveStatementsFormMixin ],
	props: {
		propertyId: {
			type: String,
			required: true
		},
		entityId: {
			type: String,
			required: true
		}
	},
	emits: [ 'hide' ],
	data() {
		return {
			cdxIconAdd,
			showProgress: false,
			formSubmitted: false,
			editStatementDataLoaded: false,
			newStatementId: null,
			showAddValueModal: false
		};
	},
	computed: Object.assign( mapState( wbui2025.store.useEditStatementsStore, {
		editableStatementGuids: 'statementIds',
		fullyParsed: 'isFullyParsed',
		hasChanges: 'hasChanges'
	} ),
	{
		propertyLinkHtml() {
			return wbui2025.store.propertyLinkHtml( this.propertyId );
		},
		isDeletedProperty() {
			return wbui2025.store.isDeletedProperty( this.propertyId );
		},
		statements() {
			return wbui2025.store.getStatementsForProperty( this.propertyId );
		},
		editableStatementGuidsWithoutNewStatement() {
			// Filter 'newStatementId' from the list so that we do not display the empty
			// statement while the addValue modal is loading.
			return this.editableStatementGuids.filter( ( statement ) => statement !== this.newStatementId );
		},
		propertyDatatype() {
			if (
			// eslint-disable-next-line vue/no-undef-properties
				this.statements &&
				this.statements.length > 0 &&
				this.statements[ 0 ] &&
				this.statements[ 0 ].mainsnak &&
				this.statements[ 0 ].mainsnak.datatype
			) {
				return this.statements[ 0 ].mainsnak.datatype;
			}
			return 'string';
		},
		canSubmit() {
			return !this.formSubmitted && this.fullyParsed && this.hasChanges === true;
		}
	} ),
	methods: Object.assign( mapActions( wbui2025.store.useEditStatementsStore, {
		disposeOfEditableStatementStores: 'disposeOfStores',
		initializeEditStatementStoreFromStatementStore: 'initializeFromStatementStore',
		createNewBlankEditableStatement: 'createNewBlankStatement',
		removeStatement: 'removeStatement'
	} ), {
		startAddValue() {
			const guid = new wikibase.utilities.ClaimGuidGenerator( this.entityId ).newGuid();
			this.newStatementId = guid;
			this.createNewBlankEditableStatement(
				guid,
				this.propertyId,
				// eslint-disable-next-line vue/no-undef-properties
				this.propertyDatatype
			).then( () => {
				this.showAddValueModal = true;
			} );
		},
		finishAddValue() {
			this.newStatementId = null;
			this.showAddValueModal = false;
		},
		cancelAddValue() {
			if ( this.newStatementId ) {
				this.removeStatement( this.newStatementId );
			}
			this.newStatementId = null;
			this.showAddValueModal = false;
		},
		submitForm() {
			const propertyId = this.propertyId;
			// eslint-disable-next-line vue/no-undef-properties
			this.submitFormWithElementRef( this.$refs.modalOverlayRef.$refs.modalOverlayActionsRef )
				.then( ( { success } ) => {
					if ( success ) {
						nextTick( () => wbui2025.util.scrollToStatementWithPropertyId( propertyId ) );
					}
				} );
		},
		cancelForm() {
			this.$emit( 'hide' );

			nextTick( () => {
				this.disposeOfEditableStatementStores();
			} );
		}
	} ),
	beforeMount: function () {
		if ( this.statements && this.statements.length > 0 ) {
			this.initializeEditStatementStoreFromStatementStore(
				this.statements.map( ( statement ) => statement.id ),
				this.propertyId
			).then( () => {
				this.editStatementDataLoaded = true;
			} );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-edit-statement-group-modal {
	.wikibase-wbui2025-modal-overlay__header {
		padding: @spacing-125 @spacing-75;
	}

	.wikibase-wbui2025-property-name {
		font-weight: @font-weight-bold;
		color: @color-progressive--focus;
		font-size: @font-size-medium;
		line-height: 1.6rem;
		text-align: center;
	}

	.wikibase-wbui2025-edit-form-body {
		flex: 1 1 auto;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: 0.625rem;
		align-self: stretch;
	}

	.wikibase-wbui2025-add-value {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: @spacing-150;
		align-self: stretch;
		padding: @spacing-125 @spacing-100;
		button.cdx-button {
			width: @size-full;
			justify-content: flex-start;
		}
	}

	button .wikibase-snaktypeselector {
		background-repeat: no-repeat;
	}
	button[disabled] .wikibase-snaktypeselector {
		background-position: 0.125rem 0;
	}
}
</style>
