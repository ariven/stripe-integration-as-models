<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * assumes latest Stripe api library is installed in APPPATH.'third_party'
	 */
	require_once(APPPATH . 'third_party/Stripe.php');

	class Stripe_cust extends CI_Model
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

		}

		/**
		 * Get a single record by creating a WHERE clause with
		 * a value for your primary key
		 *
		 * @param string $primary_value The value of your primary key
		 * @return object
		 */
		public function get($customer_id)
		{
			try
			{
				$ch = Stripe_Customer::retrieve($customer_id);
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
		 * updates given data fields on a customer
		 *
		 * @param type $customer_id
		 * @param type $data  array of optional items: email, description (name), card.  Card can be card token from Stripe or array of these fields:
		 *                    number, exp_month, exp_year, cvc, name, address_line1, address_line2, address_zip, address_state, address_country
		 *                    number, exp_month, and exp_year are the only required fields in this array
		 */
		function update($customer_id, $data)
		{
			$customer = $this->get($customer_id);
			if ($customer)
			{
				if (isset($data['email']))
				{
					$customer->email = $data['email'];
				}
				if (isset($data['description']))
				{
					$customer->description = $data['description'];
				}
				if (isset($data['name']))
				{
					$customer->description = $data['description'];
				}
				if (isset($data['card']))
				{
					$customer->card = $data['card'];
				}
				try
				{
					$customer->save();
					return TRUE;
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
				return FALSE;
			}
		}


		/**
		 * deletes a customer
		 *
		 * @param string $customer_id id of customer
		 */
		function delete($customer_id)
		{
			if (trim($customer_id) <> '')
			{
				$customer = $this->get($customer_id);
				if ($customer['error'])
				{
					return $customer;
				}
				else
				{
					try
					{
						$ch = $customer->delete();
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
		}

		/**
		 * Get last (n) charges up to 100 (default).  Cannot return more than 100 at a time.   Data
		 * returned is sorted with most recent first.
		 *
		 * @return array - function returns associative array from stripe, we cant get objects
		 */
		public function get_all($num_customers = 100, $offset = 0)
		{
			try
			{
				$ch = Stripe_Customer::all(array(
												'count'  => $num_customers,
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
		 * creates a new customer
		 *
		 * @param string $name  used as description of customer
		 * @param string $email email address of customer
		 * @param string $card  can be card token from Stripe OR array of these fields:
		 *                      number, exp_month, exp_year, cvc, name, address_line1, address_line2, address_zip, address_state, address_country
		 *                      number, exp_month, and exp_year are the only required fields in this array
		 * @return object customer object
		 */
		public function insert($name, $email, $card = NULL)
		{
			try
			{
				$record['description'] = $name;
				if ($card <> NULL)
				{
					$record['card'] = $card;
				}
				$record['email'] = $email;
				$customer        = Stripe_Customer::create($record);
				return $customer;
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
		 * @param type $token
		 * @param type $description
		 * @param type $amount
		 * @return type
		 */
		function create($name, $email, $card)
		{
			return $this->insert($name, $email, $card);
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
				$ids[] = $this->insert($row['name'], $row['email'], $row['card']);
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
		// get_limit


	}// class stripe