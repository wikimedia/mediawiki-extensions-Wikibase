import AppInformation from '@/definitions/AppInformation';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ApplicationConfig from '@/definitions/ApplicationConfig';

const mockPrepareContainer = jest.fn();
jest.mock( '@/mediawiki/prepareContainer', () => ( {
	__esModule: true, // this property makes it work
	default: ( oo: any, $: any ) => mockPrepareContainer( oo, $ ),
} ) );

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

		const entityId = 'Q5';
		const propertyId = 'P4711';
		const editFlow = EditFlow.OVERWRITE;
		const testLinkHref = `https://www.wikidata.org/wiki/${entityId}?uselang=en#${propertyId}`;
		document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>`;
		const testLink = document.querySelector( 'a' );

		return init().then( () => {
			testLink!.click();

			const appConfig: ApplicationConfig = {
				containerSelector: '#data-bridge-container',
			};
			const appInformation: AppInformation = {
				entityId,
				propertyId,
				editFlow,
			};
			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( app.launch ).toHaveBeenCalledWith(
				appConfig,
				appInformation,
			);
		} );
	} );
} );
