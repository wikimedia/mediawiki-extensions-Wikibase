<template>
	<div class="wikibase-wbui2025-edit-statement-modal-overlay">
		<div class="wikibase-wbui2025-edit-statement">
			<div class="wikibase-wbui2025-edit-statement-heading">
				<div class="wikibase-wbui2025-edit-statement-headline">
					<cdx-icon :icon="cdxIconArrowPrevious" @click="$emit( 'hide' )"></cdx-icon>
					<p class="heading">
						edit statement
					</p>
				</div>
				<div class="wikibase-wbui2025-property-name" v-html="propertyLinkHtml"></div>
			</div>
			<div class="wikibase-wbui2025-edit-form-body">
				<template v-for="valueForm in valueForms" :key="valueForm.id">
					<wikibase-wbui2025-edit-statement
						:value-id="valueForm.id"
						@remove="removeValue"
					></wikibase-wbui2025-edit-statement>
				</template>
				<div class="wikibase-wbui2025-add-value">
					<cdx-button @click="addValue">
						<cdx-icon :icon="cdxIconAdd"></cdx-icon>
						add value
					</cdx-button>
				</div>
			</div>
			<div class="wikibase-wbui2025-edit-statement-footer">
				<div class="wikibase-wbui2025-edit-form-actions">
					<cdx-button @click="$emit( 'hide' )">
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
						cancel
					</cdx-button>
					<cdx-button class="inactive">
						<cdx-icon :icon="cdxIconCheck"></cdx-icon>
						publish
					</cdx-button>
				</div>
			</div>
		</div>
	</div>
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
const { propertyLinkHtml } = require( './store/serverRenderedHtml.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditStatementGroup',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025EditStatement
	},
	props: {
		propertyId: {
			type: String,
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
			valueForms: [
				{ id: 0 }
			]
		};
	},
	computed: {
		propertyLinkHtml() {
			return propertyLinkHtml( this.propertyId );
		}
	},
	methods: {
		addValue() {
			const maxId = this.valueForms.reduce( ( max, form ) => Math.max( max, form.id ), 0 ) + 1;
			this.valueForms.push( { id: maxId } );
		},
		removeValue( valueId ) {
			this.valueForms = this.valueForms.filter( ( form ) => form.id !== valueId );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-edit-statement-modal-overlay {
	position: fixed;
	background-color: @background-color-base;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	display: flex;
	justify-content: center;
	align-items: center;
	z-index: 1;
}

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
