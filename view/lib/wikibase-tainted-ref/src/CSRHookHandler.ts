import { HookHandler } from '@/HookHandler';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { STATEMENT_TAINTED_STATE_UNTAINT, STATEMENT_TAINTED_STATE_TAINT } from '@/store/actionTypes';

export default class CSRHookHandler implements HookHandler {
	public addStore( store: Store<Application> ): void {
		document.querySelectorAll( '.wikibase-statementview' ).forEach( ( element ) => {
			const guid = element.getAttribute( 'id' );

			const untaintLink: HTMLElement | null = element.querySelector( '.wikibase-toolbar-button-untaint' );
			if ( untaintLink ) {
				untaintLink.onclick = () => {
					store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, guid );
					return false;
				};
			}
			const taintLink: HTMLElement | null = element.querySelector( '.wikibase-toolbar-button-taint' );
			if ( taintLink ) {
				taintLink.onclick = () => {
					store.dispatch( STATEMENT_TAINTED_STATE_TAINT, guid );
					return false;
				};
			}
		} );
	}
}
