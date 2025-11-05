const { setActivePinia, createPinia } = require( 'pinia' );
const { useServerRenderedHtml } = require( '../../../resources/wikibase.wbui2025/store/serverRenderedHtml.js' );

describe( 'Server-rendered HTML Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'store starts empty', () => {
		const serverRenderedHtml = useServerRenderedHtml();
		expect( serverRenderedHtml.propertyLinks.size ).toBe( 0 );
		expect( serverRenderedHtml.snakValues.size ).toBe( 0 );
	} );
} );
