import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import { Store } from 'vuex';
import Application from '@/store/Application';

declare module '@vue/runtime-core' {
	interface ComponentCustomProperties {
		$clientRouter: MediaWikiRouter;
		$repoRouter: MediaWikiRouter;
		$messages: Messages,
		$store: Store<Application>;
		$inLanguage: ( langCode: string ) => { lang: string; dir: string; }
	}
}
