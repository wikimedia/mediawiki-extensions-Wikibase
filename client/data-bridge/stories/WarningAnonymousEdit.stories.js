import { storiesOf } from '@storybook/vue';
import WarningAnonymousEdit from '@/presentation/components/WarningAnonymousEdit';

storiesOf( 'WarningAnonymousEdit', module )
	.addParameters( { component: WarningAnonymousEdit } )
	.add( 'default', () => ( {
		components: { WarningAnonymousEdit },
		template: `<div style="max-width: 550px; max-height: 550px; border: 1px solid black;">
			<WarningAnonymousEdit login-url="https://example.com"/>
		</div>`,
	} ) );
