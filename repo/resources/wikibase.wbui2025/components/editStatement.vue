<template>
	<template v-if="showAddQualifierModal">
		<wbui2025-add-qualifier
			:statement-id="statementId"
			@hide="showAddQualifierModal = false"
			@qualifier-added="qualifierAdded"
		>
		</wbui2025-add-qualifier>
	</template>
	<template v-if="showAddReferenceModal">
		<wbui2025-add-reference
			:statement-id="statementId"
			@hide="showAddReferenceModal = false"
			@reference-added="referenceAdded"
		>
		</wbui2025-add-reference>
	</template>
	<div class="wikibase-wbui2025-edit-statement-value-form">
		<div class="wikibase-wbui2025-value-input-fields">
			<div
				v-if="mainSnakKey"
				class="wikibase-wbui2025-edit-statement-value-input"
				:data-snak-key="mainSnakKey"
			>
				<wbui2025-editable-snak-value
					:snak-key="mainSnakKey"
				></wbui2025-editable-snak-value>
			</div>
			<div class="wikibase-wbui2025-rank-input">
				<cdx-select
					v-model:selected="rank"
					:menu-items="rankMenuItems"
				></cdx-select>
			</div>
		</div>
		<div class="wikibase-wbui2025-qualifiers-and-references">
			<wbui2025-editable-qualifiers
				:qualifiers="qualifiers"
				:qualifiers-order="qualifiersOrder"
				@remove-snak-from-property="removeQualifierSnakFromProperty"
			>
			</wbui2025-editable-qualifiers>
			<div class="wikibase-wbui2025-button-holder">
				<cdx-button
					action="progressive"
					class="wikibase-wbui2025-add-qualifier-button"
					@click="showAddQualifierModal = true"
				>
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					{{ $i18n( 'wikibase-addqualifier' ) }}
				</cdx-button>
			</div>
			<wbui2025-editable-references-section
				:references="references"
				@add-reference-snak="addReferenceSnak"
				@remove-reference="removeReference"
				@remove-reference-snak="removeReferenceSnak"
				@remove-new-reference-snak="removeNewReferenceSnak"
			></wbui2025-editable-references-section>
			<div class="wikibase-wbui2025-button-holder">
				<cdx-button
					action="progressive"
					class="wikibase-wbui2025-add-reference-button"
					@click="showAddReferenceModal = true">
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					{{ $i18n( 'wikibase-addreference' ) }}
				</cdx-button>
			</div>
		</div>
		<div v-if="!hideRemoveButton" class="wikibase-wbui2025-remove-value">
			<cdx-button weight="quiet" @click="$emit( 'remove', statementId )">
				<cdx-icon :icon="cdxIconTrash"></cdx-icon>
				{{ $i18n( 'wikibase-remove' ) }}
			</cdx-button>
		</div>
	</div>
</template>

<script>
const { defineComponent, computed } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const { CdxButton, CdxIcon, CdxSelect } = require( '../../../codex.js' );
const {
	cdxIconAdd,
	cdxIconTrash
} = require( '../icons.json' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025EditableReferencesSection = require( './editableReferencesSection.vue' );
const Wbui2025EditableQualifiers = require( './editableQualifiers.vue' );
const Wbui2025EditableSnakValue = require( './editableSnakValue.vue' );
const Wbui2025AddQualifier = require( './addQualifier.vue' );
const Wbui2025AddReference = require( './addReference.vue' );

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
		Wbui2025AddQualifier,
		Wbui2025EditableReferencesSection,
		Wbui2025EditableQualifiers,
		Wbui2025EditableSnakValue,
		Wbui2025AddReference
	},
	props: {
		statementId: {
			type: String,
			required: true
		},
		hideRemoveButton: {
			type: Boolean,
			required: false,
			default: false
		}
	},
	emits: [ 'remove' ],
	setup( props ) {
		/*
		 * Usually we use the Options API to map state and actions. In this case, we need a parameterised
		 * store - we pass in the statementId to make a statement-specific store. This forces us to use
		 * the Composition API to initialise the component.
		 */
		const editStatementStore = wbui2025.store.useEditStatementStore( props.statementId );
		const computedStatementProperties = mapWritableState( editStatementStore, [
			'mainSnakKey',
			'qualifiers',
			'qualifiersOrder',
			'rank',
			'references'
		] );
		return {
			mainSnakKey: computed( computedStatementProperties.mainSnakKey ),
			qualifiers: computed( computedStatementProperties.qualifiers ),
			qualifiersOrder: computed( computedStatementProperties.qualifiersOrder ),
			rank: computed( computedStatementProperties.rank ),
			references: computed( computedStatementProperties.references )
		};
	},
	data() {
		return {
			cdxIconAdd,
			cdxIconTrash,
			rankMenuItems: [
				{ label: mw.msg( 'wikibase-statementview-rank-normal' ), value: 'normal', icon: rankSelectorNormalIcon },
				{ label: mw.msg( 'wikibase-statementview-rank-preferred' ), value: 'preferred', icon: rankSelectorPreferredIcon },
				{ label: mw.msg( 'wikibase-statementview-rank-deprecated' ), value: 'deprecated', icon: rankSelectorDeprecatedIcon }
			],
			showAddQualifierModal: false,
			showAddReferenceModal: false
		};
	},
	methods: {
		qualifierAdded() {
			this.showAddQualifierModal = false;
		},
		removeQualifierSnakFromProperty( propertyId, snakKey ) {
			this.qualifiers[ propertyId ].splice( this.qualifiers[ propertyId ].indexOf( snakKey ), 1 );
			if ( this.qualifiers[ propertyId ].length === 0 ) {
				delete this.qualifiers[ propertyId ];
				this.qualifiersOrder.splice( this.qualifiersOrder.indexOf( propertyId ), 1 );
			}
		},
		referenceAdded() {
			this.showAddReferenceModal = false;
		},
		addReferenceSnak( reference, snakKey ) {
			if ( !reference.newSnaks ) {
				reference.newSnaks = [];
			}
			reference.newSnaks.push( snakKey );
		},
		removeReference( reference ) {
			this.references.splice( this.references.indexOf( reference ), 1 );
		},
		removeReferenceSnak( reference, propertyId, snakKey ) {
			reference.snaks[ propertyId ].splice( reference.snaks[ propertyId ].indexOf( snakKey ), 1 );
			if ( reference.snaks[ propertyId ].length === 0 ) {
				delete reference.snaks[ propertyId ];
				reference[ 'snaks-order' ].splice( reference[ 'snaks-order' ].indexOf( propertyId ), 1 );
				if ( reference[ 'snaks-order' ].length === 0 && ( !reference.newSnaks || reference.newSnaks.length === 0 ) ) {
					this.removeReference( reference );
				}
			}
		},
		removeNewReferenceSnak( reference, snakKey ) {
			if ( !reference.newSnaks || reference.newSnaks.length === 0 ) {
				return;
			}
			reference.newSnaks.splice( reference.newSnaks.indexOf( snakKey ), 1 );
			if ( reference.newSnaks.length === 0 ) {
				delete reference.newSnaks;
				if ( reference[ 'snaks-order' ].length === 0 ) {
					this.removeReference( reference );
				}
			}
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

		> * {
			align-self: stretch;
		}

		div.wikibase-wbui2025-button-holder {

			& > button.cdx-button {
				width: 100%;
				justify-content: flex-start;
			}
		}
	}
}
</style>
