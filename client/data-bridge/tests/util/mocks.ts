import MwWindow from '@/@types/mediawiki/MwWindow';
import MwConfig from '@/@types/mediawiki/MwConfig';
import WbRepo from '@/@types/wikibase/WbRepo';

export function mockMwConfig( values: {
	hrefRegExp?: string|null;
	wbRepo?: WbRepo;
} = {} ): MwConfig {
	if ( values.hrefRegExp === undefined ) {
		values.hrefRegExp = 'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)';
	}
	return {
		get( key: string ): any {
			switch ( key ) {
				case 'wbDataBridgeConfig':
					return {
						hrefRegExp: values.hrefRegExp,
					};
				case 'wbRepo':
					return values.wbRepo || {
						url: 'http://localhost',
						scriptPath: '/w/',
						articlePath: '/wiki/$1',
					};
				default:
					throw new Error( `unexpected config key ${key}` );
			}
		},
	};
}

export function mockMwEnv(
	using: () => Promise<any> = jest.fn(),
	config: MwConfig = mockMwConfig(),
	warn: () => void = jest.fn(),
): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
		config,
		log: {
			deprecate: jest.fn(),
			error: jest.fn(),
			warn,
		},
	};
	( window as MwWindow ).$ = new ( jest.fn() )();
}
