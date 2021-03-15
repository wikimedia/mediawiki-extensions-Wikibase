import ErrorUnknown from '@/presentation/components/ErrorUnknown';
import useStore from './useStore';
import { ErrorTypes } from '@/definitions/ApplicationError';

export default {
	title: 'ErrorUnknown',
	component: ErrorUnknown,
	decorators: [
		useStore( {
			applicationErrors: [ {
				type: ErrorTypes.APPLICATION_LOGIC_ERROR,
				info: {
					stack: 'this is the stack trace',
				},
			} ],
		} ),
	],
};

export function normal() {
	return {
		components: { ErrorUnknown },
		template: '<ErrorUnknown />',
	};
}
