import { storiesOf } from '@storybook/vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown';
import useStore from './useStore';
import { ErrorTypes } from '@/definitions/ApplicationError';

storiesOf( 'ErrorUnknown', module )
	.addParameters( { component: ErrorUnknown } )
	.addDecorator( useStore( {
		applicationErrors: [ {
			type: ErrorTypes.APPLICATION_LOGIC_ERROR,
			info: {
				stack: 'this is the stack trace',
			},
		} ],
	} ) )
	.add( 'default', () => ( {
		components: { ErrorUnknown },
		template: '<ErrorUnknown />',
	} ) );
