const { scrollToStatementWithPropertyId } = require( '../../../resources/wikibase.wbui2025/utils.js' );

describe( 'scrollToStatementWithPropertyId', () => {
	const WRAPPER_ID = 'P1';

	let el;
	beforeEach( () => {
		el = document.createElement( 'div' );
		el.id = WRAPPER_ID;
		el.scrollIntoView = jest.fn();
		document.body.appendChild( el );
	} );

	afterEach( () => {
		el.remove();
	} );

	it( 'scrolls element to the top of the viewport', () => {
		scrollToStatementWithPropertyId( 'P1' );
		expect( el.scrollIntoView ).toHaveBeenCalledWith( { behavior: 'smooth', block: 'start' } );
	} );

	it( 'does nothing when the element does not exist', () => {
		expect( () => scrollToStatementWithPropertyId( 'P99' ) ).not.toThrow();
	} );
} );
