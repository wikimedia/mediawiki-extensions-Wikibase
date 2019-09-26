import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import Vue from 'vue';

declare module 'vue/types/vue' {

	interface Vue {
		$messages: Messages;
		$bridgeConfig: BridgeConfig;
	}
}
