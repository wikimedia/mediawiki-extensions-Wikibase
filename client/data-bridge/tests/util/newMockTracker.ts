import BridgeTracker from '@/definitions/data-access/BridgeTracker';

export default function ( methods?: Partial<BridgeTracker> ): BridgeTracker {
	return {
		...{
			trackPropertyDatatype: jest.fn(),
		},
		...methods,
	};
}
