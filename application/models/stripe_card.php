<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * assumes latest Stripe api library is installed in APPPATH.'third_party'
	 */
	require_once(APPPATH . 'third_party/Stripe.php');

	class Stripe_card extends CI_Model
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

		/**
		 * Get a single record by creating a WHERE clause with
		 * a value for your primary key
		 *
		 * @param string $primary_value The value of your primary key
		 * @return object or array
		 */
		public function get($card_id)
		{
			try
			{
				$ch = Stripe_Token::retrieve($card_id);
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
		 * creates a card token
		 *
		 * @param type $card_info     array of number, exp_month, exp_year, cvc, name, address_line1,
		 *                            address_line2, address_zip, address_state, address_country
		 *                            only number, exp_month, exp_year are required
		 * @return type
		 */
		public function insert($card_info)
		{
			try
			{
				$card = Stripe_Token::create($card_info);
				return $card;
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
				$ids[] = $this->insert($row);
			}

			return $ids;
		}

	}// class stripe