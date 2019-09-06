import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';

describe( 'MwLanguageInfoRepository', () => {
	it( 'passes the language code to the uls', () => {
		const ulsData = {
			getDir: jest.fn(),
		};

		const languageCode = 'er';
		const language = new MwLanguageInfoRepository( { bcp47: jest.fn() }, ulsData );

		language.resolve( languageCode );
		expect( ulsData.getDir ).toBeCalledTimes( 1 );
		expect( ulsData.getDir ).toBeCalledWith( languageCode );
	} );

	it( 'returns the directionality provided by uls', () => {
		const directionality = 'ltr';
		const ulsData = {
			getDir: (): 'ltr'|'rtl' => directionality,
		};

		const language = new MwLanguageInfoRepository( { bcp47: jest.fn() }, ulsData );

		expect( language.resolve( 'any' ) ).toEqual( { directionality } );
	} );

	it( 'passes the language code to the mw.language', () => {
		const mwLanguage = {
			bcp47: jest.fn(),
		};

		const languageCode = 'er';
		const language = new MwLanguageInfoRepository( mwLanguage, { getDir: jest.fn() } );

		language.resolve( languageCode );
		expect( mwLanguage.bcp47 ).toBeCalledTimes( 1 );
		expect( mwLanguage.bcp47 ).toBeCalledWith( languageCode );
	} );

	it( 'returns the a bcp47 conform language code', () => {
		const code = 'gsw';
		const mwLanguage = {
			bcp47: (): string => code,
		};

		const language = new MwLanguageInfoRepository( mwLanguage, { getDir: jest.fn() } );
		expect( language.resolve( 'als' ) ).toEqual( { code } );
	} );
} );
