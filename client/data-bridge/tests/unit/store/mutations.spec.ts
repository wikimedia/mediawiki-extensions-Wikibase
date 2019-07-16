import { mutations } from '@/store/mutations';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
} from '@/store/mutationTypes';
import lockState from './lockState';
import Application from '@/store/Application';

function makeApplicationState( fields?: any ): Application {
	let AppState: Application = {
		targetProperty: '',
		editFlow: '',
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
		lockState( AppState );
	}

	return AppState;
}

describe( 'root/mutations', () => {
	it( 'changes the targetProperty of the store', () => {
		const store: Application = makeApplicationState();
		mutations[ PROPERTY_TARGET_SET ]( store, 'P42' );
		expect( store.targetProperty ).toBe( 'P42' );
	} );

	it( 'changes the editFlow of the store', () => {
		const store: Application = makeApplicationState();
		mutations[ EDITFLOW_SET ]( store, 'Heraklid' );
		expect( store.editFlow ).toBe( 'Heraklid' );
	} );
} );
