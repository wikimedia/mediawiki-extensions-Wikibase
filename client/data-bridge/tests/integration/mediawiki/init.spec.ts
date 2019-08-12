import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import { mockMwEnv } from '../../util/mocks';

const mockPrepareContainer = jest.fn();
jest.mock( '@/mediawiki/prepareContainer', () => ( {
	__esModule: true, // this property makes it work
	default: ( oo: any, $: any ) => mockPrepareContainer( oo, $ ),
} ) );

describe( 'init', () => {
	it( 'loads `wikibase.client.data-bridge.app` and launches it on click', () => {
		const app = { launch: jest.fn() },
			require = jest.fn( () => app ),
			using = jest.fn( () => {
				return new Promise( ( resolve ) => resolve( require ) );
			} );
		mockMwEnv( using );
		const expectedServices = new ServiceRepositories();
		expectedServices.setEntityRepository(
			new SpecialPageEntityRepository(
				( window as MwWindow ).$,
				'http://localhost/wiki/Special:EntityData',
			),
		);
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

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( app.launch ).toHaveBeenCalledWith(
				{ containerSelector: '#data-bridge-container' },
				{
					entityId,
					propertyId,
					editFlow,
				},
				expectedServices,
			);
		} );
	} );
} );
