import { storiesOf } from '@storybook/vue';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser';
import useStore from './useStore';
import { ErrorTypes } from '@/definitions/ApplicationError';

storiesOf( 'ErrorSavingAssertUser', module )
	.addParameters( { component: ErrorSavingAssertUser } )
	.addDecorator( useStore( {
		applicationErrors: [ {
			type: ErrorTypes.ASSERT_USER_FAILED,
		} ],
	} ) )
	.add( 'default', () => ( {
		components: { ErrorSavingAssertUser },
		template: `<div style="max-width: 550px; max-height: 550px; border: 1px solid black;">
			<ErrorSavingAssertUser login-url="https://example.com"/>
		</div>`,
	} ) );
