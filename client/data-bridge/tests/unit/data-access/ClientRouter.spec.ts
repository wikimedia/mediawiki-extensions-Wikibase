import ClientRouter from '@/data-access/ClientRouter';

describe( 'ClientRouter', () => {
	it( 'delegates to mw.util.getUrl', () => {
		const title = 'Some page';
		const params = { action: 'edit' };
		const url = 'https://wiki.example/w/index.php?action=edit&title=Some_page';
		const getUrl = jest.fn().mockReturnValue( url );
		const router = new ClientRouter( getUrl );

		expect( router.getPageUrl( title, params ) ).toBe( url );
		expect( getUrl ).toHaveBeenCalledWith( title, params );
	} );
} );
