import { MessageToTextFunction } from '@/@types/MessageOptions';
import { TrackFunction } from '@/@types/TrackingOptions';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import { STORE_INIT, HELP_LINK_SET } from '@/store/actionTypes';
import { HookHandler } from '@/HookHandler';
import Message from '@/vue-plugins/Message';
import Track from '@/vue-plugins/Track';
import Vue, { CreateElement } from 'vue';

export function launch(
	hookHandler: HookHandler,
	helpLink: string,
	messageToTextFunction: MessageToTextFunction,
	trackingFunction: TrackFunction,
): void {
	Vue.use( Message, { messageToTextFunction } );
	Vue.use( Track, { trackingFunction } );
	const store = createStore( trackingFunction );
	const guids: string[] = [];
	document.querySelectorAll( '.wikibase-statementview' ).forEach( ( element ) => {
		const id = element.getAttribute( 'id' );
		const headingElement = element.querySelector( '.wikibase-statementview-references-heading' );
		if ( headingElement && id ) {
			guids.push( id );
			const appElement = headingElement.appendChild( document.createElement( 'div' ) );
			appElement.setAttribute( 'class', 'wikibase-tainted-references-container' );
			const compatApp = {
				store,
				render( h: CreateElement ) {
					return h( App, { props: { id } } );
				},
			};
			Vue.createMwApp( compatApp )
				.mount( appElement );
		}
	} );
	store.dispatch( STORE_INIT, guids );
	store.dispatch( HELP_LINK_SET, helpLink );
	hookHandler.addStore( store );
}
