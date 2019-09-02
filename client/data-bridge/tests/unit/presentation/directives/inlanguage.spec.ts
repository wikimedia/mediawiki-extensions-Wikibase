import DirectionalityRepository from '@/definitions/data-access/DirectionalityRepository';
import inlanguageConstructor from '@/presentation/directives/inlanguage';

describe( 'inlanguage directive', () => {
	it( 'adds language properties to element\'s attributes', () => {
		const languageCode = 'de';
		const directionality = 'ltr';
		const element = document.createElement( 'div' );
		const resolver: DirectionalityRepository = {
			resolve: jest.fn( (): 'rtl'|'ltr' => directionality ),
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
		expect( resolver.resolve ).toBeCalledTimes( 1 );
		expect( element.setAttribute ).toHaveBeenCalledWith( 'lang', languageCode );
		expect( element.setAttribute ).toHaveBeenCalledWith( 'dir', directionality );
	} );
} );
