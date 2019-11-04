import DispatchingEntityLabelRepository from '@/data-access/DispatchingEntityLabelRepository';
import Term from '@/datamodel/Term';
import EntityWithoutLabelInLanguageException from '@/data-access/error/EntityWithoutLabelInLanguageException';
import mockEntityInfoDispatcher from './mockEntityInfoDispatcher';

describe( 'DispatchingEntityLabelRepository', () => {

	const wellFormedSameLanguageEnResponse = {
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
	};

	it( 'returns well-formed Term for response in very language asked for', () => {
		const dispatcher = mockEntityInfoDispatcher( wellFormedSameLanguageEnResponse );

		const entityLabelReader = new DispatchingEntityLabelRepository( 'en', dispatcher );

		const expectedTerm = { language: 'en', value: 'Andromeda Galaxy' };

		return entityLabelReader.getLabel( 'Q1141' ).then(
			( actualTerm: Term ) => {
				expect( actualTerm ).toStrictEqual( expectedTerm );
			},
		);
	} );

	it( 'returns well-formed Term for response using language fallback chain', () => {
		const dispatcher = mockEntityInfoDispatcher( {
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
		} );

		const entityLabelReader = new DispatchingEntityLabelRepository( 'de', dispatcher );

		const expectedTerm = { language: 'en', value: 'Andromeda Galaxy' };

		return entityLabelReader.getLabel( 'Q1141' ).then(
			( actualTerm: Term ) => {
				expect( actualTerm ).toStrictEqual( expectedTerm );
			},
		);
	} );

	it( 'adds request to the dispatcher with the correct parameters', () => {
		const dispatcher = mockEntityInfoDispatcher( wellFormedSameLanguageEnResponse );
		jest.spyOn( dispatcher, 'dispatchEntitiesInfoRequest' );
		const id = 'Q1141';
		const languageFor = 'en';

		const entityLabelReader = new DispatchingEntityLabelRepository( languageFor, dispatcher );

		return entityLabelReader.getLabel( id ).then(
			() => {
				expect( dispatcher.dispatchEntitiesInfoRequest ).toHaveBeenCalledTimes( 1 );
				expect( dispatcher.dispatchEntitiesInfoRequest ).toHaveBeenCalledWith( {
					ids: [ id ],
					props: [ 'labels' ],
					otherParams: {
						languages: languageFor,
						languagefallback: 1,
					},
				} );
			},
		);
	} );

	describe( 'if there is a problem', () => {
		it( 'rejects on property which does not contain label in given language', () => {
			const dispatcher = mockEntityInfoDispatcher( {
				Q4711: {
					type: 'item',
					id: 'Q4711',
					labels: {},
				},
			} );

			const entityLabelReader = new DispatchingEntityLabelRepository( 'de', dispatcher );
			return expect( entityLabelReader.getLabel( 'Q4711' ) )
				.rejects
				.toStrictEqual(
					new EntityWithoutLabelInLanguageException( 'Could not find label for language \'de\'.' ),
				);
		} );

		it( 'rejects if the dispatcher encountered an error', () => {
			const originalError = new Error( 'I failed 😢' );
			const dispatcher = mockEntityInfoDispatcher( null, originalError );

			const entityLabelReader = new DispatchingEntityLabelRepository( 'de', dispatcher );
			return expect( entityLabelReader.getLabel( 'Q4711' ) )
				.rejects
				.toStrictEqual(
					originalError,
				);
		} );
	} );
} );
