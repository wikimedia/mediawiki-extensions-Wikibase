import subscribeToEvents from '@/mediawiki/subscribeToEvents';
import Events from '@/events';

describe( 'subscribeToEvents', () => {
	describe( Events.onSaved, () => {
		const emitter = { on: jest.fn() };
		const mwWindow = { reload: jest.fn() };

		it( 'subscribes EventEmitter on saved', () => {
			subscribeToEvents( emitter as any, { on: jest.fn() } as any, mwWindow as any );
			expect( emitter.on.mock.calls[ 0 ][ 0 ] ).toBe( Events.onSaved );
		} );

		it( 'subscribes EventEmitter on cancel', () => {
			subscribeToEvents( emitter as any, { on: jest.fn() } as any, mwWindow as any );
			expect( emitter.on.mock.calls[ 1 ][ 0 ] ).toBe( Events.onCancel );
		} );

		it( 'subcribes WindowManger on closing', () => {
			const windowManager = { on: jest.fn() };
			subscribeToEvents( { on: jest.fn() } as any, windowManager as any, mwWindow as any );
			expect( windowManager.on.mock.calls[ 0 ][ 0 ] ).toBe( 'closing' );
		} );
	} );
} );
