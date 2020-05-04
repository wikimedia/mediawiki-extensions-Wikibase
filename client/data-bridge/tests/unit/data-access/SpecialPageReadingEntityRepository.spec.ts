import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';

function jQueryGetMock( successObject: null | unknown, rejectData?: unknown ): JQueryStatic {
	const jQueryMock = {
		get(): any {
			if ( successObject !== null ) {
				return Promise.resolve( successObject );
			}
			return Promise.reject( rejectData );
		},
	};

	return ( jQueryMock as unknown ) as JQueryStatic;
}

describe( 'SpecialPageReadingEntityRepository', () => {
	it( 'returns well formatted answer as expected', async () => {
		const jQueryMock = jQueryGetMock( {
			'entities': {
				'Q123': {
					'pageid': 214,
					'ns': 120,
					'title': 'Item:Q123',
					'lastrevid': 2183,
					'modified': '2019-07-09T09:08:46Z',
					'type': 'item',
					'id': 'Q123',
					'labels': { 'en': { 'language': 'en', 'value': 'Wikidata bridge test item' } },
					'descriptions': [],
					'aliases': [],
					'claims': {
						'P20': [ {
							'mainsnak': {
								'snaktype': 'value',
								'property': 'P20',
								'datavalue': {
									'value': 'String for Wikidata bridge',
									'type': 'string',
								},
								'datatype': 'string',
							},
							'type': 'statement',
							'id': 'Q123$36ae6854-4e74-d74c-d583-701bc130166f',
							'rank': 'normal',
						} ],
					},
					'sitelinks': [],
				},
			},
		} );

		jest.spyOn( jQueryMock, 'get' );

		const entityDataSupplier = new SpecialPageReadingEntityRepository(
			jQueryMock,
			'index.php?title=Special:EntityData/',
		);

		const actualResultData = await entityDataSupplier.getEntity( 'Q123', 2183 );

		const expectedData = {
			entity: {
				'id': 'Q123',
				'statements': {
					'P20': [ {
						'id': 'Q123$36ae6854-4e74-d74c-d583-701bc130166f',
						'mainsnak': {
							'datatype': 'string',
							'datavalue': { 'type': 'string', 'value': 'String for Wikidata bridge' },
							'property': 'P20',
							'snaktype': 'value',
						},
						'rank': 'normal',
						'type': 'statement',
					} ],
				},
			},
			revisionId: 2183,
		};

		expect( jQueryMock.get ).toHaveBeenCalledTimes( 1 );
		expect( jQueryMock.get )
			.toHaveBeenCalledWith( 'index.php?title=Special:EntityData/Q123.json', { revision: 2183 } );
		expect( actualResultData ).toEqual( expectedData );
	} );

	describe( 'expected URL + params', () => {
		it.each( [
			// [ 'index.php/Special:EntityData', undefined, 'index.php/Special:EntityData/Q1.json', {} ],
			[ 'index.php/Special:EntityData', 2, 'index.php/Special:EntityData/Q1.json', { revision: 2 } ],
			// [ 'index.php?title=Special:EntityData', undefined, 'index.php?title=Special:EntityData/Q1.json', {} ],
			[ 'index.php?title=Special:EntityData', 2, 'index.php?title=Special:EntityData/Q1.json', { revision: 2 } ],
		] )(
			'base URL %s + revision ID %s = URL %s + params %s',
			async ( baseUrl, revisionId, expectedUrl, expectedParams ) => {
				const jQueryMock = jQueryGetMock( {} );
				jest.spyOn( jQueryMock, 'get' );

				const entityDataSupplier = new SpecialPageReadingEntityRepository( jQueryMock, baseUrl );
				try {
					await entityDataSupplier.getEntity( 'Q1', revisionId );
				} catch ( _e ) {
					// ignore result not well-formed error
				}

				expect( jQueryMock.get ).toHaveBeenCalledWith( expectedUrl, expectedParams );
			},
		);
	} );

	describe( 'if there is a problem', () => {
		it( 'rejects on result that does not contain an object', () => {
			const jQueryMock = jQueryGetMock( '<some><random><html>' );

			const entityDataSupplier = new SpecialPageReadingEntityRepository( jQueryMock, 'testurl' );
			return expect( entityDataSupplier.getEntity( 'Q123', 456 ) )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing entities key', () => {
			const jQueryMock = jQueryGetMock( {} );

			const entityDataSupplier = new SpecialPageReadingEntityRepository( jQueryMock, 'testurl' );
			return expect( entityDataSupplier.getEntity( 'Q123', 456 ) )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'rejects on result missing relevant entity in entities', () => {
			const jQueryMock = jQueryGetMock( {
				entities: {
					'Q4': {},
				},
			} );

			const entityDataSupplier = new SpecialPageReadingEntityRepository( jQueryMock, 'testurl' );
			return expect( entityDataSupplier.getEntity( 'Q123', 456 ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Result does not contain relevant entity.' ) );
		} );

		it( 'rejects on result indicating relevant entity as missing', () => {
			const jQueryMock = jQueryGetMock( null, { status: 404 } );

			const entityDataSupplier = new SpecialPageReadingEntityRepository( jQueryMock, 'testurl' );
			return expect( entityDataSupplier.getEntity( 'Q123', 456 ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'rejects if there was a serverside problem with the API', () => {
			const jQueryMock = jQueryGetMock( null, { status: 500 } );

			const entityDataSupplier = new SpecialPageReadingEntityRepository( jQueryMock, 'testurl' );
			return expect( entityDataSupplier.getEntity( 'Q123', 456 ) )
				.rejects
				.toStrictEqual( new JQueryTechnicalError( { status: 500 } as any ) );
		} );
	} );
} );
