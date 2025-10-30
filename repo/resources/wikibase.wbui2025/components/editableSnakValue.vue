<template>
	<div class="wikibase-wbui2025-edit-statement-snak-value">
		<div class="wikibase-snaktypeselector ui-state-default">
			<cdx-menu-button
				v-model:selected="snakTypeSelection"
				:menu-items="snakTypeMenuItems"
				:disabled="disabled"
			>
				<span class="ui-icon ui-icon-snaktypeselector wikibase-snaktypeselector" :title="snakTypeSelectionMessage"></span>
			</cdx-menu-button>
		</div>
		<div
			class="wikibase-wbui2025-snak-value"
			:data-snak-hash="hash"
		>
			<component
				:is="valueStrategy.getEditableSnakComponent()"
				v-if="snakTypeSelection === 'value'"
				ref="inputElement"
				:snak-key="snakKey"
				:class="className"
				:disabled="disabled"
			></component>
			<div v-else class="wikibase-wbui2025-novalue-somevalue-holder">
				<p>{{ snakTypeSelectionMessage }}</p>
			</div>
			<div v-if="removable" class="wikibase-wbui2025-remove-snak">
				<cdx-button
					weight="quiet"
					:aria-label="$i18n( 'wikibase-remove' )"
					:disabled="disabled"
					@click="$emit( 'remove-snak', snakKey )"
				>
					<cdx-icon :icon="cdxIconTrash"></cdx-icon>
				</cdx-button>
			</div>
		</div>
	</div>
</template>

<script>
const { computed, defineComponent } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const { cdxIconTrash } = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025EditableStringSnakValue = require( './editableStringSnakValue.vue' );
const Wbui2025EditableLookupSnakValue = require( './editableLookupSnakValue.vue' );
const { CdxButton, CdxIcon, CdxMenuButton } = require( '../../../codex.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableSnakValue',
	components: {
		CdxButton,
		CdxIcon,
		CdxMenuButton,
		Wbui2025EditableStringSnakValue,
		Wbui2025EditableLookupSnakValue
	},
	props: {
		removable: {
			type: Boolean,
			required: false,
			default: false
		},
		snakKey: {
			type: String,
			required: true
		},
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false
		}
	},
	emits: [ 'remove-snak' ],
	setup( props ) {
		/*
		 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
		 * store - we pass in the snakHash to make a snak-specific store. This forces us to use
		 * the Composition API to initialise the component.
		 */
		const editSnakStoreGetter = wbui2025.store.useEditSnakStore( props.snakKey );
		const computedProperties = mapWritableState( editSnakStoreGetter, [
			'textvalue',
			'snaktype',
			'hash',
			'valueStrategy'
		] );
		return {
			textvalue: computed( computedProperties.textvalue ),
			snaktype: computed( computedProperties.snaktype ),
			hash: computed( computedProperties.hash ),
			valueStrategy: computed( computedProperties.valueStrategy )
		};
	},
	data() {
		return {
			cdxIconTrash,
			snakTypeMenuItems: [
				{ label: mw.msg( 'wikibase-snakview-snaktypeselector-value' ), value: 'value' },
				{ label: mw.msg( 'wikibase-snakview-variations-novalue-label' ), value: 'novalue' },
				{ label: mw.msg( 'wikibase-snakview-variations-somevalue-label' ), value: 'somevalue' }
			],
			previousValue: null
		};
	},
	computed: {
		snakTypeSelection: {
			get() {
				return this.snaktype;
			},
			set( newSnakTypeSelection ) {
				if ( this.snaktype === 'value' ) {
					this.previousValue = this.textvalue;
				}
				if ( newSnakTypeSelection === 'value' ) {
					this.textvalue = this.previousValue;
				}
				this.snaktype = newSnakTypeSelection;
			}
		},
		snakTypeSelectionMessage() {
			if ( this.snakTypeSelection === 'value' ) {
				return mw.msg( 'wikibase-snakview-snaktypeselector-value' );
			}
			const messageKey = 'wikibase-snakview-variations-' + this.snakTypeSelection + '-label';
			// messages that can appear here:
			// * wikibase-snakview-variations-novalue-label
			// * wikibase-snakview-variations-somevalue-label
			return mw.msg( messageKey );
		}
	},
	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

div.wikibase-wbui2025-edit-statement-snak-value {
	justify-content: center;
	width: 100%;
	display: flex;

	div.wikibase-wbui2025-snak-value {
		width: 100%;
	}

	div.wikibase-wbui2025-novalue-somevalue-holder {
		width: 100%;
		display: flex;
		align-items: center;

		p {
			font-family: 'Inter', sans-serif;
			font-weight: 500;
			font-size: 1.125rem;
			line-height: 1.25;
			color: @color-placeholder;
			padding: 0;
			margin: 0;
			align-items: center;
			gap: @spacing-25;
		}
	}

	.wikibase-snaktypeselector {
		position: relative;
		padding: 0;
		display: inline-block;
	}
}
</style>
