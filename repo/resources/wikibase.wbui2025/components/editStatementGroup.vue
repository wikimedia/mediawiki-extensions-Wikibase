<template>
	<wikibase-wbui2025-modal-overlay
		modal-class="wikibase-wbui2025-edit-statement-group-modal">
		<div v-if="editStatementDataLoaded" class="wikibase-wbui2025-edit-statement">
			<div class="wikibase-wbui2025-edit-statement-heading">
				<cdx-icon :icon="cdxIconArrowPrevious" @click="cancelEditForm"></cdx-icon>
				<div class="wikibase-wbui2025-edit-statement-headline">
					<p class="heading">
						{{ $i18n( 'wikibase-statementgrouplistview-edit', editableStatementGuids.length ) }}
					</p>
				</div>
				<div class="wikibase-wbui2025-property-name" v-html="propertyLinkHtml"></div>
			</div>
			<div class="wikibase-wbui2025-edit-form-body">
				<template v-if="editStatementDataLoaded">
					<template
						v-for="statementGuid in editableStatementGuidsWithoutNewStatement"
						:key="statementGuid"
					>
						<wikibase-wbui2025-edit-statement
							:statement-id="statementGuid"
							@remove="removeStatement"
						></wikibase-wbui2025-edit-statement>
					</template>
				</template>
				<div
					v-else
					class="wikibase-wbui2025-edit-statement-value-form loading-placeholder"
				>
				</div>
				<div class="wikibase-wbui2025-add-value">
					<cdx-button @click="startAddValue">
						<cdx-icon :icon="cdxIconAdd"></cdx-icon>
						{{ $i18n( 'wikibase-statementlistview-add' ) }}
					</cdx-button>
				</div>
			</div>
			<transition name="fade">
				<div v-if="showProgress" class="wikibase-wbui2025-message-container">
					<cdx-message
						type="notice"
						class="wikibase-wbui2025-message"
					>
						{{ $i18n( 'wikibase-publishing-progress' ) }}
					</cdx-message>
				</div>
			</transition>
			<div ref="editFormActionsRef" class="wikibase-wbui2025-edit-statement-footer">
				<transition name="fade">
					<cdx-progress-bar
						v-if="showProgress"
						:value="100"
						inline
						aria-label="Publishing in progress"
					></cdx-progress-bar>
				</transition>

				<div class="wikibase-wbui2025-edit-form-actions">
					<cdx-button
						weight="quiet"
						@click="cancelEditForm"
					>
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
						{{ $i18n( 'wikibase-cancel' ) }}
					</cdx-button>
					<cdx-button
						action="progressive"
						weight="primary"
						:disabled="!canSubmit"
						@click="submitForm"
					>
						<cdx-icon :icon="cdxIconCheck"></cdx-icon>
						{{ saveMessage }}
					</cdx-button>
				</div>
			</div>
		</div>
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
	CdxIcon,
	CdxMessage,
	CdxProgressBar
} = require( '../../../codex.js' );
const {
	cdxIconAdd,
	cdxIconArrowPrevious,
	cdxIconCheck,
	cdxIconClose
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
		CdxMessage,
		CdxProgressBar,
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
			cdxIconArrowPrevious,
			cdxIconCheck,
			cdxIconClose,
			showProgress: false,
			formSubmitted: false,
			editStatementDataLoaded: false,
			newStatementId: null,
			showEditStatementModal: true,
			showAddValueModal: false,
			progressTimeout: null
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
		saveMessage() {
			return mw.config.get( 'wgEditSubmitButtonLabelPublish' )
				? mw.msg( 'wikibase-publish' )
				: mw.msg( 'wikibase-save' );
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
		removeStatement: 'removeStatement',
		saveChangedStatements: 'saveChangedStatements'
	} ), mapActions( wbui2025.store.useMessageStore, [
		'addStatusMessage'
	] ), {
		startAddValue() {
			const guid = new wikibase.utilities.ClaimGuidGenerator( this.entityId ).newGuid();
			this.newStatementId = guid;
			this.createNewBlankEditableStatement(
				guid,
				this.propertyId,
				// eslint-disable-next-line vue/no-undef-properties
				this.propertyDatatype
			).then( () => {
				this.showEditStatementModal = false;
				this.showAddValueModal = true;
			} );
		},
		finishAddValue() {
			this.newStatementId = null;
			this.showAddValueModal = false;
			this.showEditStatementModal = true;
		},
		cancelAddValue() {
			if ( this.newStatementId ) {
				this.removeStatement( this.newStatementId );
			}
			this.newStatementId = null;
			this.showAddValueModal = false;
			this.showEditStatementModal = true;
		},
		submitForm() {
			this.formSubmitted = true;
			setTimeout( () => {
				this.showProgress = true;
			}, 300 );
			this.saveChangedStatements( this.entityId )
			.then( () => {
				clearTimeout( this.progressTimeout );
				this.$emit( 'hide' );
				this.disposeOfEditableStatementStores();
				this.addStatusMessage( {
					type: 'success',
					text: mw.msg( 'wikibase-publishing-succeeded' )
				} );
				this.showProgress = false;
				this.showEditStatementModal = false;
			} )
			.catch( () => {
				clearTimeout( this.progressTimeout );
				this.addStatusMessage( {
					text: mw.msg( 'wikibase-publishing-error' ),
					attachTo: this.$refs.editFormActionsRef,
					type: 'error'
				} );
				this.formSubmitted = false;
				this.showProgress = false;
			} );
		},
		cancelEditForm() {
			this.showAddValueModal = false;
			this.showEditStatementModal = false;

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

.wikibase-wbui2025-edit-statement {
	position: relative;
	background-color: @background-color-base;
	display: flex;
	flex-direction: column;
	overflow: hidden;
	height: 100%;
	width: 100%;

	ul {
		padding-left: 0;
	}

	li {
		margin-bottom: 0;
	}

	.wikibase-wbui2025-edit-statement-heading {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: @spacing-125 @spacing-75;
		box-shadow: 0 2px 11.8px 0 rgba(0, 0, 0, 0.10);
		gap: @spacing-25;

		.cdx-icon {
			position: absolute;
			left: @spacing-75;
			top: 50%;
			transform: translateY(-50%);
			cursor: pointer;
		}

		.wikibase-wbui2025-edit-statement-headline {
			text-align: center;

			p.heading {
				margin: 0;
				font-weight: 700;
				font-size: 1.25rem;
				line-height: 1.5625rem;
				padding: 0;
				color: @color-emphasized;
			}
		}

		.wikibase-wbui2025-property-name {
			font-weight: 700;
			color: @color-progressive--focus;
			font-size: 1rem;
			line-height: 1.6rem;
			text-align: center;
		}
	}

	.wikibase-wbui2025-edit-form-body {
		flex: 1 1 auto;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: 0.625rem;
		align-self: stretch;

		.wikibase-wbui2025-add-value {
			display: flex;
			flex-direction: column;
			align-items: flex-start;
			gap: @spacing-150;
			align-self: stretch;
			padding: @spacing-125 @spacing-100;

			button.cdx-button {
				width: 100%;
				cursor: pointer;
				justify-content: flex-start;
				border-color: @border-color-interactive;
				background: @background-color-interactive-subtle;
			}
		}
	}

	.wikibase-wbui2025-message-container {
		display: flex;
		justify-content: center;
		padding: @spacing-150;
	}

	.wikibase-wbui2025-message {
		display: flex;
		width: 343px;
		max-width: 90%;
		padding: @spacing-100 @spacing-100 @spacing-100 @spacing-150;
		justify-content: flex-end;
		align-items: center;
		gap: @spacing-50;
	}
	.cdx-progress-bar {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
	}

	.wikibase-wbui2025-edit-statement-footer {
		flex: 0 0 auto;
		display: flex;
		padding: @spacing-125 @spacing-400 @spacing-200;
		flex-direction: column;
		align-items: center;
		gap: 0.625rem;
		box-shadow: 0 -2px 11.8px 0 rgba(0, 0, 0, 0.10);
		justify-content: center;
		position: relative;

		.wikibase-wbui2025-edit-form-actions {
			display: flex;
			align-items: center;
			gap: @spacing-200;
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
