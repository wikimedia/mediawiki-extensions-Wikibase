import Bcp47Language from '@/datamodel/Bcp47Language';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import InLanguagePlugin from '@/presentation/plugins/InLanguagePlugin';
import { App } from 'vue';

describe( 'InLanguage plugin', () => {
	it( 'adds $inLanguage to global instance and resolve language code', () => {
		const languageCode = 'de';
		const language: Bcp47Language = { code: languageCode, directionality: 'ltr' };
		const resolver: LanguageInfoRepository = {
			resolve: jest.fn( (): Bcp47Language => language ),
		};
		const app = {
			config: { globalProperties: {} },
		} as App;

		InLanguagePlugin( app, resolver );

		const actualLanguage = app.config.globalProperties.$inLanguage( languageCode );

		expect( resolver.resolve ).toHaveBeenCalledWith( languageCode );
		expect( resolver.resolve ).toHaveBeenCalledTimes( 1 );
		expect( actualLanguage.lang ).toBe( language.code );
		expect( actualLanguage.dir ).toBe( language.directionality );
	} );

	it( 'resolves to an empty object if called without language', () => {
		const resolver = {
			resolve: jest.fn(),
		};
		const app = {
			config: { globalProperties: {} },
		} as App;

		InLanguagePlugin( app, resolver );

		const actualLanguage = app.config.globalProperties.$inLanguage( '' );

		expect( resolver.resolve ).not.toHaveBeenCalled();
		expect( actualLanguage ).toStrictEqual( {} );
	} );
} );
