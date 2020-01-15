import _Vue from 'vue';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default function RepoRouterPlugin( Vue: typeof _Vue, repoRouter: MediaWikiRouter ): void {
	Vue.prototype.$repoRouter = repoRouter;
}
