/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import classNames from 'classnames';
import { Product } from '@woocommerce/data';
import { DataForm, DataFormProps } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { SectionHeader } from '../../../components/section-header';

type ProductSectionProps = {
	sectionTemplate: Template;
	postType: string;
} & Omit< DataFormProps< Product >, 'fields' | 'form' >;

const fields = [
	{
		id: 'product-name-field',
		type: 'text',
		label: 'Title',
	},
];

export function ProductSection( {
	sectionTemplate,
	postType,
	data,
	onChange,
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

	const form = useMemo( () => {
		return {
			type: 'regular' as const,
			fields: sectionTemplate[ 2 ]
				.filter(
					( field ) => field[ 0 ] === 'woocommerce/product-name-field'
				)
				.map( ( field ) => field[ 0 ].split( '/' )[ 1 ] ),
		};
	}, [ sectionTemplate ] );

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
				<DataForm
					// @ts-expect-error fields is not typed
					fields={ fields }
					form={ form }
					onChange={ onChange }
					data={ data }
				/>
			</div>
		</SectionTagName>
	);
}
