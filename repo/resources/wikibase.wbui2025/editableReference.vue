<template>
	<div class="wikibase-wbui2025-editable-reference">
		<div class="wikibase-wbui2025-editable-reference-snaks">
			<div class="wikibase-wbui2025-editable-reference-snak-list">
				<template v-for="propertyId in reference[ 'snaks-order' ]" :key="propertyId">
					<template v-if="typeof reference.snaks === 'object' && Array.isArray( reference.snaks[ propertyId ] )">
						<div
							v-for="snakKey in reference.snaks[ propertyId ]"
							:key="snakKey"
						>
							<wbui2025-editable-snak
								:snak-key="snakKey"
								:property-id="propertyId"
								@remove-snak-from-property="removeReferenceSnak"
							></wbui2025-editable-snak>
						</div>
					</template>
				</template>
			</div>
			<div class="wikibase-wbui-editable-reference-add-snak-button-holder">
				<cdx-button action="progressive" weight="quiet">
					<cdx-icon :icon="cdxIconAdd"></cdx-icon>
					{{ $i18n( 'wikibase-add' ) }}
				</cdx-button>
			</div>
		</div>
		<div class="wikibase-wbui2025-editable-reference-remove-button-holder">
			<cdx-button
				weight="quiet"
				@click="removeReference"
			>
				<cdx-icon :icon="cdxIconTrash"></cdx-icon>
				{{ $i18n( 'wikibase-remove' ) }}
			</cdx-button>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { cdxIconAdd, cdxIconTrash } = require( './icons.json' );
const { CdxButton, CdxIcon } = require( '../../codex.js' );
const Wbui2025EditableSnak = require( './editableSnak.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableReference',
	components: {
		CdxButton,
		CdxIcon,
		Wbui2025EditableSnak
	},
	props: {
		reference: {
			type: Object,
			required: true
		}
	},
	emits: [ 'remove-reference', 'remove-reference-snak' ],
	data() {
		return { cdxIconAdd, cdxIconTrash };
	},
	methods: {
		removeReferenceSnak( propertyId, snakKey ) {
			this.$emit( 'remove-reference-snak', this.reference, propertyId, snakKey );
		},
		removeReference() {
			this.$emit( 'remove-reference', this.reference );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-editable-reference {
	display: flex;
	flex-direction: column;
	gap: @spacing-35;
}

.wikibase-wbui2025-editable-reference-snaks {
	padding: @spacing-75;
	background-color: @background-color-interactive;
	display: flex;
	flex-direction: column;
	gap: @spacing-125;
}

.wikibase-wbui2025-editable-reference-snak-list {
	display: flex;
	flex-direction: column;
	gap: @spacing-100;
}

.wikibase-wbui2025-editable-reference-remove-button-holder {
	display: flex;
	justify-content: flex-end;
}
</style>
