/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './payment-settings-section.scss';

export const PaymentSettingsSection = ( {
	title,
	description,
	children,
	id,
}: {
	title: string;
	description: string;
	children?: React.ReactNode;
	id?: string;
} ) => (
	<div className={ 'payment-settings-section' } id={ id }>
		<div className="payment-settings-section__details">
			<h2>{ title }</h2>
			<p>{ description }</p>
		</div>
		<div className="payment-settings-section__controls">{ children }</div>
	</div>
);
