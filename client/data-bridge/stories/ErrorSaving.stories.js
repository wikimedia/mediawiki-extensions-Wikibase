import ErrorSaving from '@/presentation/components/ErrorSaving';
import useStore from './useStore';
import { ErrorTypes } from '@/definitions/ApplicationError';

export default {
	title: 'ErrorSaving',
	component: ErrorSaving,
	decorators: [
		useStore( {
			applicationErrors: [ {
				type: ErrorTypes.SAVING_FAILED,
			} ],
			config: { issueReportingLink: 'https://example.com' },
		} ),
	],
};

export function normal() {
	return {
		components: { ErrorSaving },
		template: '<ErrorSaving />',
	};
}
