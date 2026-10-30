<template>
	<cdx-dialog
		class="wikibase-wbui2025-copyright-dialog"
		:title="$i18n( 'wikibase-copyrightdialog-heading' ).text()"
		:open="open"
		:primary-action="primaryAction"
		:default-action="defaultAction"
		:use-default-action="true"
		:use-close-button="false"
		@primary="onConfirm"
		@default="$emit( 'hide' )"
	>
		<div v-html="dialogBodyHtml"></div>
		<cdx-checkbox v-model="persistAcceptance">
			{{ $i18n( 'wikibase-copyrighttooltip-acknowledge' ) }}
		</cdx-checkbox>
	</cdx-dialog>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxDialog, CdxCheckbox } = require( '../../../codex.js' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025CopyrightDialog',
	components: {
		CdxDialog,
		CdxCheckbox
	},
	props: {
		open: {
			type: Boolean,
			required: true
		}
	},
	emits: [ 'confirm', 'hide' ],
	data() {
		return {
			persistAcceptance: false,
			dialogBodyHtml: wbui2025.copyright.messageHtml
		};
	},
	computed: {
		primaryAction() {
			return {
				label: mw.config.get( 'wgEditSubmitButtonLabelPublish' )
					? mw.msg( 'wikibase-copyrightdialog-confirm-publish' )
					: mw.msg( 'wikibase-copyrightdialog-confirm-save' ),
				actionType: 'progressive'
			};
		},
		defaultAction() {
			return { label: mw.msg( 'wikibase-cancel' ) };
		}
	},
	methods: {
		onConfirm() {
			if ( this.persistAcceptance ) {
				wbui2025.copyright.persistAcceptance();
			}
			this.$emit( 'confirm' );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-copyright-dialog {

	// Codex's default behavior is to always show the header's close button on
	// narrow screens, even when useCloseButton is false. Override that here.
	.cdx-dialog__header--no-close-button .cdx-dialog__header__close-button {
		display: none;
	}

	.cdx-dialog__body {
		display: flex;
		flex-direction: column;
		gap: @spacing-150;
	}
}

</style>
