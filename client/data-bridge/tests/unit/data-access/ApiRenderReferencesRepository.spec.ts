import { Reference } from '@wmde/wikibase-datamodel-types';
import { mockApi } from '../../util/mocks';
import ApiRenderReferencesRepository from '@/data-access/ApiRenderReferencesRepository';
import Api, { ReadingApi } from '@/definitions/data-access/Api';
import { MwApi } from '@/@types/mediawiki/MwWindow';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

describe( 'ApiRenderReferencesRepository', function () {

	const wellFormedReferenceResponse = {
		wbformatreference: {
			html: '<span>Reference</span>',
		},
	};

	it( 'makes API request with the correct parameters', () => {
		const language = 'de';
		const api = mockApi( wellFormedReferenceResponse );
		jest.spyOn( api, 'get' );
		const references: Reference[] = [
			{
				hash: 'abc',
				'snaks-order': [],
				snaks: {},
			},
		];

		const renderReferencesRepository = new ApiRenderReferencesRepository( api, language );

		return renderReferencesRepository.getRenderedReferences( references ).then(
			( result ) => {
				expect( api.get ).toHaveBeenCalledTimes( 1 );
				expect( api.get ).toHaveBeenCalledWith( {
					action: 'wbformatreference',
					reference: JSON.stringify( references[ 0 ] ),
					style: 'internal-data-bridge',
					outputformat: 'html',
					errorformat: 'raw',
					formatversion: 2,
					uselang: language,
				} );
				expect( result ).toStrictEqual( [
					wellFormedReferenceResponse.wbformatreference.html,
				] );
			},
		);
	} );

	it( 'makes multiple calls for multiple references and returns results in stable order', async () => {
		const language = 'de';
		const api = {
			get: jest.fn( ( parameters ) => {
				return Promise.resolve( {
					wbformatreference: {
						html: JSON.parse( parameters.reference as string ).hash,
					},
				} );
			} ),
		} as ReadingApi;
		const references: Reference[] = [
			{
				hash: 'ref1',
				'snaks-order': [],
				snaks: {},
			},
			{
				hash: 'ref2',
				'snaks-order': [],
				snaks: {},
			},
			{
				hash: 'ref3',
				'snaks-order': [],
				snaks: {},
			},
		];

		const renderReferencesRepository = new ApiRenderReferencesRepository( api, language );

		const formattedReferences = await renderReferencesRepository.getRenderedReferences( references );

		expect( api.get ).toHaveBeenCalledTimes( 3 );
		expect( api.get ).toHaveBeenCalledWith( {
			action: 'wbformatreference',
			reference: JSON.stringify( references[ 0 ] ),
			style: 'internal-data-bridge',
			outputformat: 'html',
			errorformat: 'raw',
			formatversion: 2,
			uselang: language,
		} );
		expect( api.get ).toHaveBeenCalledWith( {
			action: 'wbformatreference',
			reference: JSON.stringify( references[ 1 ] ),
			style: 'internal-data-bridge',
			outputformat: 'html',
			errorformat: 'raw',
			formatversion: 2,
			uselang: language,
		} );
		expect( api.get ).toHaveBeenCalledWith( {
			action: 'wbformatreference',
			reference: JSON.stringify( references[ 2 ] ),
			style: 'internal-data-bridge',
			outputformat: 'html',
			errorformat: 'raw',
			formatversion: 2,
			uselang: language,
		} );

		expect( formattedReferences ).toStrictEqual( [ 'ref1', 'ref2', 'ref3' ] );
	} );

	describe( 'if something goes wrong', function () {
		it( 'rejects if any of the requests fail and passes the API error through', () => {
			function mockApi( successObject?: unknown, rejectData?: unknown ): Api & MwApi {
				return {
					get: jest.fn()
						.mockReturnValue( Promise.reject( rejectData ) )
						.mockReturnValueOnce( Promise.resolve( successObject ) )
						.mockReturnValueOnce( Promise.resolve( successObject ) ),
				} as any;
			}

			const apiError = {};
			const api = mockApi( wellFormedReferenceResponse, apiError );
			const references: Reference[] = [
				{
					hash: 'ref1',
					'snaks-order': [],
					snaks: {},
				},
				{
					hash: 'ref2',
					'snaks-order': [],
					snaks: {},
				},
				{
					hash: 'ref3',
					'snaks-order': [],
					snaks: {},
				},
			];

			const renderReferencesRepository = new ApiRenderReferencesRepository( api, 'de' );
			return expect( renderReferencesRepository.getRenderedReferences( references ) ).rejects.toBe( apiError );
		} );

		it( 'rejects if the result is not well formed', () => {
			const api = mockApi( { wbformatreference: { wikitext: 'heh' } } );
			const references: Reference[] = [
				{
					hash: 'abc',
					'snaks-order': [],
					snaks: {},
				},
			];

			const renderReferencesRepository = new ApiRenderReferencesRepository( api, 'de' );
			return expect( renderReferencesRepository.getRenderedReferences( references ) ).rejects
				.toStrictEqual( new TechnicalProblem( 'Reference formatting server response not well formed.' ) );
		} );
	} );
} );
