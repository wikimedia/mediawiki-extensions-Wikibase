import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import { STATEMENT_TAINTED_STATE_INIT, STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';

export function launch( editStart: Function ): void {
	const store = createStore();
	const guids: string[] = [];
	document.querySelectorAll( '.wikibase-statementview' ).forEach( ( element ) => {
		const id = element.getAttribute( 'id' );
		const headingElement = element.querySelector( '.wikibase-statementview-references-heading' );
		if ( headingElement && id ) {
			guids.push( id );
			const appElement = headingElement.appendChild( document.createElement( 'div' ) );
			appElement.setAttribute( 'class', 'wikibase-tainted-references-container' );
			new App( { store, data: { id } } ).$mount( appElement );
		}
	} );
	store.dispatch( STATEMENT_TAINTED_STATE_INIT, guids );
	editStart( ( guid: string ) => {
		store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, guid );
	} );
}
