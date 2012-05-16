<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * assumes latest Stripe api library is installed in APPPATH.'third_party'
	 */
	require_once(APPPATH . 'third_party/Stripe.php');

	class Stripe_events extends CI_Model
	{

		protected $private_key; // private key for accessing stripe account
		public $message = ''; // used to hand messages back in case of error
		public $code; // error code, when saved on errors
		public $error = FALSE; // used to hand error condition back if you dont use array returns

		/**
		 * constructor
		 */
		public function __construct()
		{
			parent::__construct();
			$this->config->load('charge');
			$this->private_key = $this->config->item('stripe_secret_key');
			$this->set_api_key();
		}

		/**
		 * sets the private API key to enable transactions
		 */
		function set_api_key()
		{
			Stripe::setApiKey($this->private_key);
		}


		/**
		 * retrieves an event
		 *
		 * @param $event_id unique identifier of event, i.e. from a webhook
		 */
		function get($event_id)
		{
			try
			{
				$ch = Stripe_Event::retrieve($event_id);
				return $ch;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}

		}

		/**
		 * Get last (n) events up to at least last 30 days worth. Data returned is sorted with most recent first.
		 * Maximum is 100 items, per Stripe
		 *
		 * @param int $num_events number of events to retrieve
		 * @param int $offset     offset in the event list
		 * @param string $type    event type, or * for all
		 * @param string $created either exact UTC timestamp, or associative array with gt, gte, lt, lte and timestamp
		 *                        for filtering on time (i.e.  array('gt' => 1000) would be all records later than 1000 UTC timestamp
		 * @return array - function returns associative array from stripe, we cant get objects
		 */
		public function get_all($num_events = 100, $offset = 0, $type = '*', $created = FALSE)
		{
			try
			{
				$ch = Stripe_Event::all(array(
											 'count'   => $num_events,
											 'offset'  => $offset,
											 'type'    => $type,
											 'created' => ($created) ? $created : array('gt' => 0),
										));
				return $ch;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
		}


		/**
		 * @param $event_type event type
		 * @return string description of what an event is.
		 */
		public function event_description($event_type)
		{
			switch ($event_type)
			{
				case 'charge.succeeded':
					return 'Occurs whenever a new charge is created and is successful.';
					break;
				case 'charge.failed':
					return 'Occurs whenever a failed charge attempt occurs.';
					break;
				case 'charge.refunded':
					return 'Occurs whenever a charge is refunded, including partial refunds.';
					break;
				case 'charge.disputed':
					return 'Occurs whenever someone disputes a charge with their bank (chargeback).';
					break;
				case 'customer.created':
					return 'Occurs whenever a new customer is created.';
					break;
				case 'customer.updated':
					return 'Occurs whenever any property of a customer changes.';
					break;
				case 'customer.deleted':
					return 'Occurs whenever a customer is deleted.';
					break;
				case 'customer.subscription.created':
					return 'Occurs whenever a customer with no subscription is signed up for a plan.';
					break;
				case 'customer.subscription.updated':
					return 'Occurs whenever a subscription changes. Examples would include switching from one plan to another, or switching status from trial to active.';
					break;
				case 'customer.subscription.deleted':
					return 'Occurs whenever a customer ends their subscription.';
					break;
				case 'customer.subscription.trial_will_end':
					return 'Occurs three days before the trial period of a subscription is scheduled to end.';
					break;
				case 'customer.discount.created':
					return 'Occurs whenever a coupon is attached to a customer.';
					break;
				case 'customer.discount.updated':
					return 'Occurs whenever a customer is switched from one coupon to another.';
					break;
				case 'customer.discount.deleted':
					return "Occurs whenever a customer's discount is removed.";
					break;
				case 'invoice.created':
					return 'Occurs whenever a new invoice is created. If you are using webhooks, Stripe will wait one hour after they have all succeeded to attempt to pay the invoice; the only exception here is on the first invoice, which gets created and paid immediately when you subscribe a customer to a plan. If your webhooks do not all respond successfully, Stripe will continue retrying the webhooks every hour and will not attempt to pay the invoice. After 3 days, Stripe will attempt to pay the invoice regardless of whether or not your webhooks have succeeded. See how to respond to a webhook.';
					break;
				case 'invoice.updated':
					return 'Occurs whenever an invoice changes (for example, the amount could change).';
					break;
				case 'invoice.payment_succeeded':
					return 'Occurs whenever an invoice attempts to be paid, and the payment succeeds.';
					break;
				case 'invoice.payment_failed':
					return 'Occurs whenever an invoice attempts to be paid, and the payment fails.';
					break;
				case 'invoiceitem.created':
					return 'Occurs whenever an invoice item is created.  ';
					break;
				case 'invoiceitem.updated':
					return 'Occurs whenever an invoice item is updated.';
					break;
				case 'invoiceitem.deleted':
					return 'Occurs whenever an invoice item is deleted.';
					break;
				case 'plan.created':
					return 'Occurs whenever a plan is created.';
					break;
				case 'plan.updated':
					return 'Occurs whenever a plan is updated.';
					break;
				case 'plan.deleted':
					return 'Occurs whenever a plan is deleted.';
					break;
				case 'coupon.created':
					return 'Occurs whenever a coupon is created.';
					break;
				case 'coupon.updated':
					return 'Occurs whenever a coupon is updated.';
					break;
				case 'coupon.deleted':
					return 'Occurs whenever a coupon is deleted.';
					break;
				case 'transfer.created':
					return 'Occurs whenever a new transfer is created.';
					break;
				case 'transfer.failed':
					return 'Occurs whenever Stripe attempts to send a transfer and that transfer fails.';
					break;
				case 'ping':
					return 'May be sent by Stripe at any time to see if a provided webhook URL is working.';
					break;
				default:
					return 'Unknown event type';
					break;
			}
		}
	}
