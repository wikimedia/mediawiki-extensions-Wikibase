import { App } from 'vue';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default function RepoRouterPlugin( app: App, repoRouter: MediaWikiRouter ): void {
	app.config.globalProperties.$repoRouter = repoRouter;
}
