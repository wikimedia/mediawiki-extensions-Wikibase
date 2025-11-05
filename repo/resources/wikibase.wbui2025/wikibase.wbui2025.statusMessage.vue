<template>
	<teleport to="#mw-teleport-target">
		<div id="wikibase-wbui2025-status-messages">
			<div
				v-if="messageList !== null && messageList.size > 0"
				class="wikibase-wbui2025-status-message-container"
				:style="{ bottom: bottom + 'px' }"
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
						@user-dismissed="deleteMessage( messageId )"
						@auto-dismissed="deleteMessage( messageId )"
					>
						{{ message.text }}
					</cdx-message>
				</template>
			</div>
		</div>
	</teleport>
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
			attachTo: null,
			bottom: 0
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
		}
	},
	watch: {
		messageList: {
			handler( messages ) {
				for ( const message of messages.values() ) {
					if ( message.attachTo ) {
						this.attachTo = message.attachTo;
					}
				}
			},
			immediate: true,
			deep: true
		},
		attachTo: {
			handler( newAttachTo ) {
				if ( newAttachTo ) {
					this.bottom = newAttachTo.offsetHeight;
				} else {
					this.bottom = 0;
				}
			}
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

#wikibase-wbui2025-status-messages {
	width: 100vw;
}

.wikibase-wbui2025-status-message-container {
	width: 100vw;
	position: fixed;
	bottom: @spacing-0;
	z-index: 999;
	display: flex;
	flex-direction: column;
	justify-content: center;
	left: 0;
	padding: @spacing-125 @spacing-100;
	box-sizing: border-box;
}
</style>
