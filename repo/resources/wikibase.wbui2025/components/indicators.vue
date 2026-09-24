<template>
	<div v-if="indicatorsHtml">
		<!-- Jest (`indicators.spec.js`) doesn't like setting the anchor to be a component,
					for some reason, so we set the anchor here to a 'div' containing the button
					component -->
		<div ref="indicatorAnchor">
			<cdx-toggle-button
				:id="indicatorKey"
				ref="indicatorAnchor"
				v-model="popoverVisible"
				size="small"
				aria-haspopup="dialog"
				aria-owns="wbui2025-inidicator-popover"
				quiet
				class="wikibase-wbui2025-indicator-popover-toggle"
			>
				<span
					class="indicators wikibase-wbui2025-indicators"
					v-html="indicatorsHtml"
				></span>
			</cdx-toggle-button>
		</div>
		<wbui2025-indicator-popover
			v-if="popoverVisible"
			id="wbui2025-inidicator-popover"
			:aria-describedby="indicatorKey"
			role="dialog"
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
const { CdxToggleButton } = require( '../../../codex.js' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025IndicatorPopover = require( './indicatorPopover.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025Indicators',
	components: {
		CdxToggleButton,
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
		indicatorKey() {
			return wbui2025.store.getIndicatorKey( this.statementId, this.referenceHash, this.snakHash, this.isQualifier );
		},
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

.wikibase-wbui2025-indicator-popover-toggle{
	margin-left: @spacing-50;
	margin-right: calc( @spacing-75 - 3px );
	padding: 0 3px;

	&.cdx-toggle-button--quiet:enabled.cdx-toggle-button--toggled-on {
		background-color: @background-color-interactive-subtle--active;
	}

	.wikibase-wbui2025-indicators {
		display: inline-block;

		span {
			height: @spacing-150;
		}

		.wikibase-wbui2025-indicator-icon--error {
			.cdx-mixin-css-icon( @cdx-icon-error, @color-icon-error );
			padding: 3px 0 3px 0;
		}
	}
}
</style>
