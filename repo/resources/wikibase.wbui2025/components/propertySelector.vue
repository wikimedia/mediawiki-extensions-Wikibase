<template>
	<div class="wikibase-wbui2025-property-selector">
		<div class="wikibase-wbui2025-property-selector-heading">
			<h3>{{ headingMessage }}</h3>
			<div class="wikibase-wbui2025-property-selector-heading-buttons">
				<cdx-button
					weight="quiet"
					@click="$emit( 'cancel' )"
				>
					<cdx-icon :icon="cdxIconClose"></cdx-icon>
					{{ $i18n( 'wikibase-cancel' ) }}
				</cdx-button>
				<cdx-button
					action="progressive"
					weight="primary"
					:disabled="addButtonDisabled"
					@click="$emit( 'add', selection )"
				>
					<cdx-icon :icon="cdxIconCheck"></cdx-icon>
					{{ $i18n( 'wikibase-add' ) }}
				</cdx-button>
			</div>
		</div>
		<wikibase-wbui2025-property-lookup
			@update:selected="onPropertySelection"
		>
		</wikibase-wbui2025-property-lookup>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconCheck, cdxIconClose } = require( '../icons.json' );
const WikibaseWbui2025PropertyLookup = require( './propertyLookup.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025PropertySelector',
	components: {
		CdxButton,
		CdxIcon,
		WikibaseWbui2025PropertyLookup
	},
	props: {
		headingMessageKey: {
			type: String,
			required: true
		}
	},
	emits: [ 'add', 'cancel' ],
	data() {
		return {
			cdxIconCheck,
			cdxIconClose,
			selection: null
		};
	},
	computed: {
		headingMessage() {
			// messages that can be used here:
			// * wikibase-statementgrouplistview-add
			// * anything else the parent component passes in
			return mw.msg( this.headingMessageKey );
		},
		addButtonDisabled() {
			return this.selection === null;
		}
	},
	methods: {
		onPropertySelection( propertyId ) {
			this.selection = propertyId;
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-property-selector {
	.wikibase-wbui2025-property-selector-heading {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
}
</style>
