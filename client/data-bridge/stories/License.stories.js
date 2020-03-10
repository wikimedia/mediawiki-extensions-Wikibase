import { storiesOf } from '@storybook/vue';
import License from '@/presentation/components/License';

storiesOf( 'License', module )
	.addParameters( { component: License } )
	.add( 'default', () => ( {
		components: { License },
		template:
			`<License
			/>`,
	} ) );
