import RepoRouter from '@/data-access/RepoRouter';

describe( 'RepoRouter', () => {
	const mockWikiUrlencode = jest.fn();
	const querySerializer = jest.fn();
	beforeEach( () => {
		mockWikiUrlencode.mockImplementation( ( title ) => title );
	} );

	it( 'correctly builds page url using url & article path', () => {
		const title = 'Item:Q42';
		const router = new RepoRouter(
			{
				url: 'http://localhost:8087',
				articlePath: '/wiki/$1',
				scriptPath: '/w',
			},
			mockWikiUrlencode,
			querySerializer,
		);

		expect( router.getPageUrl( title ) )
			.toBe( 'http://localhost:8087/wiki/' + title );
		expect( mockWikiUrlencode ).toHaveBeenCalledWith( title );
		expect( querySerializer ).not.toHaveBeenCalled();
	} );

	it( 'correctly builds page url using url & script path & query', () => {
		querySerializer.mockImplementation( ( query ) => new URLSearchParams( query ).toString() );

		const title = 'Special:Log';
		const params = {
			page: 'Item:Q42',
		};
		const router = new RepoRouter(
			{
				url: 'http://localhost:8087',
				articlePath: '/wiki/$1',
				scriptPath: '/w',
			},
			mockWikiUrlencode,
			querySerializer,
		);

		// inconsistent colon escaping in line with util.getUrl()
		expect( router.getPageUrl( title, params ) )
			.toBe( 'http://localhost:8087/w/index.php?title=Special:Log&page=Item%3AQ42' );
		expect( mockWikiUrlencode ).toHaveBeenCalledWith( title );
		expect( querySerializer ).toHaveBeenCalledWith( params );
	} );

	it( 'runs the title through wikiUrlencode', () => {
		const title = 'Foo bar';
		const escapedTitle = 'Foo_bar';
		mockWikiUrlencode.mockReturnValue( escapedTitle );
		const router = new RepoRouter(
			{
				url: 'http://localhost:8087',
				articlePath: '/wiki/$1',
				scriptPath: '/w',
			},
			mockWikiUrlencode,
			querySerializer,
		);

		expect( router.getPageUrl( title ) )
			.toBe( 'http://localhost:8087/wiki/' + escapedTitle );
		expect( mockWikiUrlencode ).toHaveBeenCalledWith( title );
		expect( querySerializer ).not.toHaveBeenCalled();
	} );

	it( 'is robust against special replacement pattern $$', () => {
		const title = 'Test$$hello$$'; // https://phabricator.wikimedia.org/T149767
		const router = new RepoRouter(
			{
				url: 'http://localhost:8087',
				articlePath: '/wiki/$1',
				scriptPath: '/w',
			},
			mockWikiUrlencode,
			querySerializer,
		);

		expect( router.getPageUrl( title ) )
			.toBe( 'http://localhost:8087/wiki/' + title );
		expect( mockWikiUrlencode ).toHaveBeenCalledWith( title );
		expect( querySerializer ).not.toHaveBeenCalled();
	} );
} );
