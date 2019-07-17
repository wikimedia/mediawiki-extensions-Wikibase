import { actions } from '@/store/actions';
import {
	NS_ENTITY,
} from '@/store/namespaces';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET, APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import newMockStore from './newMockStore';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import ApplicationStatus from '@/store/ApplicationStatus';

describe( 'root/actions', () => {
	describe( BRIDGE_INIT, () => {

		it( `commits to ${EDITFLOW_SET}`, () => {
			const editFlow = 'Heraklid';
			const context = newMockStore( {} );

			return actions[ BRIDGE_INIT ]( context, {
				editFlow,
				targetProperty: '',
				targetEntity: '',
			} ).then( () => {
				expect( context.commit ).toBeCalledWith(
					EDITFLOW_SET,
					editFlow,
				);
			} );
		} );

		it( `commits to ${PROPERTY_TARGET_SET}`, () => {
			const targetProperty = 'P42';
			const context = newMockStore( {} );

			return actions[ BRIDGE_INIT ]( context, {
				editFlow: '',
				targetProperty,
				targetEntity: '',
			} ).then( () => {
				expect( context.commit ).toBeCalledWith(
					PROPERTY_TARGET_SET,
					targetProperty,
				);
			} );
		} );

		it( `dispatches to ${namespacedStoreEvent( NS_ENTITY, ENTITY_INIT )}$`, () => {
			const targetEntity = 'Q42';
			const context = newMockStore( {} );

			return actions[ BRIDGE_INIT ]( context, {
				editFlow: '',
				targetProperty: '',
				targetEntity,
			} ).then( () => {
				expect( context.dispatch ).toBeCalledWith(
					namespacedStoreEvent( NS_ENTITY, ENTITY_INIT ),
					{ 'entity': targetEntity },
				);
			} );
		} );

		it( `commits to ${APPLICATION_STATUS_SET} on successful entity lookup`, () => {
			const context = newMockStore( {} );

			return actions[ BRIDGE_INIT ]( context, {
				editFlow: 'overwrite',
				targetProperty: 'P123',
				targetEntity: 'Q123',
			} ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith( APPLICATION_STATUS_SET, ApplicationStatus.READY );
			} );
		} );

		it( `commits to ${APPLICATION_STATUS_SET} on fail entity lookup`, () => {
			const context = newMockStore( {
				dispatch: () => Promise.reject(),
			} );

			return actions[ BRIDGE_INIT ]( context, {
				editFlow: 'overwrite',
				targetProperty: 'P123',
				targetEntity: 'Q123',
			} ).catch( () => {
				expect( context.commit ).toHaveBeenCalledWith( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
			} );
		} );

	} );
} );
