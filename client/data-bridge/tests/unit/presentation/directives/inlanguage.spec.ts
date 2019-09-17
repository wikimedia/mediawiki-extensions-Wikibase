import Bcp47Language from '@/datamodel/Bcp47Language';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import inlanguageConstructor from '@/presentation/directives/inlanguage';

describe( 'inlanguage directive', () => {
	it( 'adds language properties to element\'s attributes', () => {
		const languageCode = 'de';
		const language: Bcp47Language = { code: languageCode, directionality: 'ltr' };
		const element = document.createElement( 'div' );
		const resolver: LanguageInfoRepository = {
			resolve: jest.fn( (): Bcp47Language => language ),
		};
		element.setAttribute = jest.fn();
		const inlanguage = inlanguageConstructor( resolver );

		inlanguage(
			element,
			{
				modifiers: {},
				name: 'inlanguage',
				value: languageCode,
			},
			{} as any,
		);

		expect( resolver.resolve ).toHaveBeenCalledWith( languageCode );
		expect( resolver.resolve ).toHaveBeenCalledTimes( 1 );
		expect( element.setAttribute ).toHaveBeenCalledWith( 'lang', language.code );
		expect( element.setAttribute ).toHaveBeenCalledWith( 'dir', language.directionality );
	} );
} );
