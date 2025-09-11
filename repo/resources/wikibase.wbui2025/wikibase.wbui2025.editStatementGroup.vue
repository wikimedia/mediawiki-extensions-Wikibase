<template>
	<wikibase-wbui2025-modal-overlay>
		<div class="wikibase-wbui2025-edit-statement">
			<div class="wikibase-wbui2025-edit-statement-heading">
				<div class="wikibase-wbui2025-edit-statement-headline">
					<cdx-icon :icon="cdxIconArrowPrevious" @click="$emit( 'hide' )"></cdx-icon>
					<p class="heading">
						{{ $i18n( 'wikibase-statementgrouplistview-edit', valueForms.length ) }}
					</p>
				</div>
				<div class="wikibase-wbui2025-property-name" v-html="propertyLinkHtml"></div>
			</div>
			<div class="wikibase-wbui2025-edit-form-body">
				<template v-for="( valueForm, index ) in valueForms" :key="valueForm.id">
					<wikibase-wbui2025-edit-statement
						v-model:rank="valueForms[index].statement.rank"
						v-model:main-snak="valueForms[index].statement.mainsnak"
						:statement="valueForms[index].statement"
						:value-id="valueForm.id"
						@remove="removeValue"
					></wikibase-wbui2025-edit-statement>
				</template>
				<div class="wikibase-wbui2025-add-value">
					<cdx-button @click="addValue( null )">
						<cdx-icon :icon="cdxIconAdd"></cdx-icon>
						{{ $i18n( 'wikibase-statementlistview-add' ) }}
					</cdx-button>
				</div>
			</div>
			<div class="wikibase-wbui2025-edit-statement-footer">
				<div class="wikibase-wbui2025-edit-form-actions">
					<cdx-button @click="$emit( 'hide' )">
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
						{{ $i18n( 'wikibase-cancel' ) }}
					</cdx-button>
					<cdx-button class="inactive">
						<cdx-icon :icon="cdxIconCheck"></cdx-icon>
						{{ saveMessage }}
					</cdx-button>
				</div>
			</div>
		</div>
	</wikibase-wbui2025-modal-overlay>
</template>

<script>
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
		statements: {
			type: Array,
			required: true
		}
	},
	emits: [ 'hide' ],
	setup() {
		return {
			cdxIconAdd,
			cdxIconArrowPrevious,
			cdxIconCheck,
			cdxIconClose
		};
	},
	data() {
		return {
			valueForms: [],
			maxValueFormId: 0
		};
	},
	computed: {
		propertyLinkHtml() {
			return propertyLinkHtml( this.propertyId );
		},
		saveMessage() {
			if ( mw.config.get( 'wgEditSubmitButtonLabelPublish' ) ) {
				return mw.msg( 'wikibase-publish' );
			} else {
				return mw.msg( 'wikibase-save' );
			}
		}
	},
	methods: {
		addValue( statement = null ) {
			const valueForm = { id: this.maxValueFormId, statement: {} };
			this.maxValueFormId++;
			if ( statement ) {
				valueForm.statement = Object.assign( {}, statement );
			} else {
				valueForm.statement = Object.assign( {}, {
					mainSnak: {
						datavalue: {
							value: '',
							type: 'string'
						}
					},
					rank: 'normal',
					'qualifiers-order': [],
					qualifiers: {}
				} );
			}
			this.valueForms.push( valueForm );
		},
		removeValue( valueId ) {
			this.valueForms = this.valueForms.filter( ( form ) => form.id !== valueId );
		}
	},
	mounted: function () {
		if ( this.statements && this.statements.length > 0 ) {
			this.statements.forEach( ( statement ) => this.addValue( statement ) );
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

			button.cdx-button {
				cursor: pointer;
				border-color: @border-color-transparent;
				background: @background-color-transparent;

				&.inactive {
					color: @color-disabled;
					background: @background-color-disabled;
				}
			}
		}
	}
}
</style>
