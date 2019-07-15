import init from '@/mediawiki/init';
import MwWindow from '@/@types/mediawiki/MwWindow';

function mockMwEnv( using: () => Promise<any>, get: () => any ): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
		config: {
			get,
		},
		log: {
			deprecate: jest.fn(),
			error: jest.fn(),
			warn: jest.fn(),
		},
	};
}

describe( 'init', () => {

	it( 'loads `wikibase.client.data-bridge.app` and launches it on click', () => {
		const app = { launch: jest.fn() },
			require = jest.fn( () => app ),
			using = jest.fn( () => {
				return new Promise( ( resolve ) => resolve( require ) );
			} );
		const get = (): any => ( {
			hrefRegExp: 'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
		} );
		mockMwEnv( using, get );

		const entityID = 'Q5';
		const propertyID = 'P4711';
		const editFlow = 'overwrite';
		const testLinkHref = `https://www.wikidata.org/wiki/${entityID}?uselang=en#${propertyID}`;
		document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>`;
		const testLink = document.querySelector( 'a' );

		return init().then( () => {
			testLink!.click();

			expect( app.launch ).toHaveBeenCalledWith( {
				entityID,
				propertyID,
				editFlow,
			} );
		} );
	} );
} );
