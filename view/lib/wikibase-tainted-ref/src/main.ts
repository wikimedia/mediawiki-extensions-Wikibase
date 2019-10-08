import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import { STATEMENT_TAINTED_STATE_INIT } from '@/store/actionTypes';

export function launch(): void {
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
}
