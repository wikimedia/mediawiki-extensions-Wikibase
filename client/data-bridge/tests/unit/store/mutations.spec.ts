import EditFlow from '@/definitions/EditFlow';
import { mutations } from '@/store/mutations';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
	TARGET_LABEL_SET,
} from '@/store/mutationTypes';
import Application from '@/store/Application';
import newApplicationState from './newApplicationState';
import ApplicationStatus from '@/definitions/ApplicationStatus';

describe( 'root/mutations', () => {
	it( 'changes the targetProperty of the store', () => {
		const store: Application = newApplicationState();
		mutations[ PROPERTY_TARGET_SET ]( store, 'P42' );
		expect( store.targetProperty ).toBe( 'P42' );
	} );

	it( 'changes the editFlow of the store', () => {
		const store: Application = newApplicationState(),
			editFlow: EditFlow = EditFlow.OVERWRITE;
		mutations[ EDITFLOW_SET ]( store, editFlow );
		expect( store.editFlow ).toBe( editFlow );
	} );

	it( 'changes the applicationStatus of the store', () => {
		const store: Application = newApplicationState();
		mutations[ APPLICATION_STATUS_SET ]( store, ApplicationStatus.READY );
		expect( store.applicationStatus ).toBe( ApplicationStatus.READY );
	} );

	it( 'changes the targetLabel of the store', () => {
		const targetLabel = { language: 'el', value: 'πατατα' };
		const store: Application = newApplicationState();
		mutations[ TARGET_LABEL_SET ]( store, targetLabel );
		expect( store.targetLabel ).toBe( targetLabel );
	} );
} );
