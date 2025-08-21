<template>
	<div class="wikibase-wbui2025-add-statement-button">
		<div v-if="propertySelectorVisible">
			A property selector
		</div>
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

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementButton',
	components: {
		CdxButton,
		CdxIcon
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
