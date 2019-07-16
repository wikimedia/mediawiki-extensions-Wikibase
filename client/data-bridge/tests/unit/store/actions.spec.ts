import { actions } from '@/store/actions';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
} from '@/store/mutationTypes';
import newMockStore from './newMockStore';

describe( 'root/actions', () => {
	describe( BRIDGE_INIT, () => {
		it( `commits to ${EDITFLOW_SET}`, () => {
			const editFlow = 'Heraklid';
			const context = newMockStore( {
				commit: jest.fn(),
			} );

			actions[ BRIDGE_INIT ]( context, { editFlow, targetProperty: '' } );
			expect( context.commit ).toBeCalledWith(
				EDITFLOW_SET,
				editFlow,
			);
		} );

		it( `commits to ${PROPERTY_TARGET_SET}`, () => {
			const targetProperty = 'P42';
			const context = newMockStore( {
				commit: jest.fn(),
			} );

			actions[ BRIDGE_INIT ]( context, { editFlow: '', targetProperty } );
			expect( context.commit ).toBeCalledWith(
				PROPERTY_TARGET_SET,
				targetProperty,
			);
		} );
	} );
} );
