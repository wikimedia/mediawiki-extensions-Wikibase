<template>
	<div class="wikibase-wbui2025-add-statement-button">
		<wbui2025-add-statement-modal
			v-if="addStatementModalVisible"
			:entity-id="entityId"
			:section-key="sectionKey"
			@hide="addStatementModalVisible = false">
		</wbui2025-add-statement-modal>
		<cdx-button
			action="progressive"
			weight="primary"
			size="large"
			@click="addStatementModalVisible = true"
		>
			<cdx-icon :icon="cdxIconAdd"></cdx-icon>
			{{ $i18n( 'wikibase-addstatement' ) }}
		</cdx-button>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconAdd } = require( '../icons.json' );
const Wbui2025AddStatementModal = require( './addStatementModal.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025AddStatementButton',
	components: {
		CdxButton,
		CdxIcon,
		Wbui2025AddStatementModal
	},
	props: {
		entityId: {
			type: String,
			required: true
		},
		sectionKey: {
			type: String,
			required: true
		}
	},
	data: () => ( {
		cdxIconAdd,
		addStatementModalVisible: false
	} )
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-add-statement-button {
	margin-top: 20px;
	margin-bottom: 32px;
}

.wikibase-wbui2025-add-statement-form {
	.wikibase-wbui2025-add-statement-form_property-selector {
		padding: @spacing-250 @spacing-100;
		background: @background-color-progressive-subtle;
	}
}
</style>
