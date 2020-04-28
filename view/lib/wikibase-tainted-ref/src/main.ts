import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import { STORE_INIT, HELP_LINK_SET } from '@/store/actionTypes';
import { HookHandler } from '@/HookHandler';
import { TrackFunction } from '@/store/TrackFunction';

export function launch(
	hookHandler: HookHandler,
	helpLink: string,
	trackFunction: TrackFunction,
): void {
	const store = createStore( trackFunction );
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
	store.dispatch( STORE_INIT, guids );
	store.dispatch( HELP_LINK_SET, helpLink );
	hookHandler.addStore( store );
}
