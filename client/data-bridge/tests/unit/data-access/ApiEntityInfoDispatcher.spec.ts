import { Api } from '@/@types/mediawiki/MwWindow';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import ApiEntityInfoDispatcher from '@/data-access/ApiEntityInfoDispatcher';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';

function mockApi( successObject?: unknown, rejectData?: unknown ): Api {
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

describe( 'ApiEntityInfoDispatcher', () => {
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
		const api = mockApi( wellFormedResponse );
		const dispatcher = new ApiEntityInfoDispatcher( api );
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
		const api = mockApi( {
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
		jest.spyOn( api, 'get' );
		const dispatcher = new ApiEntityInfoDispatcher( api, [ 'labels', 'datatype' ] );
		const labelRequestPromise = dispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'labels' ],
			ids: [ 'Q1141' ],
			otherParams: {
				languages: 'en',
				languagefallback: 1,
			},
		} );
		expect( api.get ).toHaveBeenCalledTimes( 0 );
		dispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'datatype' ],
			ids: [ 'P123' ],
		} );

		return labelRequestPromise.then(
			() => {
				expect( api.get ).toHaveBeenCalledTimes( 1 );
				expect( api.get ).toHaveBeenCalledWith( {
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
			const api = mockApi( 'noObject' );
			const dispatcher = new ApiEntityInfoDispatcher( api );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing entities key', () => {
			const api = mockApi( {} );
			const dispatcher = new ApiEntityInfoDispatcher( api );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing relevant entity in entities', () => {
			const api = mockApi( {
				entities: {
					'Q4': {},
				},
			} );
			const dispatcher = new ApiEntityInfoDispatcher( api );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Result does not contain relevant entity.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing (via error)', () => {
			const api = mockApi( {
				error: {
					code: 'no-such-entity',
					info: 'Could not find an entity with the ID "P123".',
					id: 'P123',
				},
			} );
			const dispatcher = new ApiEntityInfoDispatcher( api );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing (via missing)', () => {
			const api = mockApi( {
				entities: {
					P123: {
						id: 'P123',
						missing: '',
					},
				}, success: 1,
			} );
			const dispatcher = new ApiEntityInfoDispatcher( api );
			const requestPromise = dispatcher.dispatchEntitiesInfoRequest( {
				props: [ 'datatype' ],
				ids: [ 'P123' ],
			} );

			return expect( requestPromise )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects if there was a serverside problem with the API', () => {
			const api = mockApi( null, { status: 500 } );
			const dispatcher = new ApiEntityInfoDispatcher( api );
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
