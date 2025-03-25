/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './payment-settings-section.scss';

export const PaymentSettingsSection: React.FunctionComponent< {
	children?: React.ReactNode;
	description: string;
	title: string;
	id?: string;
} > = ( { title, description, children, id } ) => (
	<div className={ 'payment-settings-section' } id={ id }>
		<div className="payment-settings-section__details">
			<h2>{ title }</h2>
			<p>{ description }</p>
		</div>
		<div className="payment-settings-section__controls">{ children }</div>
	</div>
);
