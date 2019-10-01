import subscribeToEvents from '@/mediawiki/subscribeToEvents';
import Events from '@/events';

describe( 'subscribeToEvents', () => {
	describe( Events.onSaved, () => {
		it( 'subscribes EventEmitter on saved', () => {
			const emitter = { on: jest.fn() };

			subscribeToEvents( emitter as any, { on: jest.fn() } as any );
			expect( emitter.on.mock.calls[ 0 ][ 0 ] ).toBe( Events.onSaved );
		} );

		it( 'subscribes EventEmitter on cancel', () => {
			const emitter = { on: jest.fn() };

			subscribeToEvents( emitter as any, { on: jest.fn() } as any );
			expect( emitter.on.mock.calls[ 1 ][ 0 ] ).toBe( Events.onCancel );
		} );

		it( 'subcribes WindowManger on closing', () => {
			const windowManager = { on: jest.fn() };

			subscribeToEvents( { on: jest.fn() } as any, windowManager as any );
			expect( windowManager.on.mock.calls[ 0 ][ 0 ] ).toBe( 'closing' );
		} );
	} );
} );
