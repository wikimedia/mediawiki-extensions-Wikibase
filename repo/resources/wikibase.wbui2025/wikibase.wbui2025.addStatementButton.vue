<template>
	<div class="wikibase-wbui2025-add-statement-button">
		<wbui2025-property-selector
			v-if="propertySelectorVisible"
			heading-message-key="wikibase-statementgrouplistview-add"
			@add="propertySelectorVisible = false"
			@cancel="propertySelectorVisible = false"
		>
		</wbui2025-property-selector>
		<cdx-button
			@click="propertySelectorVisible = true"
		>
			<cdx-icon :icon="cdxIconAdd"></cdx-icon>
			{{ $i18n( 'wikibase-add' ) }}
		</cdx-button>
	</div>
</template>

<script>
const { defineComponent, ref } = require( 'vue' );
const { CdxButton, CdxIcon } = require( '../../codex.js' );
const { cdxIconAdd } = require( './icons.json' );
const Wbui2025PropertySelector = require( './wikibase.wbui2025.propertySelector.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementButton',
	components: {
		CdxButton,
		CdxIcon,
		Wbui2025PropertySelector
	},
	props: {},
	setup( props ) {
		const propertySelectorVisible = ref( false );

		return {
			cdxIconAdd,
			propertySelectorVisible
		};
	}
} );
</script>

<style>
.wikibase-wbui2025-add-statement-button {
	margin-top: 1em;
}
</style>
