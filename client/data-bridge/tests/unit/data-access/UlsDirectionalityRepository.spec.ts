import UlsDirectionalityRepository from '@/data-access/UlsDirectionalityRepository';

describe( 'UlsDirectionalityRepository', () => {
	it( 'passes the language code to the uls', () => {
		const ulsData = {
			getDir: jest.fn(),
		};

		const languageCode = 'er';
		const directionality = new UlsDirectionalityRepository( ulsData );

		directionality.resolve( languageCode );
		expect( ulsData.getDir ).toBeCalledTimes( 1 );
		expect( ulsData.getDir ).toBeCalledWith( languageCode );
	} );

	it( 'returns the directionality provided by uls', () => {
		const dir = 'ltr';
		const ulsData = {
			getDir: (): 'ltr'|'rtl' => dir,
		};

		const directionality = new UlsDirectionalityRepository( ulsData );

		expect( directionality.resolve( 'any' ) ).toBe( dir );
	} );
} );
