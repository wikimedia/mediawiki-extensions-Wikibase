<template>
	<cdx-popover
		v-model:open="showPopover"
		:title="popoverTitle"
		:use-close-button="true"
		:use-primary-action="false"
		:use-default-action="false"
		:render-in-place="true"
		placement="bottom-end"
		@update:open="$emit( 'close' )"
	>
		<template #header>
			<span v-if="popoverTitleIcon" v-html="popoverTitleIcon"></span>
			<div class="cdx-popover__header__title">
				{{ popoverTitle }}
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
		</template>
		<div v-html="popoverContent()"></div>
	</cdx-popover>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const { CdxPopover, CdxButton, CdxIcon } = require( '../../../codex.js' );
const { cdxIconClose } = require( '../icons.json' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025IndicatorPopover',
	components: {
		CdxPopover,
		CdxButton,
		CdxIcon
	},
	props: {
		snakHash: {
			type: String,
			required: true
		}
	},
	emits: [ 'close' ],
	data() {
		return {
			cdxIconClose,
			showPopover: true
		};
	},
	computed: {
		storedContent() {
			return wbui2025.store.getPopoverContentForSnakHash( this.snakHash );
		},
		popoverTitle() {
			return this.storedContent.title;
		},
		popoverTitleIcon() {
			return this.storedContent.icon;
		}
	},
	methods: {
		popoverContent() {
			if ( this.storedContent ) {
				return this.storedContent.bodyHtml;
			}
			throw new Error( 'No popover content stored for snak ' + this.snakHash );
		}
	}
} );

</script>
