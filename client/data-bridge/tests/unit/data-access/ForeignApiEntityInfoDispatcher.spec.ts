import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import ForeignApiEntityInfoDispatcher from '@/data-access/ForeignApiEntityInfoDispatcher';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';

function mockForeignApi( successObject?: unknown, rejectData?: unknown ): ForeignApi {
	return {
		get(): any {
			if ( successObject ) {
				return Promise.resolve( successObject );
			}
			if ( rejectData ) {
				return Promise.reject( rejectData );
			}
			return Promise.resolve( {
				success: 1,
				entities: {
					Q0: {
						id: 'Q0',
						labels: {
							'de': {
								value: 'foo',
								language: 'de',
							},
						},
					},
				},
			} );
		},
	} as any;
}

describe( 'ForeignApiEntityInfoDispatcher', () => {
	const wellFormedResponse = {
		entities: {
			Q1141: {
				type: 'item',
				id: 'Q1141',
				labels: {
					en: {
						value: 'Andromeda Galaxy',
						language: 'en',
					},
				},
			},
		}, success: 1,
	};
	it( 'returns well-formed entities as received in the request', () => {
		const foreignApi = mockForeignApi( wellFormedResponse );
		const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
		const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'labels' ],
			ids: [ 'Q1141' ],
			otherParams: {
				languages: 'en',
				languagefallback: 1,
			},
		} );

		return requestPromise.then(
			( actualEntities: any ) => {
				expect( actualEntities ).toStrictEqual( wellFormedResponse.entities );
			},
		);
	} );

	it( 'bundles requests', () => {
		const foreignApi = mockForeignApi( {
			entities: {
				Q1141: {
					type: 'item',
					id: 'Q1141',
					labels: {
						en: {
							value: 'Andromeda Galaxy',
							language: 'en',
						},
					},
				},
				P123: {
					datatype: 'string',
					id: 'P123',
				},
			}, success: 1,
		} );
		jest.spyOn( foreignApi, 'get' );
		const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi, [ 'labels', 'datatype' ] );
		const labelRequestPromise = dispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'labels' ],
			ids: [ 'Q1141' ],
			otherParams: {
				languages: 'en',
				languagefallback: 1,
			},
		} );
		expect( foreignApi.get ).toHaveBeenCalledTimes( 0 );
		dispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'datatype' ],
			ids: [ 'P123' ],
		} );

		return labelRequestPromise.then(
			() => {
				expect( foreignApi.get ).toHaveBeenCalledTimes( 1 );
				expect( foreignApi.get ).toHaveBeenCalledWith( {
					action: 'wbgetentities',
					ids: [ 'Q1141', 'P123' ],
					props: [ 'labels', 'datatype' ],
					languages: 'en',
					languagefallback: 1,
				} );
			},
		);
	} );

	describe( 'if there is a problem', () => {

		it( 'rejects on result that does not contain an object', () => {
			const foreignApi = mockForeignApi( 'noObject' );
			const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing entities key', () => {
			const foreignApi = mockForeignApi( {} );
			const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing relevant entity in entities', () => {
			const foreignApi = mockForeignApi( {
				entities: {
					'Q4': {},
				},
			} );
			const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Result does not contain relevant entity.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing (via error)', () => {
			const foreignApi = mockForeignApi( {
				error: {
					code: 'no-such-entity',
					info: 'Could not find an entity with the ID "P123".',
					id: 'P123',
				},
			} );
			const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing (via missing)', () => {
			const foreignApi = mockForeignApi( {
				entities: {
					P123: {
						id: 'P123',
						missing: '',
					},
				}, success: 1,
			} );
			const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects if there was a serverside problem with the API', () => {
			const foreignApi = mockForeignApi( null, { status: 500 } );
			const dispatcher = new ForeignApiEntityInfoDispatcher( foreignApi );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new JQueryTechnicalError( { status: 500 } as any ) );
		} );
	} );
} );
