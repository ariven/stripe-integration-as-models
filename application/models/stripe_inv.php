<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * assumes latest Stripe api library is installed in APPPATH.'third_party'
	 */
	require_once(APPPATH . 'third_party/stripe.php');

	class Stripe_inv extends CI_Model
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
		 * Initializes object
		 *
		 * @param array $config
		 */
		public function init($config = array())
		{
			if (isset($config['private_key']))
			{
				$this->private_key = $config['private_key'];
			}
			$this->set_api_key();
		}

		/**
		 * Gets specified invoice
		 * @param $invoice_id
		 * @return mixed
		 */
		public function get($invoice_id)
		{
			try
			{
				$ch = Stripe_Invoice::retrieve($invoice_id);
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
		 * gets all invoices, up to max of 100 at a time.
		 *
		 * @param int $num_invoices
		 * @param int $offset
		 * @return array|bool
		 */
		public function get_all($num_invoices = 100, $offset = 0)
		{
			try
			{
				$ch = Stripe_Invoice::all(array(
											   'count'  => $num_invoices,
											   'offset' => $offset
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
		 * Gets all invoices for specified customer, up to max of 100 at a time
		 *
		 * @param $customer_id
		 * @param int $num_invoices
		 * @param int $offset
		 * @return array|bool
		 */
		public function get_all_customer($customer_id, $num_invoices = 100, $offset = 0)
		{
			try
			{
				$ch = Stripe_Invoice::all(array(
											   'customer' => $customer_id,
											   'count'    => $num_invoices,
											   'offset'   => $offset
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
		 * Gets upcoming invoice for customer
		 * @param $customer_id
		 * @return array|bool
		 */
		public function get_upcoming_customer($customer_id)
		{
			try
			{
				$ch = Stripe_Invoice::upcoming(array('customer' => $customer_id));
				return $ch;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
		}
	}