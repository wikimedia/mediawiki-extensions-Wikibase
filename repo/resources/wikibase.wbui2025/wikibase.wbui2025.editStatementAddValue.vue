<template>
	<div class="wikibase-wbui2025-edit-statement-value-form">
		<div class="wikibase-wbui2025-value-input-fields">
			<div class="wikibase-wbui2025-edit-statement-value-input">
				<div class="wikibase-snaktypeselector ui-state-default">
					<span class="ui-icon ui-icon-snaktypeselector wikibase-snaktypeselector" title="custom value"></span>
				</div>
				<cdx-text-input v-model="value" placeholder="add property value here"></cdx-text-input>
			</div>
			<div class="wikibase-wbui2025-rank-input">
				<div class="wikibase-rankselector ui-state-default">
					<span class="ui-icon ui-icon-rankselector wikibase-rankselector-normal" title="Normal rank"></span>
				</div>
				<cdx-select
					v-model:selected="rankSelection"
					:menu-items="rankMenuItems"
				></cdx-select>
			</div>
		</div>
		<div class="wikibase-wbui2025-qualifiers-and-references">
			<div class="wikibase-wbui2025-button-holder">
				<cdx-button>
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					add qualifier
				</cdx-button>
			</div>
			<div class="wikibase-wbui2025-button-holder">
				<cdx-button>
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					add reference
				</cdx-button>
			</div>
		</div>
		<div class="wikibase-wbui2025-remove-value">
			<cdx-button @click="$emit( 'remove', valueId )">
				<cdx-icon :icon="cdxIconTrash"></cdx-icon>
				remove
			</cdx-button>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxButton, CdxIcon, CdxSelect, CdxTextInput } = require( '../../codex.js' );
const {
	cdxIconAdd,
	cdxIconTrash
} = require( './icons.json' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditStatementAddValue',
	components: {
		CdxButton,
		CdxIcon,
		CdxSelect,
		CdxTextInput
	},
	props: {
		valueId: {
			type: Number,
			required: true
		}
	},
	emits: [ 'remove' ],
	setup() {
		return {
			cdxIconAdd,
			cdxIconTrash
		};
	},
	data() {
		return {
			value: '',
			rankMenuItems: [
				{ label: 'normal rank', value: 0 }
			],
			rankSelection: 0
		};
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-edit-statement-value-form {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: @spacing-150;
	align-self: stretch;
	padding: @spacing-125 @spacing-100;
	border-bottom: 1px solid @border-color-muted;

	.wikibase-wbui2025-value-input-fields {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: @spacing-75;
		align-self: stretch;

		.wikibase-wbui2025-edit-statement-value-input {
			width: 100%;
			display: flex;
			justify-content: center;
			gap: @spacing-75;

			.wikibase-snaktypeselector {
				position: relative;
				padding: 0;
				margin-top: 3px;
				display: inline-block;
			}

			div.cdx-text-input {
				width: 100%;

				input {
					width: 100%;
				}
			}
		}

		.wikibase-wbui2025-rank-input {
			display: flex;
			align-items: center;
			gap: @spacing-75;

			.wikibase-rankselector {
				position: relative;
				padding: 3px;
			}

			.wikibase-rankselector .ui-icon.ui-icon-rankselector {
				display: inherit;
			}

			.cdx-select-vue div.cdx-select-vue__handle {
				border-color: var(--border-neutral-hover, #a2a9b1);
			}
		}
	}

	.wikibase-wbui2025-qualifiers-and-references {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: @spacing-100;
		align-self: stretch;

		div.wikibase-wbui2025-button-holder {
			width: 100%;

			button.cdx-button {
				width: 100%;
				cursor: pointer;
				justify-content: flex-start;
				border-color: @border-color-progressive;
				background: @background-color-progressive-subtle;
				color: @color-progressive;
			}
		}
	}

	.wikibase-wbui2025-remove-value {
		button.cdx-button {
			cursor: pointer;
			background-color: @background-color-base;
			border: 0;
		}
	}
}
</style>
