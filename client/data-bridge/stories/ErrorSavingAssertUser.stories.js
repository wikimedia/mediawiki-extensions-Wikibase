import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser';
import useStore from './useStore';
import { ErrorTypes } from '@/definitions/ApplicationError';

export default {
	title: 'ErrorSavingAssertUser',
	component: ErrorSavingAssertUser,
	decorators: [
		useStore( {
			applicationErrors: [ {
				type: ErrorTypes.ASSERT_USER_FAILED,
			} ],
			config: { usePublish: true },
		} ),
	],
};

export function normal() {
	return {
		components: { ErrorSavingAssertUser },
		template: `<div style="max-width: 550px; max-height: 550px; border: 1px solid black;">
			<ErrorSavingAssertUser login-url="https://example.com"/>
		</div>`,
	};
}
