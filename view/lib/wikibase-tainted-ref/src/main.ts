import App from '@/presentation/App.vue';

export function launch(): void {
	document.querySelectorAll( '.wikibase-statementview' ).forEach( ( element ) => {
		const headingElement = element.querySelector( '.wikibase-statementview-references-heading' );
		if ( headingElement ) {
			const appElement = headingElement.appendChild( document.createElement( 'div' ) );
			appElement.setAttribute( 'class', 'wikibase-tainted-references-container' );
			new App().$mount( appElement );
		}
	} );
}
