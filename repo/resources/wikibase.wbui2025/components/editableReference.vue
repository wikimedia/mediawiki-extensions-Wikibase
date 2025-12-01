<template>
	<div class="wikibase-wbui2025-editable-reference">
		<div class="wikibase-wbui2025-editable-reference-snaks">
			<div class="wikibase-wbui2025-editable-reference-snak-list">
				<template v-for="propertyId in reference[ 'snaks-order' ]" :key="propertyId">
					<template v-if="typeof reference.snaks === 'object' && Array.isArray( reference.snaks[ propertyId ] )">
						<div
							v-for="snakKey in reference.snaks[ propertyId ]"
							:key="snakKey"
							class="wikibase-wbui2025-editable-reference-snak"
						>
							<wbui2025-editable-snak
								:snak-key="snakKey"
								:property-id="propertyId"
								@remove-snak-from-property="removeReferenceSnak"
							></wbui2025-editable-snak>
						</div>
					</template>
				</template>
				<template v-if="Array.isArray( reference.newSnaks )">
					<div
						v-for="snakKey in reference.newSnaks"
						:key="snakKey"
						class="wikibase-wbui2025-editable-reference-snak"
					>
						<wbui2025-new-reference-snak
							:snak-key="snakKey"
							@remove-snak="removeNewReferenceSnak"
						></wbui2025-new-reference-snak>
					</div>
				</template>
			</div>
			<div>
				<cdx-button
					class="wikibase-wbui2025-add-snak-button"
					action="progressive"
					weight="quiet"
					@click="addNewSnak"
				>
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
const { cdxIconAdd, cdxIconTrash } = require( '../icons.json' );
const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const Wbui2025EditableSnak = require( './editableSnak.vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025NewReferenceSnak = require( './newReferenceSnak.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableReference',
	components: {
		CdxButton,
		CdxIcon,
		Wbui2025EditableSnak,
		Wbui2025NewReferenceSnak
	},
	props: {
		reference: {
			type: Object,
			required: true
		}
	},
	emits: [ 'add-reference-snak', 'remove-reference', 'remove-reference-snak', 'remove-new-reference-snak' ],
	data() {
		return {
			cdxIconAdd,
			cdxIconTrash
		};
	},
	methods: {
		removeReferenceSnak( propertyId, snakKey ) {
			this.$emit( 'remove-reference-snak', this.reference, propertyId, snakKey );
		},
		removeNewReferenceSnak( snakKey ) {
			this.$emit( 'remove-new-reference-snak', this.reference, snakKey );
		},
		removeReference() {
			this.$emit( 'remove-reference', this.reference );
		},
		addNewSnak() {
			const snakKey = wbui2025.store.generateNextSnakKey();
			this.$emit( 'add-reference-snak', this.reference, snakKey );
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

.wikibase-wbui2025-editable-reference-snak {
	display: flex;
	flex-direction: column;
	gap: @spacing-35;
}

.wikibase-wbui2025-editable-reference-remove-button-holder {
	display: flex;
	justify-content: flex-end;
}
</style>
