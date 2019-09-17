import ForeignApiEntityLabelRepository from '@/data-access/ForeignApiEntityLabelRepository';
import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import Term from '@/datamodel/Term';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import EntityWithoutLabelInLanguageException from '@/data-access/error/EntityWithoutLabelInLanguageException';

function mockForeignApi( successObject?: unknown, rejectData?: unknown ): ForeignApi {
	return {
		get: (): any => {
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

describe( 'ForeignApiEntityLabelRepository', () => {
	const wellFormedSameLanguageEnResponse = {
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

	it( 'returns well-formed Term for response in very language asked for', () => {
		const foreignApi = mockForeignApi( wellFormedSameLanguageEnResponse );

		const entityLabelReader = new ForeignApiEntityLabelRepository( 'en', foreignApi );

		const expectedTerm = { language: 'en', value: 'Andromeda Galaxy' };

		return entityLabelReader.getLabel( 'Q1141' ).then(
			( actualTerm: Term ) => {
				expect( actualTerm ).toStrictEqual( expectedTerm );
			},
		);
	} );

	it( 'returns well-formed Term for response using language fallback chain', () => {
		const foreignApi = mockForeignApi( {
			entities: {
				Q1141: {
					type: 'item',
					id: 'Q1141',
					labels: {
						de: {
							value: 'Andromeda Galaxy',
							language: 'en',
							'for-language': 'de',
						},
					},
				},
			}, success: 1,
		} );

		const entityLabelReader = new ForeignApiEntityLabelRepository( 'de', foreignApi );

		const expectedTerm = { language: 'en', value: 'Andromeda Galaxy' };

		return entityLabelReader.getLabel( 'Q1141' ).then(
			( actualTerm: Term ) => {
				expect( actualTerm ).toStrictEqual( expectedTerm );
			},
		);
	} );

	it( 'calls the foreignApi with the correct parameters', () => {
		const foreignApi = mockForeignApi( wellFormedSameLanguageEnResponse );
		jest.spyOn( foreignApi, 'get' );
		const id = 'Q1141';
		const languageFor = 'en';

		const entityLabelReader = new ForeignApiEntityLabelRepository( languageFor, foreignApi );

		return entityLabelReader.getLabel( id ).then(
			() => {
				expect( foreignApi.get ).toHaveBeenCalledTimes( 1 );
				expect( foreignApi.get ).toHaveBeenCalledWith( {
					action: 'wbgetentities',
					ids: id,
					props: 'labels',
					languages: languageFor,
					languagefallback: 1,
				} );
			},
		);
	} );

	describe( 'if there is a problem', () => {
		it( 'rejects on property which does not contain label in given language', () => {
			const foreignApi = mockForeignApi( {
				entities: {
					Q4711: {
						type: 'item',
						id: 'Q4711',
						labels: {},
					},
				}, success: 1,
			} );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'de', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q4711' ) )
				.rejects
				.toStrictEqual(
					new EntityWithoutLabelInLanguageException( "Could not find label for language 'de'." ),
				);
		} );

		it( 'rejects on result that does not contain an object', () => {
			const foreignApi = mockForeignApi( 'noObject' );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'foo', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q0' ) )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing entities key', () => {
			const foreignApi = mockForeignApi( {} );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'foo', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q0' ) )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing relevant entity in entities', () => {
			const foreignApi = mockForeignApi( {
				entities: {
					'Q4': {},
				},
			} );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'foo', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q123' ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Result does not contain relevant entity.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing (via error)', () => {
			const foreignApi = mockForeignApi( {
				error: {
					code: 'no-such-entity',
					info: 'Could not find an entity with the ID "Q123".',
					id: 'Q123',
				},
			} );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'foo', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q123' ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing (via missing)', () => {
			const foreignApi = mockForeignApi( {
				entities: {
					Q123: {
						id: 'Q123',
						missing: '',
					},
				}, success: 1,
			} );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'en', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q123' ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects if there was a serverside problem with the API', () => {
			const foreignApi = mockForeignApi( null, { status: 500 } );

			const entityLabelReader = new ForeignApiEntityLabelRepository( 'foo', foreignApi );
			return expect( entityLabelReader.getLabel( 'Q123' ) )
				.rejects
				.toStrictEqual( new JQueryTechnicalError( { status: 500 } as any ) );
		} );

	} );
} );
