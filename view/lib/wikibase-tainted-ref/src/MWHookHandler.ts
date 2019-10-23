import { HookHandler } from '@/HookHandler';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';

export default class MWHookHandler implements HookHandler {
	private readonly editStartFunction: Function;

	public constructor( editStartFunction: Function ) {
		this.editStartFunction = editStartFunction;
	}

	public addStore( store: Store<Application> ): void {
		this.editStartFunction( ( guid: string ) => {
			store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, guid );
		} );
	}
}
