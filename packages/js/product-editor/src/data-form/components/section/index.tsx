/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { SectionHeader } from '../../../components/section-header';

type ProductSectionProps = {
	sectionTemplate: Template;
	postType: string;
};

export function ProductSection( {
	sectionTemplate,
	postType,
}: ProductSectionProps ) {
	const { description, title, blockGap } = sectionTemplate[ 1 ] as {
		description: string;
		title: string;
		blockGap: string;
	};

	const nestedClassNames = classNames(
		'wp-block-woocommerce-product-section-header__content',
		`wp-block-woocommerce-product-section-header__content--block-gap-${ blockGap }`
	);
	const SectionTagName = title ? 'fieldset' : 'div';

	return (
		<SectionTagName>
			{ title && (
				<SectionHeader
					description={ description }
					sectionTagName={ SectionTagName }
					title={ title }
				/>
			) }

			<div className={ nestedClassNames }>
				<p>
					Render DataForm with { sectionTemplate[ 2 ]?.length } fields
				</p>
				<ul>
					{ sectionTemplate[ 2 ]?.map( ( field ) => (
						<li key={ field[ 0 ] }>{ field[ 0 ] }</li>
					) ) }
				</ul>
			</div>
		</SectionTagName>
	);
}
