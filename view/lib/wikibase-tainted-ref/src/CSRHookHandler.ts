import { HookHandler } from '@/HookHandler';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';

export default class CSRHookHandler implements HookHandler {
	public addStore( store: Store<Application> ): void {
		setTimeout( () => {
			store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, 'Q73$c14f0d0c-4b50-784a-48ef-3c8d55d2a03b' );
		}, 2000 );
	}
}
