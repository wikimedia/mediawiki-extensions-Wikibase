const {
	getCurrentPageLocation,
	addReturnToParams,
	handleTempUserRedirect
} = require( '../../../resources/wikibase.wbui2025/utils.js' );

describe( 'tempAccountHelpers', () => {
	let mockConfig, mockLocation;

	beforeEach( () => {
		mockConfig = {
			wgPageName: 'Item:Q1',
			wgArticlePath: '/wiki/$1'
		};
		mw.config = {
			get: jest.fn( ( key ) => mockConfig[ key ] )
		};

		mockLocation = {
			search: '?foo=bar&title=Item:Q1',
			hash: '#statements',
			href: '/w/index.php?title=Item:Q1&foo=bar#statements'
		};
		Object.defineProperty( window, 'location', {
			value: mockLocation,
			writable: true,
			configurable: true
		} );

		jest.clearAllMocks();
	} );

	describe( 'getCurrentPageLocation', () => {
		it( 'returns current page information from config and window location', () => {
			const result = getCurrentPageLocation();

			expect( result ).toEqual( {
				title: 'Item:Q1',
				query: 'foo=bar&title=Item:Q1',
				anchor: '#statements'
			} );
			expect( mw.config.get ).toHaveBeenCalledWith( 'wgPageName' );
		} );

		it( 'returns empty strings when search or hash are empty', () => {
			mockLocation.search = '';
			mockLocation.hash = '';

			const result = getCurrentPageLocation();

			expect( result ).toEqual( {
				title: 'Item:Q1',
				query: '',
				anchor: ''
			} );
		} );
	} );

	describe( 'addReturnToParams', () => {
		it( 'adds return-to parameters when location has all fields', () => {
			const params = { action: 'wbeditentity', id: 'Q1' };
			const location = {
				title: 'Item:Q1',
				query: 'param1=value1',
				anchor: '#statements'
			};

			const result = addReturnToParams( params, location );

			expect( result ).toEqual( {
				action: 'wbeditentity',
				id: 'Q1',
				returnto: 'Item:Q1',
				returntoquery: 'param1=value1',
				returntoanchor: '#statements'
			} );
		} );

		it( 'only adds parameters that exist in location', () => {
			const params = { action: 'wbeditentity' };
			const location = {
				title: 'Item:Q1',
				query: '',
				anchor: ''
			};

			const result = addReturnToParams( params, location );

			expect( result ).toEqual( {
				action: 'wbeditentity',
				returnto: 'Item:Q1'
			} );
			expect( result ).not.toHaveProperty( 'returntoquery' );
			expect( result ).not.toHaveProperty( 'returntoanchor' );
		} );

		it( 'does not modify original params object', () => {
			const params = { action: 'wbeditentity', id: 'Q1' };
			const location = { title: 'Item:Q1' };

			addReturnToParams( params, location );

			expect( params ).toEqual( { action: 'wbeditentity', id: 'Q1' } );
		} );
	} );

	describe( 'handleTempUserRedirect', () => {
		it( 'redirects when tempuserredirect is present in response', () => {
			const redirectUrl = 'http://localhost/wiki/Item:Q1';
			const response = { tempuserredirect: redirectUrl };
			const originalHref = window.location.href;

			const result = handleTempUserRedirect( response );

			expect( result ).toBe( true );
			expect( window.location.href ).toBe( redirectUrl );
			window.location.href = originalHref;
		} );

		it( 'returns false when tempuserredirect is not present', () => {
			const response = { entity: { id: 'Q1' } };
			const originalHref = window.location.href;

			const result = handleTempUserRedirect( response );

			expect( result ).toBe( false );
			expect( window.location.href ).toBe( originalHref );
		} );

		it( 'returns false when response is empty', () => {
			const response = {};
			const originalHref = window.location.href;

			const result = handleTempUserRedirect( response );

			expect( result ).toBe( false );
			expect( window.location.href ).toBe( originalHref );
		} );
	} );
} );
