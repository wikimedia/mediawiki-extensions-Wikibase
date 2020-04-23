import ApiReadingEntityRepository from '@/data-access/ApiReadingEntityRepository';
import { mockApi } from '../../util/mocks';

describe( 'ApiReadingEntityRepository', () => {
	it( 'returns well formatted answer as expected', async () => {
		const api = mockApi( {
			'entities': {
				'Q123': {
					'pageid': 214,
					'ns': 120,
					'title': 'Item:Q123',
					'lastrevid': 2183,
					'modified': '2019-07-09T09:08:46Z',
					'type': 'item',
					'id': 'Q123',
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
				},
			},
		} );

		const entityDataSupplier = new ApiReadingEntityRepository( api );

		const actualResultData = await entityDataSupplier.getEntity( 'Q123' );

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

		expect( actualResultData ).toEqual( expectedData );
	} );
} );
