<template>
	<div class="wikibase-wbui2025-edit-statement-value-form">
		<div class="wikibase-wbui2025-value-input-fields">
			<div class="wikibase-wbui2025-edit-statement-value-input">
				<div class="wikibase-snaktypeselector ui-state-default">
					<span class="ui-icon ui-icon-snaktypeselector wikibase-snaktypeselector" :title="$i18n( 'wikibase-snakview-snaktypeselector-value' )"></span>
				</div>
				<cdx-text-input v-model="value"></cdx-text-input>
			</div>
			<div class="wikibase-wbui2025-rank-input">
				<cdx-select
					:selected="rankSelection"
					:menu-items="rankMenuItems"
					@update:selected="rankSelection = $event; $emit( 'update:rank', $event )"
				></cdx-select>
			</div>
		</div>
		<div class="wikibase-wbui2025-qualifiers-and-references">
			<div class="wikibase-wbui2025-button-holder">
				<wbui2025-qualifiers
					:qualifiers="qualifiers"
					:qualifiers-order="qualifiersOrder">
				</wbui2025-qualifiers>
				<cdx-button>
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					{{ $i18n( 'wikibase-addqualifier' ) }}
				</cdx-button>
			</div>
			<div class="wikibase-wbui2025-button-holder">
				<wbui2025-references
					:references="references"
				></wbui2025-references>
				<cdx-button>
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					{{ $i18n( 'wikibase-addreference' ) }}
				</cdx-button>
			</div>
		</div>
		<div class="wikibase-wbui2025-remove-value">
			<cdx-button @click="$emit( 'remove', valueId )">
				<cdx-icon :icon="cdxIconTrash"></cdx-icon>
				{{ $i18n( 'wikibase-remove' ) }}
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
const Wbui2025References = require( './wikibase.wbui2025.references.vue' );
const Wbui2025Qualifiers = require( './wikibase.wbui2025.qualifiers.vue' );

const rankSelectorPreferredIcon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="8" height="20"><defs><path d="M3.1,0 0,3.8 0,6 8,6 8,3.8 4.9,0zm8.2,7 -2.3,2 0,2 2.3,2 3.4,0 2.3,-2 0,-2 -2.3,-2zm6.7,7 0,2.2 3.1,3.8 1.8,0 3.1,-3.8 0,-2.2z" id="a"/><path d="m18.5,10.75 0,-1.5 2,-1.75 3,0 2,1.75 0,1.5 -2,1.75 -3,0zm0,-6.75 0,1.5 7,0 0,-1.5 -2.875,-3.5 -1.25,0zm-9,12 0,-1.5 7,0 0,1.5 -2.875,3.5 -1.25,0zm0,-12 0,1.5 7,0 0,-1.5 -2.875,-3.5 -1.25,0zm-9,12 0,-1.5 7,0 0,1.5 -2.875,3.5 -1.25,0zm0,-5.25 0,-1.5 2,-1.75 3,0 2,1.75 0,1.5 -2,1.75 -3,0z" id="b" fill="none"/></defs><use fill="#36c" x="0" y="0" xlink:href="#a"/><use stroke="#36c" x="0" y="0" xlink:href="#b"/></svg>';
const rankSelectorNormalIcon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="8" height="20"><defs><path d="M3.1,0 0,3.8 0,6 8,6 8,3.8 4.9,0zm8.2,7 -2.3,2 0,2 2.3,2 3.4,0 2.3,-2 0,-2 -2.3,-2zm6.7,7 0,2.2 3.1,3.8 1.8,0 3.1,-3.8 0,-2.2z" id="a"/><path d="m18.5,10.75 0,-1.5 2,-1.75 3,0 2,1.75 0,1.5 -2,1.75 -3,0zm0,-6.75 0,1.5 7,0 0,-1.5 -2.875,-3.5 -1.25,0zm-9,12 0,-1.5 7,0 0,1.5 -2.875,3.5 -1.25,0zm0,-12 0,1.5 7,0 0,-1.5 -2.875,-3.5 -1.25,0zm-9,12 0,-1.5 7,0 0,1.5 -2.875,3.5 -1.25,0zm0,-5.25 0,-1.5 2,-1.75 3,0 2,1.75 0,1.5 -2,1.75 -3,0z" id="b" fill="none"/></defs><use fill="#36c" x="-9" y="0" xlink:href="#a"/><use stroke="#36c" x="-9" y="0" xlink:href="#b"/></svg>';
const rankSelectorDeprecatedIcon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="8" height="20"><defs><path d="M3.1,0 0,3.8 0,6 8,6 8,3.8 4.9,0zm8.2,7 -2.3,2 0,2 2.3,2 3.4,0 2.3,-2 0,-2 -2.3,-2zm6.7,7 0,2.2 3.1,3.8 1.8,0 3.1,-3.8 0,-2.2z" id="a"/><path d="m18.5,10.75 0,-1.5 2,-1.75 3,0 2,1.75 0,1.5 -2,1.75 -3,0zm0,-6.75 0,1.5 7,0 0,-1.5 -2.875,-3.5 -1.25,0zm-9,12 0,-1.5 7,0 0,1.5 -2.875,3.5 -1.25,0zm0,-12 0,1.5 7,0 0,-1.5 -2.875,-3.5 -1.25,0zm-9,12 0,-1.5 7,0 0,1.5 -2.875,3.5 -1.25,0zm0,-5.25 0,-1.5 2,-1.75 3,0 2,1.75 0,1.5 -2,1.75 -3,0z" id="b" fill="none"/></defs><use fill="#36c" x="-18" y="0" xlink:href="#a"/><use stroke="#36c" x="-18" y="0" xlink:href="#b"/></svg>';

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditStatement',
	components: {
		CdxButton,
		CdxIcon,
		CdxSelect,
		CdxTextInput,
		Wbui2025References,
		Wbui2025Qualifiers
	},
	props: {
		valueId: {
			type: Number,
			required: true
		},
		mainSnak: {
			type: Object,
			required: true,
			default: () => ( {
				datavalue: {
					value: '',
					type: 'string'
				}
			} )
		},
		statement: {
			type: Object,
			required: true,
			default: () => ( {} )
		},
		rank: {
			type: String,
			required: true,
			default: () => 'normal'
		}
	},
	emits: [ 'remove', 'update:mainSnak', 'update:rank' ],
	setup() {
		return {
			cdxIconAdd,
			cdxIconTrash
		};
	},
	data() {
		return {
			rankMenuItems: [
				{ label: mw.msg( 'wikibase-statementview-rank-normal' ), value: 'normal', icon: rankSelectorNormalIcon },
				{ label: mw.msg( 'wikibase-statementview-rank-preferred' ), value: 'preferred', icon: rankSelectorPreferredIcon },
				{ label: mw.msg( 'wikibase-statementview-rank-deprecated' ), value: 'deprecated', icon: rankSelectorDeprecatedIcon }
			],
			rankSelection: this.rank
		};
	},
	computed: {
		value: {
			get() {
				return this.mainSnak.datavalue.value;
			},
			set( newValue ) {
				this.$emit( 'update:mainSnak',
					Object.assign( Object.assign( {}, this.mainSnak ), {
						datavalue: {
							value: newValue,
							type: this.mainSnak.datavalue.type
						}
					} ) );
			}
		},
		references() {
			return this.statement.references ? this.statement.references : [];
		},
		qualifiers() {
			return this.statement.qualifiers ? this.statement.qualifiers : {};
		},
		qualifiersOrder() {
			return this.statement[ 'qualifiers-order' ] ? this.statement[ 'qualifiers-order' ] : [];
		}
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
