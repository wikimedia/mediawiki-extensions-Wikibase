import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
// eslint-disable-next-line @typescript-eslint/no-unused-vars
import Vue from 'vue';

declare module 'vue/types/vue' {

	interface Vue {
		$messages: Messages;
		$repoRouter: MediaWikiRouter;
		$clientRouter: MediaWikiRouter;
		$inLanguage: ( langCode: string ) => { lang: string; dir: string; }
	}
}
