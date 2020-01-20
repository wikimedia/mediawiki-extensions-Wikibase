import Vue from 'vue';
import inlanguage from './directives/inlanguage';
import MessagesPlugin from './plugins/MessagesPlugin';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin';
import RepoRouterPlugin from './plugins/RepoRouterPlugin';
import ClientRouterPlugin from '@/presentation/plugins/ClientRouterPlugin';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default function extendVueEnvironment(
	languageInfoRepo: LanguageInfoRepository,
	messageRepo: MessagesRepository,
	bridgeConfigOptions: WikibaseClientConfiguration,
	repoRouter: MediaWikiRouter,
	clientRouter: MediaWikiRouter,
): void {
	Vue.directive( 'inlanguage', inlanguage( languageInfoRepo ) );
	Vue.use( MessagesPlugin, messageRepo );
	Vue.use( BridgeConfig, bridgeConfigOptions );
	Vue.use( RepoRouterPlugin, repoRouter );
	Vue.use( ClientRouterPlugin, clientRouter );
}
