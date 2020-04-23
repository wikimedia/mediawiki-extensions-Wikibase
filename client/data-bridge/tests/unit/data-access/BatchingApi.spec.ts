import BatchingApi from '@/data-access/BatchingApi';
import Api, {
	ApiAction,
	ApiParams,
} from '@/definitions/data-access/Api';

describe( 'BatchingApi', () => {

	const testCases: [ string, object[], object[] ][] = [
		// name, [ originalParams... ], [ resultingParams... ]
		[ 'merge two empty params', [ {}, {} ], [ {} ] ],
		[ 'merge five empty params', [ {}, {}, {}, {}, {} ], [ {} ] ],
		[ 'merge param on left side (right side missing)', [ { a: 'a' }, {} ], [ { a: 'a' } ] ],
		[ 'merge param on left side (right side undefined)', [ { a: 'a' }, { a: undefined } ], [ { a: 'a' } ] ],
		[ 'merge param on right side (left side missing)', [ {}, { a: 'a' } ], [ { a: 'a' } ] ],
		[ 'merge param on right side (left side undefined)', [ { a: undefined }, { a: 'a' } ], [ { a: 'a' } ] ],
		[
			'merge different params',
			[ { a: 'a' }, { b: 'b' }, { c: 'c' }, { d: undefined } ],
			[ { a: 'a', b: 'b', c: 'c', d: undefined } ],
		],
		[
			'merge multiple different params',
			[ { a: 'a', A: 'A' }, { b: 'b', B: 'B' }, { c: 'c', C: 'C' }, { d: undefined, D: undefined } ],
			[ { a: 'a', A: 'A', b: 'b', B: 'B', c: 'c', C: 'C', d: undefined, D: undefined } ],
		],
		[
			'merge same param',
			[ { a: 'a' }, { a: 'a' }, { a: 'a' } ],
			[ { a: 'a' } ],
		],
		[
			'merge same param (undefined)',
			[ { a: undefined }, { a: undefined } ],
			[ { a: undefined } ],
		],
		[
			'merge compatible param, int and string',
			[ { formatversion: 2 }, { formatversion: '2' } ],
			[ { formatversion: '2' } ],
		],
		[
			'don’t merge incompatible param, two strings',
			[ { formatversion: '1' }, { formatversion: '2' } ],
			[ { formatversion: '1' }, { formatversion: '2' } ],
		],
		[
			'don’t merge incompatible param, two arrays',
			[
				{ amargs: [ 'arg', 'other arg', 'arg' ] },
				{ amargs: [ 'other arg', 'unrelated arg' ] },
			],
			[
				{ amargs: [ 'arg', 'other arg', 'arg' ] },
				{ amargs: [ 'other arg', 'unrelated arg' ] },
			],
		],
		[
			'merge compatible param, three sets of strings',
			[
				{ titles: new Set( [ 'Title A', 'Title B', 'Title C' ] ) },
				{ titles: new Set( [ 'Title A', 'Title AA', 'Title AAA' ] ) },
				{ titles: new Set( [ 'Title A', 'Title' ] ) },
			],
			[ { titles: new Set( [
				'Title A', 'Title B', 'Title C',
				'Title AA', 'Title AAA',
				'Title',
			] ) } ],
		],
		[
			'merge compatible param, set of strings and set of integers',
			[
				{ pageids: new Set( [ 123, 456 ] ) },
				{ pageids: new Set( [ '123', '456' ] ) },
			],
			[ { pageids: new Set( [ '123', '456' ] ) } ],
		],
		[
			'merge compatible param, two booleans',
			[ { curtimestamp: true }, { curtimestamp: true } ],
			[ { curtimestamp: true } ],
		],
		[
			'don’t merge incompatible param, two booleans',
			[ { redirects: true }, { redirects: false } ],
			[ { redirects: true }, { redirects: false } ],
		],
		[
			'full example',
			[
				{
					action: 'query',
					meta: new Set( [ 'wbdatabridgeconfig' ] ),
					formatversion: 2,
					errorformat: 'raw',
				},
				{
					action: 'wbgetentities',
					props: new Set( [ 'labels' ] ),
					ids: new Set( [ 'Q1' ] ),
					languages: new Set( [ 'en' ] ),
					languagefallback: true,
					formatversion: 2,
				},
				{
					action: 'query',
					titles: new Set( [ 'Title' ] ),
					prop: new Set( [ 'info' ] ),
					intestactions: new Set( [ 'edit' ] ),
					intestactionsdetail: 'full',
					formatversion: 2,
					errorformat: 'raw',
				},
				{
					action: 'wbgetentities',
					props: new Set( [ 'datatype' ] ),
					ids: new Set( [ 'P1' ] ),
					formatversion: 2,
				},
			],
			[
				{
					action: 'query',
					meta: new Set( [ 'wbdatabridgeconfig' ] ),
					formatversion: '2',
					errorformat: 'raw',
					titles: new Set( [ 'Title' ] ),
					prop: new Set( [ 'info' ] ),
					intestactions: new Set( [ 'edit' ] ),
					intestactionsdetail: 'full',
				},
				{
					action: 'wbgetentities',
					props: new Set( [ 'labels', 'datatype' ] ),
					ids: new Set( [ 'Q1', 'P1' ] ),
					languages: new Set( [ 'en' ] ),
					languagefallback: true,
					formatversion: '2',
				},
			],
		],
	];

	test.each( testCases )( '%s', async (
		_name,
		originalParams: object[],
		resultingParams: object[],
	) => {
		const innerApi: Api = {
			get: jest.fn().mockResolvedValue( {} ),
		} as unknown as Api;
		const api = new BatchingApi( innerApi );

		await Promise.all( originalParams.map(
			( params ) => api.get( params as ApiParams<ApiAction> ),
		) );

		expect( innerApi.get ).toHaveBeenCalledTimes( resultingParams.length );
		for ( const [ i, params ] of resultingParams.entries() ) {
			expect( innerApi.get ).toHaveBeenNthCalledWith( i + 1, params );
		}
	} );

} );
