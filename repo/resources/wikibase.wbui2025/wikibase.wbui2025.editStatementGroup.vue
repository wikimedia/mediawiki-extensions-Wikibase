<template>
	<wikibase-wbui2025-modal-overlay>
		<div class="wikibase-wbui2025-edit-statement">
			<div class="wikibase-wbui2025-edit-statement-heading">
				<div class="wikibase-wbui2025-edit-statement-headline">
					<cdx-icon :icon="cdxIconArrowPrevious" @click="$emit( 'hide' )"></cdx-icon>
					<p class="heading">
						{{ $i18n( 'wikibase-statementgrouplistview-edit', editableStatementGuids.length ) }}
					</p>
				</div>
				<div class="wikibase-wbui2025-property-name" v-html="propertyLinkHtml"></div>
			</div>
			<div class="wikibase-wbui2025-edit-form-body">
				<template v-for="statementGuid in editableStatementGuids" :key="statementGuid">
					<wikibase-wbui2025-edit-statement
						:statement-id="statementGuid"
						@remove="removeStatement"
					></wikibase-wbui2025-edit-statement>
				</template>
				<div class="wikibase-wbui2025-add-value">
					<cdx-button @click="createNewStatement">
						<cdx-icon :icon="cdxIconAdd"></cdx-icon>
						{{ $i18n( 'wikibase-statementlistview-add' ) }}
					</cdx-button>
				</div>
			</div>
			<div class="wikibase-wbui2025-edit-statement-footer">
				<div class="wikibase-wbui2025-edit-form-actions">
					<cdx-button
						weight="quiet"
						@click="$emit( 'hide' )"
					>
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
						{{ $i18n( 'wikibase-cancel' ) }}
					</cdx-button>
					<cdx-button
						action="progressive"
						weight="primary"
						:disabled="formSubmitted"
						@click="submitForm"
					>
						<cdx-icon :icon="cdxIconCheck"></cdx-icon>
						{{ saveMessage }}
					</cdx-button>
				</div>
			</div>
		</div>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
const { mapState, mapActions } = require( 'pinia' );
const { defineComponent } = require( 'vue' );
const { CdxButton, CdxIcon } = require( '../../codex.js' );
const {
	cdxIconAdd,
	cdxIconArrowPrevious,
	cdxIconCheck,
	cdxIconClose
} = require( './icons.json' );

const WikibaseWbui2025EditStatement = require( './wikibase.wbui2025.editStatement.vue' );
const WikibaseWbui2025ModalOverlay = require( './wikibase.wbui2025.modalOverlay.vue' );
const { propertyLinkHtml } = require( './store/serverRenderedHtml.js' );
const { getStatementsForProperty } = require( './store/savedStatementsStore.js' );
const { useEditStatementsStore } = require( './store/editStatementsStore.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditStatementGroup',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025EditStatement,
		WikibaseWbui2025ModalOverlay
	},
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
			formSubmitted: false
		};
	},
	computed: Object.assign( mapState( useEditStatementsStore, {
		editableStatementGuids: 'statementIds'
	} ),
	{
		propertyLinkHtml() {
			return propertyLinkHtml( this.propertyId );
		},
		saveMessage() {
			if ( mw.config.get( 'wgEditSubmitButtonLabelPublish' ) ) {
				return mw.msg( 'wikibase-publish' );
			} else {
				return mw.msg( 'wikibase-save' );
			}
		},
		statements() {
			return getStatementsForProperty( this.propertyId );
		}
	} ),
	methods: Object.assign( mapActions( useEditStatementsStore, {
		initializeEditStatementStoreFromStatementStore: 'initializeFromStatementStore',
		createNewBlankEditableStatement: 'createNewBlankStatement',
		removeStatement: 'removeStatement',
		saveChangedStatements: 'saveChangedStatements'
	} ), {
		createNewStatement() {
			const statementId = new wikibase.utilities.ClaimGuidGenerator( this.entityId ).newGuid();
			this.createNewBlankEditableStatement( statementId, this.propertyId );
		},
		submitForm() {
			this.formSubmitted = true;
			if ( this.editableStatementGuids.length === 0 ) {
				return;
			}
			this.saveChangedStatements( this.entityId )
				.then( () => {
					this.$emit( 'hide' );
				} );
		}
	} ),
	mounted: function () {
		// eslint-disable-next-line vue/no-undef-properties
		if ( this.statements && this.statements.length > 0 ) {
			this.initializeEditStatementStoreFromStatementStore(
				this.statements.map( ( statement ) => statement.id ),
				this.propertyId
			);
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

	.wikibase-wbui2025-edit-statement-heading {
		flex: 0 0 auto;
		padding: @spacing-250 @spacing-75 @spacing-150 @spacing-75;
		gap: @spacing-300;
		align-self: stretch;
		box-shadow: 0 2px 11.8px 0 rgba(0, 0, 0, 0.10);

		.wikibase-wbui2025-edit-statement-headline {
			display: flex;

			span.cdx-icon {
				position: absolute;
				padding: 0;
				cursor: pointer;
			}
		}

		p {
			margin: 0;
			font-style: normal;
			font-weight: 700;
			width: 100%;
			text-align: center;

			&.heading {
				color: @color-emphasized;
				font-size: 1.25rem;
				line-height: 1.5625rem;
				padding: 0;
			}
		}

		div.wikibase-wbui2025-property-name {
			font-style: normal;
			font-weight: 700;
			width: 100%;
			text-align: center;
			color: @color-progressive--focus;
			font-size: 1rem;
			line-height: 1.6rem;
			letter-spacing: -0.003rem;
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

	.wikibase-wbui2025-edit-statement-footer {
		flex: 0 0 auto;
		display: flex;
		padding: @spacing-125 @spacing-400 @spacing-300 @spacing-400;
		flex-direction: column;
		align-items: center;
		gap: 0.625rem;
		box-shadow: 0 -2px 11.8px 0 rgba(0, 0, 0, 0.10);
		justify-content: center;

		.wikibase-wbui2025-edit-form-actions {
			display: flex;
			align-items: center;
			gap: @spacing-200;
		}
	}
}
</style>
