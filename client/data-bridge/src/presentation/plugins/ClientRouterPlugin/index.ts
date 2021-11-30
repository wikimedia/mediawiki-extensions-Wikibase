import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { App } from 'vue';

export default function ClientRouterPlugin( app: App, clientRouter: MediaWikiRouter ): void {
	app.config.globalProperties.$clientRouter = clientRouter;
}
