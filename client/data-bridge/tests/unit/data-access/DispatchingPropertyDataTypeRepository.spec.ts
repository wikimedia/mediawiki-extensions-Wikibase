import DispatchingPropertyDataTypeRepository from '@/data-access/DispatchingPropertyDataTypeRepository';
import mockEntityInfoDispatcher from './mockEntityInfoDispatcher';
import DataType from '@/datamodel/DataType';

describe( 'DispatchingPropertyDataTypeRepository', () => {

	it( 'returns well-formed datatype', () => {
		const id = 'P1141';
		const expectedDatatype = 'string';
		const dispatcher = mockEntityInfoDispatcher( {
			[ id ]: {
				type: 'property',
				id,
				datatype: expectedDatatype,
			},
		} );
		const dataTypeRepository = new DispatchingPropertyDataTypeRepository( dispatcher );

		return dataTypeRepository.getDataType( id ).then(
			( actualDatatype: DataType ) => {
				expect( actualDatatype ).toStrictEqual( expectedDatatype );
			},
		);
	} );

	it( 'adds request to the dispatcher with the correct parameters', () => {
		const id = 'P1141';
		const dispatcher = mockEntityInfoDispatcher( {
			[ id ]: {
				type: 'property',
				id,
				datatype: 'string',
			},
		} );
		jest.spyOn( dispatcher, 'dispatchEntitiesInfoRequest' );

		const dataTypeRepository = new DispatchingPropertyDataTypeRepository( dispatcher );

		return dataTypeRepository.getDataType( id ).then(
			() => {
				expect( dispatcher.dispatchEntitiesInfoRequest ).toHaveBeenCalledTimes( 1 );
				expect( dispatcher.dispatchEntitiesInfoRequest ).toHaveBeenCalledWith( {
					ids: [ id ],
					props: [ 'datatype' ],
				} );
			},
		);
	} );

	describe( 'if there is a problem', () => {

		it( 'rejects if the dispatcher encountered an error', () => {
			const originalError = new Error( 'I failed 😢' );
			const dispatcher = mockEntityInfoDispatcher( null, originalError );

			const datatypeRepository = new DispatchingPropertyDataTypeRepository( dispatcher );
			return expect( datatypeRepository.getDataType( 'P123' ) )
				.rejects
				.toStrictEqual(
					originalError,
				);
		} );
	} );
} );
