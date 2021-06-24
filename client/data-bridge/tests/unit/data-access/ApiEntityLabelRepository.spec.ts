import ApiEntityLabelRepository from '@/data-access/ApiEntityLabelRepository';
import ApiErrors from '@/data-access/error/ApiErrors';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import EntityWithoutLabelInLanguageException from '@/data-access/error/EntityWithoutLabelInLanguageException';
import { mockApi } from '../../util/mocks';

describe( 'ApiEntityLabelRepository', () => {

	const wellFormedSameLanguageEnResponse = { entities: {
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
	} };

	it( 'returns well-formed Term for response in very language asked for', () => {
		const api = mockApi( wellFormedSameLanguageEnResponse );
		const entityLabelRepository = new ApiEntityLabelRepository( 'en', api );

		const expectedTerm = { language: 'en', value: 'Andromeda Galaxy' };

		return expect( entityLabelRepository.getLabel( 'Q1141' ) )
			.resolves
			.toStrictEqual( expectedTerm );
	} );

	it( 'returns well-formed Term for response using language fallback chain', () => {
		const dispatcher = mockApi( { entities: {
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
		} } );
		const entityLabelRepository = new ApiEntityLabelRepository( 'de', dispatcher );

		const expectedTerm = { language: 'en', value: 'Andromeda Galaxy' };

		return expect( entityLabelRepository.getLabel( 'Q1141' ) )
			.resolves
			.toStrictEqual( expectedTerm );
	} );

	it( 'makes API request with the correct parameters', () => {
		const api = mockApi( wellFormedSameLanguageEnResponse );
		jest.spyOn( api, 'get' );
		const id = 'Q1141';
		const languageFor = 'en';

		const entityLabelRepository = new ApiEntityLabelRepository( languageFor, api );

		return entityLabelRepository.getLabel( id ).then(
			() => {
				expect( api.get ).toHaveBeenCalledTimes( 1 );
				expect( api.get ).toHaveBeenCalledWith( {
					action: 'wbgetentities',
					props: new Set( [ 'labels' ] ),
					ids: new Set( [ id ] ),
					languages: new Set( [ languageFor ] ),
					languagefallback: true,
					errorformat: 'raw',
					formatversion: 2,
				} );
			},
		);
	} );

	describe( 'if there is a problem', () => {

		it( 'rejects on item which does not contain label in given language', () => {
			const api = mockApi( { entities: {
				Q4711: {
					type: 'item',
					id: 'Q4711',
					labels: {},
				},
			} } );
			const entityLabelRepository = new ApiEntityLabelRepository( 'de', api );

			return expect( entityLabelRepository.getLabel( 'Q4711' ) )
				.rejects
				.toStrictEqual(
					new EntityWithoutLabelInLanguageException( 'Could not find label for language \'de\'.' ),
				);
		} );

		it( 'detects no-such-entity error', () => {
			const api = mockApi( undefined, new ApiErrors( [ {
				code: 'no-such-entity',
				// info, id omitted
			} ] ) );
			const entityLabelRepository = new ApiEntityLabelRepository( 'de', api );

			return expect( entityLabelRepository.getLabel( 'Q4711' ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'passes through other API errors', () => {
			const originalError = new Error( 'I failed 😢' );
			const api = mockApi( undefined, originalError );
			const entityLabelRepository = new ApiEntityLabelRepository( 'de', api );

			return expect( entityLabelRepository.getLabel( 'Q4711' ) )
				.rejects
				.toBe( originalError );
		} );
	} );

} );
