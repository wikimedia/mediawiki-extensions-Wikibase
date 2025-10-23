<template>
	<div id="wikibase-wbui2025-status-messages" ref="containerRef">
		<div
			class="wikibase-wbui2025-status-message-container"
			:style="{ width: fixedWidth + 'px' }"
		>
			<template
				v-for="[ messageId, message ] in messageList"
				:key="message"
			>
				<cdx-message
					:type="message.type || 'success'"
					allow-user-dismiss
					:auto-dismiss="message.type !== 'error'"
					:display-time="4000"
					@user-dismiss="deleteMessage( messageId )"
					@auto-dismiss="deleteMessage( messageId )"
				>
					{{ message.text }}
				</cdx-message>
			</template>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxMessage } = require( '../../codex.js' );
const { useMessageStore } = require( './store/messageStore.js' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatusMessage',
	components: {
		CdxMessage
	},
	data() {
		return {
			fixedWidth: 0
		};
	},
	computed: {
		messageList() {
			return useMessageStore().messages;
		}
	},
	methods: {
		deleteMessage( messageId ) {
			useMessageStore().clearStatusMessage( messageId );
		},
		/*
		 * The message container has a fixed position so that it floats at the bottom of
		 * the screen. This puts it outside of the layout so it is unable to inherit the
		 * width of the parent without some Javascript support.
		 */
		updateWidth() {
			if ( this.$refs.containerRef ) {
				this.fixedWidth = this.$refs.containerRef.offsetWidth;
			}
		}
	},
	mounted() {
		this.updateWidth();
		window.addEventListener( 'resize', this.updateWidth );
	},
	unmounted() {
		window.removeEventListener( 'resize', this.updateWidth );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-status-message-container {
	position: fixed;
	bottom: 0;
	z-index: 1;

	& .cdx-message--user-dismissable {
		padding: 10px 10px 28px;
	}
}
</style>
