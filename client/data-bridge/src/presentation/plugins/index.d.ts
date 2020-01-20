import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import Vue from 'vue';

declare module 'vue/types/vue' {

	interface Vue {
		$messages: Messages;
		$bridgeConfig: BridgeConfig;
		$repoRouter: MediaWikiRouter;
		$clientRouter: MediaWikiRouter;
	}
}
