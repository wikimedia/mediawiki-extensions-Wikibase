import ApiPropertyDataTypeRepository from '@/data-access/ApiPropertyDataTypeRepository';
import ApiErrors from '@/data-access/error/ApiErrors';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import { mockApi } from '../../util/mocks';

describe( 'ApiPropertyDataTypeRepository', () => {

	it( 'returns well-formed datatype', () => {
		const id = 'P1141';
		const datatype = 'string';
		const api = mockApi( { entities: {
			[ id ]: {
				type: 'property',
				id,
				datatype,
			},
		} } );
		const dataTypeRepository = new ApiPropertyDataTypeRepository( api );

		return expect( dataTypeRepository.getDataType( id ) )
			.resolves
			.toStrictEqual( datatype );
	} );

	it( 'makes API request with the correct parameters', () => {
		const id = 'P1141';
		const api = mockApi( { entities: {
			[ id ]: {
				type: 'property',
				id,
				datatype: 'string',
			},
		} } );
		jest.spyOn( api, 'get' );
		const dataTypeRepository = new ApiPropertyDataTypeRepository( api );

		return dataTypeRepository.getDataType( id ).then(
			() => {
				expect( api.get ).toHaveBeenCalledTimes( 1 );
				expect( api.get ).toHaveBeenCalledWith( {
					action: 'wbgetentities',
					props: new Set( [ 'datatype' ] ),
					ids: new Set( [ id ] ),
					errorformat: 'raw',
					formatversion: 2,
				} );
			},
		);
	} );

	describe( 'if there is a problem', () => {

		it( 'detects no-such-entity error', () => {
			const api = mockApi( undefined, new ApiErrors( [ {
				code: 'no-such-entity',
				// info, id omitted
			} ] ) );
			const dataTypeRepository = new ApiPropertyDataTypeRepository( api );

			return expect( dataTypeRepository.getDataType( 'P123' ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'Entity flagged missing in response.' ) );
		} );

		it( 'passes through other API errors', () => {
			const originalError = new Error( 'I failed 😢' );
			const api = mockApi( undefined, originalError );
			const datatypeRepository = new ApiPropertyDataTypeRepository( api );

			return expect( datatypeRepository.getDataType( 'P123' ) )
				.rejects
				.toBe( originalError );
		} );

	} );

} );
