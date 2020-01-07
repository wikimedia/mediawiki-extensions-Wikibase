import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import Application from '@/store/Application';
import { mutations } from '@/store/mutations';
import {
	APPLICATION_ERRORS_ADD,
	APPLICATION_STATUS_SET,
	EDITDECISION_SET,
	EDITFLOW_SET,
	ORIGINAL_STATEMENT_SET,
	PROPERTY_TARGET_SET,
	TARGET_LABEL_SET,
} from '@/store/mutationTypes';
import newApplicationState from './newApplicationState';

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

	it( 'changes the originalStatement of the store', () => {
		const targetProperty = {
			type: 'statement',
			id: 'opaque statement ID',
			rank: 'normal',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P60',
				datatype: 'string',
			},
		};
		const store: Application = newApplicationState();
		mutations[ ORIGINAL_STATEMENT_SET ]( store, targetProperty );
		expect( store.originalStatement ).not.toBe( targetProperty );
		expect( store.originalStatement ).toStrictEqual( targetProperty );
	} );

	it( 'adds errors to the store', () => {
		const store: Application = newApplicationState();
		const errors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];
		mutations[ APPLICATION_ERRORS_ADD ]( store, errors );
		expect( store.applicationErrors ).toStrictEqual( errors );
	} );

	it( 'does not drop existing errors when adding new ones to the store', () => {
		const oldErrors: ApplicationError[] = [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ];
		const store: Application = newApplicationState( { applicationErrors: oldErrors.slice() } );
		const newErrors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];
		mutations[ APPLICATION_ERRORS_ADD ]( store, newErrors );
		expect( store.applicationErrors ).toStrictEqual( [ ...oldErrors, ...newErrors ] );
	} );

	it( 'sets the edit decision of the store', () => {
		const store: Application = newApplicationState();
		const editDecision = EditDecision.REPLACE;
		mutations[ EDITDECISION_SET ]( store, editDecision );
		expect( store.editDecision ).toBe( editDecision );
	} );
} );
