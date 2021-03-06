<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * assumes latest Stripe api library is installed in APPPATH.'third_party'
	 */
	require_once(APPPATH . 'third_party/stripe.php');

	class Stripe_trans extends CI_Model
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

		public function init($config = array())
		{
			if (isset($config['private_key']))
			{
				$this->private_key = $config['private_key'];
			}
			$this->set_api_key();

		} // init

		/**
		 * Get a single record by creating a WHERE clause with
		 * a value for your primary key
		 *
		 * @param string $primary_value The value of your primary key
		 * @return object transaction object
		 */
		public function get($transaction_id)
		{
			try
			{
				$ch = Stripe_Charge::retrieve($transaction_id);
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
		 * Get last (n) charges up to 100 (default).  Cannot return more than 100 at a time.   Data
		 * returned is sorted with most recent first.
		 *
		 * @return array - function returns associative array from stripe, we cant get objects
		 */
		public function get_all($num_charges = 100, $offset = 0)
		{
			try
			{
				$ch            = Stripe_Charge::all(array(
														 'count'  => $num_charges,
														 'offset' => $offset
													));
				$data['error'] = FALSE;
				$raw_data      = array();
				foreach ($ch->data as $record)
				{
					$raw_data[] = $this->charge_to_array($record);
				}
				$data['data'] = $raw_data;
				return $data;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
		}

		public function get_all_customer($customer, $num_charges = 10, $offset = 0)
		{
			try
			{
				$ch       = Stripe_Charge::all(array(
													'count'    => $num_charges,
													'offset'   => $offset,
													'customer' => $customer
											   ));
				$raw_data = array();
				foreach ($ch->data as $record)
				{
					$raw_data[] = $record;
				}
				if (count($raw_data) > 0)
				{
					return $raw_data;
				}
				else
				{
					return FALSE;
				}
			}
			catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
		}

		/**
		 * Return a count of every transaction, up to the last 100!
		 *
		 * @return integer
		 */
		public function count_all()
		{
			$charges = $this->get_all();
			return count($charges);
		}


		/**
		 * inserts a transaction into the system.  i.e. a charge of a card.
		 *
		 * @param string $token       token generated by a call to stripe to create a token,  usually by the
		 *                            javascript library
		 * @param string $description name/email, etc used to describe this charge
		 * @param INT $amount         Amount in PENNIES to charge
		 * @return object charge object or FALSE if failed
		 */
		public function insert($token, $description, $amount)
		{
			try
			{
				$charge = Stripe_Charge::create(array(
													 'amount'      => $amount,
													 'currency'    => 'usd',
													 'card'        => $token,
													 'description' => $description
												));
				return $charge;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
		}

		/**
		 * wrapper for function insert
		 *
		 * @param string $token
		 * @param string $description
		 * @param int $amount amount in pennies
		 * @return object or boolean depending on success
		 */
		function charge($token, $description, $amount)
		{
			return $this->insert($token, $description, $amount);
		}


		/**
		 * charges using customer token instead of card token.
		 *
		 * @param string $customer    customer token from Stripe
		 * @param string $description description of this charge
		 * @param int $amount         amount in PENNIES to charge
		 * @return type
		 */
		function charge_customer($customer, $description, $amount)
		{
			try
			{
				$charge = Stripe_Charge::create(array(
													 'amount'      => $amount,
													 'currency'    => 'usd',
													 'customer'    => $customer,
													 'description' => $description
												));
				return $charge;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
		}


		/**
		 * Similar to insert(), just passing an array to insert
		 * multiple rows at once. Returns an array of insert IDs.
		 *
		 * @param array $data Array of arrays to insert
		 * @return array
		 */
		public function insert_many($data)
		{
			$ids = array();

			foreach ($data as $row)
			{
				$ids[] = $this->insert($row['token'], $row['description'], $row['amount']);
			}
			return $ids;
		}


		/**
		 * returns chunk of all members dictated by $limit and $offset
		 *
		 * @param int $limit
		 * @param int $offset
		 * @return type array/object
		 */
		public function get_limit($limit, $offset = 0)
		{
			return $this->get_all($limit, $offset);
		}

		function refund($transaction_id, $amount = 'all')
		{
			$transaction = $this->get($transaction_id);
			if ($transaction)
			{
				if ($amount == 'all')
				{
					$amount = $transaction['amount'];
				}
				try
				{
					$response = $transaction->refund(array('amount' => $amount));
					return $response;
				} catch (Exception $e)
				{
					$this->error   = TRUE;
					$this->message = $e->getMessage();
					$this->code    = $e->getCode();
					return FALSE;
				}
			}
			else
			{
				$this->error = TRUE;
				// message should be carried over from failure to retrieve transaction in the get() step
				return FALSE;
			}
		}

		function charge_to_array($charge)
		{
			$data = array(
				'id'              => $charge->id,
				'invoice'         => $charge->invoice,
				'card'            => $this->card_to_array($charge->card),
				'livemode'        => $charge->livemode,
				'amount'          => $charge->amount,
				'failure_message' => $charge->failure_message,
				'fee'             => $charge->fee,
				'currency'        => $charge->currency,
				'paid'            => $charge->paid,
				'description'     => $charge->description,
				'disputed'        => $charge->disputed,
				'object'          => $charge->object,
				'refunded'        => $charge->refunded,
				'created'         => date('Y-m-d H:i:s', $charge->created),
				'customer'        => $charge->customer,
				'amount_refunded' => $charge->amount_refunded,
			);
			return $data;
		}

		/**
		 * converts card object to array
		 *
		 * @param type $card
		 */
		function card_to_array($card)
		{
			$data = array(
				'address_country'     => $card->address_country,
				'type'                => $card->type,
				'address_zip_check'   => $card->address_zip_check,
				'fingerprint'         => $card->fingerprint,
				'address_state'       => $card->address_state,
				'exp_month'           => $card->exp_month,
				'address_line1_check' => $card->address_line1_check,
				'country'             => $card->country,
				'last4'               => $card->last4,
				'exp_year'            => $card->exp_year,
				'address_zip'         => $card->address_zip,
				'object'              => $card->object,
				'address_line1'       => $card->address_line1,
				'name'                => $card->name,
				'address_line2'       => $card->address_line2,
				'id'                  => $card->id,
				'cvc_check'           => $card->cvc_check,
			);
			return $data;
		}
	}