<template>
	<cdx-popover
		v-model:open="showPopover"
		class="wikibase-wbui2025-indicator-popover"
		:class="[
			{ 'wikibase-wbui2025-indicator-popover--multiple-issues': multipleIssues }
		]"
		:render-in-place="true"
		placement="bottom-end"
		@update:open="$emit( 'close' )"
	>
		<template #header>
			<div class="wikibase-wbui2025-indicator-popover-header-row">
				<cdx-icon
					v-if="titleIconClass"
					class="cdx-popover__header__icon"
					:class="titleIconClass"
					icon="none"
				></cdx-icon>
				<div class="cdx-popover__header__title">
					{{ title }}
				</div>
				<div class="cdx-popover__header__button-wrapper">
					<cdx-button
						class="cdx-popover__header__close-button"
						weight="quiet"
						type="button"
						:aria-label="$i18n( 'cdx-popover-close-button-label' )"
						@click="$emit( 'close' )"
					>
						<cdx-icon :icon="cdxIconClose"></cdx-icon>
					</cdx-button>
				</div>
			</div>
			<template v-if="multipleIssues">
				<wbui2025-stepper
					v-if="multipleIssues"
					:current-step="currentIndex + 1"
					:total-steps="totalSteps"
				></wbui2025-stepper>
				<cdx-icon
					v-if="currentIssue.iconClass"
					:class="currentIssue.iconClass"
					icon="none"
				></cdx-icon>
				<div
					v-if="currentIssue.title"
					class="cdx-popover__header__title"
				>
					{{ currentIssue.title }}
				</div>
			</template>
		</template>
		<div v-html="bodyContent"></div>
		<template v-if="showFooter" #footer>
			<div v-html="footerContent"></div>
			<div v-if="multipleIssues" class="wikibase-wbui2025-indicator-popover-multistep-navigation">
				<cdx-button
					:disabled="currentIndex === 0"
					:aria-label="$i18n( 'wikibase-indicator-popover-multiple-issue-previous' )"
					@click="currentIndex = currentIndex - 1"
				>
					<cdx-icon :icon="cdxIconPrevious"></cdx-icon>
				</cdx-button>
				<cdx-button
					action="progressive"
					weight="primary"
					:disabled="currentIndex + 1 === totalSteps"
					:aria-label="$i18n( 'wikibase-indicator-popover-multiple-issue-next' )"
					@click="currentIndex = currentIndex + 1"
				>
					<cdx-icon :icon="cdxIconNext"></cdx-icon>
				</cdx-button>
			</div>
		</template>
	</cdx-popover>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025Stepper = require( './stepper.vue' );
const { CdxPopover, CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconClose, cdxIconPrevious, cdxIconNext } = require( '../icons.json' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025IndicatorPopover',
	components: {
		CdxPopover,
		CdxButton,
		CdxIcon,
		Wbui2025Stepper
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
	emits: [ 'close' ],
	data() {
		return {
			cdxIconClose,
			cdxIconPrevious,
			cdxIconNext,
			currentIndex: 0,
			showPopover: true
		};
	},
	computed: {
		totalSteps() {
			return this.issues.length;
		},
		multipleIssues() {
			return this.issues.length > 1;
		},
		issues() {
			if ( this.referenceHash !== null ) {
				return wbui2025.store.getPopoverContentForReferenceSnak(
					this.statementId,
					this.referenceHash,
					this.snakHash
				);
			}
			if ( this.isQualifier ) {
				return wbui2025.store.getPopoverContentForQualifier(
					this.statementId,
					this.snakHash
				);
			}
			return wbui2025.store.getPopoverContentForMainSnak(
				this.statementId
			);
		},
		currentIssue() {
			return this.issues[ this.currentIndex ];
		},
		title() {
			return this.multipleIssues ?
				mw.msg( 'wikibase-indicator-popover-multiple-issue-title' ) :
				this.currentIssue.title;
		},
		titleIconClass() {
			return this.multipleIssues ? null : this.currentIssue.iconClass;
		},
		bodyContent() {
			return this.currentIssue.bodyHtml;
		},
		footerContent() {
			return this.currentIssue.footerHtml;
		},
		showFooter() {
			return this.multipleIssues || this.footerContent;
		}
	}
} );

</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-indicator-popover {
	max-height: 500px;

	header {
		margin-bottom: @spacing-75;
	}
}
.wikibase-wbui2025-indicator-popover-header-row {
	display: flex;
	gap: @spacing-50;
	width: 100%;
}

.wikibase-wbui2025-indicator-popover--multiple-issues {
	height: 500px;

	.cdx-popover__header {
		flex-wrap: wrap;
	}

	.wikibase-wbui2025-stepper {
		width: 100%;
		margin-bottom: @spacing-100;
	}

	.cdx-popover__footer {
		display: flex;
		flex-direction: column;
		gap: @spacing-100;
	}
}

.wikibase-wbui2025-indicator-popover-multistep-navigation {
	display: flex;
	justify-content: flex-end;
	gap: @spacing-75;
}
</style>
