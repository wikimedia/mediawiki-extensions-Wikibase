import Vue from 'vue';
import { VueConstructor } from 'vue/types/vue';
import inlanguage from '@/presentation/directives/inlanguage';
import MessagesPlugin from './plugins/MessagesPlugin';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import RepoRouterPlugin from './plugins/RepoRouterPlugin';
import ClientRouterPlugin from '@/presentation/plugins/ClientRouterPlugin';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default function extendVueEnvironment(
	app: ReturnType<VueConstructor['createMwApp']>,
	languageInfoRepo: LanguageInfoRepository,
	messageRepo: MessagesRepository,
	repoRouter: MediaWikiRouter,
	clientRouter: MediaWikiRouter,
): void {
	Vue.directive( 'inlanguage', inlanguage( languageInfoRepo ) );
	app.use( MessagesPlugin, messageRepo );
	app.use( RepoRouterPlugin, repoRouter );
	app.use( ClientRouterPlugin, clientRouter );
}
