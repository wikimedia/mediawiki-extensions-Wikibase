<template>
	<div v-if="indicatorsHtml">
		<span
			ref="indicatorAnchor"
			class="indicators wikibase-wbui2025-indicators"
			@click="popoverVisible = !popoverVisible"
			v-html="indicatorsHtml"
		></span>
		<wbui2025-indicator-popover
			v-if="popoverVisible"
			:snak-hash="snakHash"
			:statement-id="statementId"
			:is-qualifier="isQualifier"
			:reference-hash="referenceHash"
			:anchor="$refs.indicatorAnchor"
			@close="popoverVisible = false"
		>
		</wbui2025-indicator-popover>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025IndicatorPopover = require( './indicatorPopover.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025Indicators',
	components: {
		Wbui2025IndicatorPopover
	},
	props: {
		snakHash: {
			type: String,
			required: true
		},
		statementId: {
			type: String,
			required: true
		},
		isQualifier: {
			type: Boolean,
			default: false
		},
		referenceHash: {
			type: String,
			default: null
		}
	},
	data() {
		return {
			popoverVisible: false
		};
	},
	computed: {
		indicatorsHtml() {
			if ( this.referenceHash !== null ) {
				return wbui2025.store.getIndicatorHtmlForReferenceSnak(
					this.statementId,
					this.referenceHash,
					this.snakHash
				);
			}
			if ( this.isQualifier ) {
				return wbui2025.store.getIndicatorHtmlForQualifier(
					this.statementId,
					this.snakHash
				);
			}
			return wbui2025.store.getIndicatorHtmlForMainSnak(
				this.statementId
			);
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-indicators {
	display: inline-block;
	margin-left: @spacing-50;
	padding: 0 @spacing-75;
	cursor: pointer;

	.wikibase-wbui2025-indicator-icon--error {
		.cdx-mixin-css-icon( @cdx-icon-error, @color-icon-error );
		padding: 3px 0 3px 0;
	}
}
</style>
