import _Vue from 'vue';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default function ClientRouterPlugin( Vue: typeof _Vue, clientRouter: MediaWikiRouter ): void {
	Vue.prototype.$clientRouter = clientRouter;
}
