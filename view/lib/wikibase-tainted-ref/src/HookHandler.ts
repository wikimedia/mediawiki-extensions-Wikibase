import { Store } from 'vuex';
import Application from '@/store/Application';

export interface HookHandler {
	/**
	 * Adds a Store to the HookHandler. Actions from this Store will then be triggered as the hooks are handled.
	 * This implies that implementations of the HookHandler provide their own way to ingest hooks (e.g. via their
	 * constructor or some internal logic)
	 *
	 * @param store
	 */
	addStore( store: Store<Application> ): void;
}
