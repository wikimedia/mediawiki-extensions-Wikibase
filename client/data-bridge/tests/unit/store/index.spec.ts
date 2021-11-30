import { createStore } from '@/store';
import newMockServiceContainer from '../services/newMockServiceContainer';

describe( 'store/index', () => {
	it( 'creates the store', () => {
		const store = createStore( newMockServiceContainer( {} ) );
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
		expect( store.state.targetProperty ).toBe( '' );
		expect( store.state.renderedTargetReferences ).toStrictEqual( [] );
		expect( store.state.editFlow ).toBe( '' );
		expect( store.state.editDecision ).toBeNull();
		expect( store.state.entityTitle ).toBe( '' );
		expect( store.state.originalHref ).toBe( '' );
	} );
} );
