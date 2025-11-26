<template>
	<teleport to="#mw-teleport-target">
		<transition name="wikibase-wbui2025-modal-slide-in" appear>
			<div
				class="wikibase-wbui2025-modal-overlay"
				:class="{ 'wikibase-wbui2025-modal-overlay--minimal': minimalStyle }">
				<slot>
					<slot name="header">
						<div class="wikibase-wbui2025-modal-overlay__header">
							<cdx-button
								v-if="!minimalStyle"
								weight="quiet"
								:aria-label="$i18n( 'wikibase-cancel' )"
								@click="$emit( 'hide' )">
								<cdx-icon :icon="cdxIconArrowPrevious"></cdx-icon>
							</cdx-button>
							<div v-if="minimalStyle" class="wikibase-wbui2025-modal-overlay__header__close-button">
								<cdx-button
									:aria-label="$i18n( 'wikibase-cancel' )"
									weight="quiet"
									@click="$emit( 'hide' )"
								>
									<cdx-icon :icon="cdxIconClose"></cdx-icon>
								</cdx-button>
							</div>
							<div class="wikibase-wbui2025-modal-overlay__header__title-group">
								<h2 class="wikibase-wbui2025-modal-overlay__header__title">
									{{ header }}
								</h2>
								<div
									v-if="subtitleHtml"
									class="wikibase-wbui2025-modal-overlay__header__subtitle"
									v-html="subtitleHtml"></div>
							</div>
						</div>
					</slot>
					<div class="wikibase-wbui2025-modal-overlay__content">
						<slot name="content">
						</slot>
					</div>
					<slot name="footer">
						<div v-if="!hideFooter" class="wikibase-wbui2025-modal-overlay__footer">
							<transition name="fade">
								<cdx-progress-bar
									v-if="showProgress"
									:value="100"
									inline
									:aria-label="progressBarLabel"
								></cdx-progress-bar>
							</transition>
							<!-- eslint-disable vue/no-unused-refs -->
							<div ref="modalOverlayActionsRef" class="wikibase-wbui2025-modal-overlay__footer__actions">
								<cdx-button
									weight="quiet"
									@click="$emit( 'hide' )"
								>
									<cdx-icon :icon="cdxIconClose"></cdx-icon>
									{{ $i18n( 'wikibase-cancel' ) }}
								</cdx-button>
								<cdx-button
									action="progressive"
									weight="primary"
									:disabled="saveButtonDisabled"
									@click="$emit( 'save' )"
								>
									<cdx-icon :icon="cdxIconCheck"></cdx-icon>
									{{ saveMessage }}
								</cdx-button>
							</div>
						</div>
					</slot>
				</slot>
			</div>
		</transition>
	</teleport>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { cdxIconArrowPrevious, cdxIconCheck, cdxIconClose } = require( '../icons.json' );
const { CdxButton, CdxIcon, CdxProgressBar } = require( '../../../codex.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025ModalOverlay',
	components: {
		CdxButton,
		CdxIcon,
		CdxProgressBar
	},
	props: {
		header: {
			type: [ String, Object ],
			required: false,
			default: ''
		},
		subtitleHtml: {
			type: String,
			required: false,
			default: ''
		},
		hideFooter: {
			type: Boolean,
			required: false,
			default: false
		},
		minimalStyle: {
			type: Boolean,
			required: false,
			default: false
		},
		saveButtonDisabled: {
			type: Boolean,
			required: false,
			default: false
		},
		showProgress: {
			type: Boolean,
			required: false,
			default: false
		},
		progressBarLabel: {
			type: [ String, Object ],
			required: false,
			default: ''
		}
	},
	emits: [ 'hide', 'save' ],
	data() {
		return {
			cdxIconArrowPrevious,
			cdxIconCheck,
			cdxIconClose
		};
	},
	computed: {
		saveMessage() {
			return mw.config.get( 'wgEditSubmitButtonLabelPublish' )
				? mw.msg( 'wikibase-publish' )
				: mw.msg( 'wikibase-save' );
		}
	},
	mounted() {
		document.body.classList.add( 'wikibase-wbui2025-modal-open' );
	},
	unmounted() {
		const target = document.getElementById( 'mw-teleport-target' );
		if ( target && target.querySelectorAll( '.wikibase-wbui2025-modal-overlay' ).length === 0 ) {
			document.body.classList.remove( 'wikibase-wbui2025-modal-open' );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-modal-overlay {
	position: fixed;
	background-color: @background-color-base;
	top: 0;
	left: 0;
	min-height: @size-full;
	display: flex;
	flex-direction: column;
	z-index: @z-index-overlay-backdrop;
	width: @size-viewport-width-full;
	height: 100%;

	.sd{
		display: flex;
		width: 375px;
		min-width: 375px;
		padding: @spacing-100 @spacing-100 @spacing-200 @spacing-100;
		flex-direction: column;
		align-items: flex-end;
		gap: 12px;
	}

	.wikibase-wbui2025-modal-overlay__header {
		position: relative;
		display: flex;
		padding: @spacing-250 @spacing-75 @spacing-150 @spacing-75;
		align-items: center;
		gap: @spacing-25;
		align-self: stretch;
		box-shadow: 0 2px 11.8px 0 rgba(0, 0, 0, 0.10);
		justify-content: center;

		.cdx-button{
			position: absolute;
			left: @spacing-75;
		}

		&__title-group {
			display: flex;
			flex-direction: column;
			gap: @spacing-25;
		}

		&__title{
			margin: 0;
			font-weight: 700;
			font-size: 1.25rem;
			line-height: 1.5625rem;
			padding: 0;
			color: @color-emphasized;
			font-family: @font-family-system-sans;
		}

		&__subtitle {
			font-weight: 700;
			color: @color-progressive--focus;
			font-size: 1rem;
			line-height: 1.6rem;
			text-align: center;
		}
	}

	.wikibase-wbui2025-modal-overlay__content {
		display: flex;
		flex-grow: 1;
		flex-direction: column;
		overflow-y: auto;
	}

	.wikibase-wbui2025-modal-overlay__footer {
		display: flex;
		flex: 0 0 auto;
		flex-direction: column;
		box-shadow: 0 2px 11.8px 0 rgba(0, 0, 0, 0.10);

		.wikibase-wbui2025-modal-overlay__footer__actions {
			padding: @spacing-125 @spacing-400 @spacing-300 @spacing-400;
			align-items: center;
			justify-content: center;
			display: flex;
			gap: @spacing-200;
		}
	}
}

.wikibase-wbui2025-modal-overlay--minimal {
	.wikibase-wbui2025-modal-overlay__header {
		align-self: stretch;
		padding: @spacing-100 @spacing-100 @spacing-200;
		border-bottom: @border-width-base @border-style-base @border-color-subtle;
		flex-direction: column;
		box-shadow: unset;

		h2 {
			padding: @spacing-0;
			font-family: @font-family-base;
			text-align: center;
			font-weight: 400;
			font-size: @font-size-xx-large;
			line-height: @line-height-small;
		}

		&__close-button {
			width: 100%;
			text-align: right;
			.cdx-button {
				position: relative;
				left: unset;
			}
		}
	}
}

.wikibase-wbui2025-modal-slide-in-enter-active {
	transition-duration: @transition-duration-base;
	transition-timing-function: @transition-timing-function-user;
}

.wikibase-wbui2025-modal-slide-in-enter-from {
	left: 100%;
}

body.wikibase-wbui2025-modal-open {
	overflow: hidden;
}
</style>
