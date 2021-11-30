import { App } from 'vue';
import InLanguagePlugin from './plugins/InLanguagePlugin';
import MessagesPlugin from './plugins/MessagesPlugin';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import RepoRouterPlugin from './plugins/RepoRouterPlugin';
import ClientRouterPlugin from '@/presentation/plugins/ClientRouterPlugin';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default function extendVueEnvironment(
	app: App,
	languageInfoRepo: LanguageInfoRepository,
	messageRepo: MessagesRepository,
	repoRouter: MediaWikiRouter,
	clientRouter: MediaWikiRouter,
): void {
	app.use( InLanguagePlugin, languageInfoRepo );
	app.use( MessagesPlugin, messageRepo );
	app.use( RepoRouterPlugin, repoRouter );
	app.use( ClientRouterPlugin, clientRouter );
}
