import { storiesOf } from '@storybook/vue';
import BailoutActions from '@/presentation/components/BailoutActions';

storiesOf( 'BailoutActions', module )
	.addParameters( { component: BailoutActions } )
	.add( 'default', () => ( {
		components: { BailoutActions },
		template:
			`<BailoutActions
				original-href="https://repo.wiki.example/wiki/Item:Q42?uselang=en"
				page-title="Client page"
			/>`,
	} ) );
