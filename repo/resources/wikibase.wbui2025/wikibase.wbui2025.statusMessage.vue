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
const { defineComponent, ref } = require( 'vue' );
const { CdxMessage } = require( '../../codex.js' );
const { useMessageStore } = require( './store/messageStore.js' );

let messageStore;

const containerRef = ref( null );
const fixedWidth = ref( 0 );

/*
 * The message container has a fixed position so that it floats at the bottom of
 * the screen. This puts it outside of the layout so it is unable to inherit the
 * width of the parent without some Javascript support.
 */
const updateWidth = () => {
	if ( containerRef.value ) {
		fixedWidth.value = containerRef.value.offsetWidth;
	}
};

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025StatusMessage',
	components: {
		CdxMessage
	},
	setup() {
		messageStore = useMessageStore();
		return {
			containerRef,
			fixedWidth
		};
	},
	computed: {
		messageList() {
			return messageStore.messages;
		}
	},
	methods: {
		deleteMessage( messageId ) {
			messageStore.clearStatusMessage( messageId );
		}
	},
	mounted() {
		updateWidth();
		window.addEventListener( 'resize', updateWidth );
	},
	unmounted() {
		window.removeEventListener( 'resize', updateWidth );
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-status-message-container {
	position: fixed;
	bottom: 0;
}
</style>
