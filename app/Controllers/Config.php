<?php

namespace App\Controllers;

use App\Libraries\Barcode_lib;
use App\Libraries\Mailchimp_lib;
use App\Libraries\Receiving_lib;
use App\Libraries\Sale_lib;
use App\Libraries\Tax_lib;

use App\Models\Appconfig;
use App\Models\Attribute;
use App\Models\Customer_rewards;
use App\Models\Dinner_table;
use App\Models\Module;
use App\Models\Enums\Rounding_mode;
use App\Models\Stock_location;
use App\Models\Tax;

use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Files\File;
use Config\Database;
use Config\Encryption;
use Config\Services;
use DirectoryIterator;
use NumberFormatter;
use ReflectionException;

/**
 * @property barcode_lib barcode_lib
 * @property mailchimp_lib mailchimp_lib
 * @property receiving_lib receiving_lib
 * @property sale_lib sale_lib
 * @property tax_lib tax_lib
 * @property encryption encryption
 * @property encrypterinterface encrypter
 * @property appconfig appconfig
 * @property attribute attribute
 * @property customer_rewards customer_rewards
 * @property dinner_table dinner_table
 * @property module module
 * @property rounding_mode rounding_mode
 * @property stock_location stock_location
 * @property tax tax
 * @property array config
 */
class Config extends Secure_Controller
{
	protected $helpers = ['security'];
	private $db;


	public function __construct()
	{
		parent::__construct('config');

		$this->barcode_lib = new Barcode_lib();
		$this->sale_lib = new Sale_lib();
		$this->receiving_lib = new receiving_lib();
		$this->tax_lib = new Tax_lib();
		$this->appconfig = model('Appconfig');
		$this->attribute = model('Attribute');
		$this->customer_rewards = model('Customer_rewards');
		$this->dinner_table = model('Dinner_table');
		$this->module = model('Module');
		$this->rounding_mode = model('Rounding_mode');
		$this->stock_location = model('Stock_location');
		$this->tax = model('Tax');
		$this->config = config('OSPOS')->settings;
		$this->db = Database::connect();

		helper('security');
		if(check_encryption())
		{
			$this->encrypter = Services::encrypter();
		}
		else
		{
			log_message('alert', 'Error preparing encryption key');
		}
	}

	/*
	 * This function loads all the licenses starting with the first one being OSPOS one
	 */
	private function _licenses(): array    //TODO: remove hungarian notation.  Super long function.  Perhaps we need to refactor out functions?
	{
		$i = 0;
		$bower = FALSE;
		$composer = FALSE;
		$license = [];

		$license[$i]['title'] = 'Open Source Point Of Sale ' . config('App')->application_version;

		if(file_exists('license/LICENSE'))
		{
			$license[$i]['text'] = file_get_contents('license/LICENSE', false, NULL, 0, 2000);
		}
		else
		{
			$license[$i]['text'] = 'LICENSE file must be in OSPOS license directory. You are not allowed to use OSPOS application until the distribution copy of LICENSE file is present.';
		}

		$dir = new DirectoryIterator('license');	// read all the files in the dir license

		foreach($dir as $fileinfo)	//TODO: $fileinfo doesn't match our variable naming convention
		{
			// license files must be in couples: .version (name & version) & .license (license text)
			if($fileinfo->isFile())
			{
				if($fileinfo->getExtension() == 'version')
				{
					++$i;

					$basename = 'license/' . $fileinfo->getBasename('.version');

					$license[$i]['title'] = file_get_contents($basename . '.version', false, NULL, 0, 100);

					$license_text_file = $basename . '.license';

					if(file_exists($license_text_file))
					{
						$license[$i]['text'] = file_get_contents($license_text_file , false, NULL, 0, 2000);
					}
					else
					{
						$license[$i]['text'] = $license_text_file . ' file is missing';
					}
				}
				elseif($fileinfo->getBasename() == 'bower.LICENSES')
				{
					// set a flag to indicate that the JS Plugin bower.LICENSES file is available and needs to be attached at the end
					$bower = TRUE;
				}
				elseif($fileinfo->getBasename() == 'composer.LICENSES')
				{
					// set a flag to indicate that the composer.LICENSES file is available and needs to be attached at the end
					$composer = TRUE;
				}
			}
		}

		// attach the licenses from the LICENSES file generated by bower
		if($composer)
		{
			++$i;
			$license[$i]['title'] = 'Composer Libraries';
			$license[$i]['text'] = '';

			$file = file_get_contents('license/composer.LICENSES');
			$array = json_decode($file, TRUE);

			foreach($array as $key => $val)
			{
				if(is_array($val) && $key == 'dependencies')
				{
					foreach($val as $key1 => $val1)
					{
						if(is_array($val1))
						{
							$license[$i]['text'] .= "component: $key1\n";	//TODO: Duplicated Code

							foreach($val1 as $key2 => $val2)
							{
								if(is_array($val2))
								{
									$license[$i]['text'] .= "$key2: ";

									foreach($val2 as $key3 => $val3)
									{
										$license[$i]['text'] .= "$val3 ";
									}

									$license[$i]['text'] .= '\n';
								}
								else
								{
									$license[$i]['text'] .= "$key2: $val2\n";
								}
							}

							$license[$i]['text'] .= '\n';
						}
						else
						{
							$license[$i]['text'] .= "$key1: $val1\n";
						}
					}
				}
			}
		}

		// attach the licenses from the LICENSES file generated by bower
		if($bower)
		{
			++$i;
			$license[$i]['title'] = 'JS Plugins';
			$license[$i]['text'] = '';

			$file = file_get_contents('license/bower.LICENSES');
			$array = json_decode($file, TRUE);

			foreach($array as $key => $val)
			{
				if(is_array($val))
				{
					$license[$i]['text'] .= "component: $key\n";	//TODO: Duplicated Code.

					foreach($val as $key1 => $val1)
					{
						if(is_array($val1))
						{
							$license[$i]['text'] .= "$key1: ";

							foreach($val1 as $key2 => $val2)
							{
								$license[$i]['text'] .= "$val2 ";
							}

							$license[$i]['text'] .= '\n';
						}
						else
						{
							$license[$i]['text'] .= "$key1: $val1\n";
						}
					}

					$license[$i]['text'] .= '\n';
				}
			}
		}

		return $license;
	}

	/*
	 * This function loads all the available themes in the dist/bootswatch directory
	 */
	private function _themes(): array	//TODO: Hungarian notation
	{
		$themes = [];

		// read all themes in the dist folder
		$dir = new DirectoryIterator('resources/bootswatch');

		foreach($dir as $dirinfo)	//TODO: $dirinfo doesn't follow naming convention
		{
			if($dirinfo->isDir() && !$dirinfo->isDot() && $dirinfo->getFileName() != 'fonts')
			{
				$file = $dirinfo->getFileName();
				$themes[$file] = ucfirst($file);
			}
		}

		asort($themes);

		return $themes;
	}

	/**
	 * @throws ReflectionException
	 */
	public function getIndex(): void
	{
		$data['stock_locations'] = $this->stock_location->get_all()->getResultArray();
		$data['dinner_tables'] = $this->dinner_table->get_all()->getResultArray();
		$data['customer_rewards'] = $this->customer_rewards->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['barcode_fonts'] = $this->barcode_lib->listfonts('fonts');
		$data['logo_exists'] = $this->config['company_logo'] != '';
		$data['line_sequence_options'] = $this->sale_lib->get_line_sequence_options();
		$data['register_mode_options'] = $this->sale_lib->get_register_mode_options();
		$data['invoice_type_options'] = $this->sale_lib->get_invoice_type_options();
		$data['rounding_options'] = rounding_mode::get_rounding_options();
		$data['tax_code_options'] = $this->tax_lib->get_tax_code_options();
		$data['tax_category_options'] = $this->tax_lib->get_tax_category_options();
		$data['tax_jurisdiction_options'] = $this->tax_lib->get_tax_jurisdiction_options();
		$data['show_office_group'] = $this->module->get_show_office_group();
		$data['currency_code'] = isset($this->config['currency_code'])
			? $this->config['currency_code']
			: '' ;
		$data['db_version'] = mysqli_get_server_info(db_connect()->mysqli);

		// load all the license statements, they are already XSS cleaned in the private function
		$data['licenses'] = $this->_licenses();

		// load all the themes, already XSS cleaned in the private function
		$data['themes'] = $this->_themes();

		//Load General related fields
		$image_allowed_types = ['jpg','jpeg','gif','svg','webp','bmp','png','tif','tiff'];
		$data['image_allowed_types'] = array_combine($image_allowed_types,$image_allowed_types);

		$data['selected_image_allowed_types'] = explode('|', $this->config['image_allowed_types']);

		//Load Integrations Related fields
		$data['mailchimp']	= [];

		if(check_encryption())	//TODO: Hungarian notation
		{
			if(!isset($this->encrypter))
			{
				helper('security');
				$this->encrypter = Services::encrypter();
			}

			$mailchimp_api_key = (isset($this->config['mailchimp_api_key']) && !empty($this->config['mailchimp_api_key']))
				? $this->encrypter->decrypt($this->config['mailchimp_api_key'])
				: '';

			$mailchimp_list_id = (isset($this->config['mailchimp_list_id']) && !empty($this->config['mailchimp_list_id']))
				? $this->encrypter->decrypt($this->config['mailchimp_list_id'])
				: '';

			//Remove any backup of .env created by check_encryption()
			remove_backup();
		}
		else
		{
			$data['mailchimp']['api_key'] = '';
			$data['mailchimp']['list_id'] = '';
		}

		$data['mailchimp']['lists'] = $this->_mailchimp();

		echo view('configs/manage', $data);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveInfo(): void
	{
		$upload_data = $this->upload_logo();
		$upload_success = !empty($upload_data['error']);

		$batch_save_data = [
			'company' => $this->request->getPost('company'),
			'address' => $this->request->getPost('address'),
			'phone' => $this->request->getPost('phone'),
			'email' => $this->request->getPost('email', FILTER_SANITIZE_EMAIL),
			'fax' => $this->request->getPost('fax'),
			'website' => $this->request->getPost('website', FILTER_SANITIZE_URL),
			'return_policy' => $this->request->getPost('return_policy')
		];

		if(!empty($upload_data['orig_name']) && $upload_data['raw_name'] === TRUE)
		{
			$batch_save_data['company_logo'] = $upload_data['raw_name'] . $upload_data['file_ext'];
		}

		$result = $this->appconfig->batch_save($batch_save_data);
		$success = $upload_success && $result;
		$message = lang('Config.saved_' . ($success ? '' : 'un') . 'successfully');
		$message = $upload_success ? $message : strip_tags($upload_data['error']);

		echo json_encode(['success' => $success, 'message' => $message]);
	}


	/**
	 * @return array
	 */
	private function upload_logo(): array
	{
		helper(['form']);
		$validation_rule = [
			'company_logo' => [
				'label' => 'Company logo',
				'rules' => [
					'uploaded[company_logo]',
					'is_image[company_logo]',
					'max_size[company_logo,1024]',
					'mime_in[company_logo,image/png,image/jpg,image/gif]',
					'ext_in[company_logo,png,jpg,gif]',
					'max_dims[company_logo,800,680]',
				]
			]
		];

		if (!$this->validate($validation_rule))
		{
			return (['error' => $this->validator->getError('company_logo')]);
		}
		else
		{
			$file = $this->request->getFile('company_logo');
			$file->move(BASEPATH . 'uploads');

			$file_info = [
				'orig_name' => $file->getClientName(),
				'raw_name' => $file->getName(),
				'file_ext' => $file->guessExtension()
			];

			return ($file_info);
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveGeneral(): void
	{
		$batch_save_data = [
			'theme' => $this->request->getPost('theme'),
			'login_form' => $this->request->getPost('login_form'),
			'default_sales_discount_type' => $this->request->getPost('default_sales_discount_type') != NULL,
			'default_sales_discount' => $this->request->getPost('default_sales_discount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'default_receivings_discount_type' => $this->request->getPost('default_receivings_discount_type') != NULL,
			'default_receivings_discount' => $this->request->getPost('default_receivings_discount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'enforce_privacy' => $this->request->getPost('enforce_privacy', FILTER_SANITIZE_NUMBER_INT),
			'receiving_calculate_average_price' => $this->request->getPost('receiving_calculate_average_price') != NULL,
			'lines_per_page' => $this->request->getPost('lines_per_page', FILTER_SANITIZE_NUMBER_INT),
			'notify_horizontal_position' => $this->request->getPost('notify_horizontal_position', FILTER_SANITIZE_NUMBER_INT),
			'notify_vertical_position' => $this->request->getPost('notify_vertical_position', FILTER_SANITIZE_NUMBER_INT),
			'image_max_width' => $this->request->getPost('image_max_width', FILTER_SANITIZE_NUMBER_INT),
			'image_max_height' => $this->request->getPost('image_max_height', FILTER_SANITIZE_NUMBER_INT),
			'image_max_size' => $this->request->getPost('image_max_size', FILTER_SANITIZE_NUMBER_INT),
			'image_allowed_types' => implode('|', $this->request->getPost('image_allowed_types')),
			'gcaptcha_enable' => $this->request->getPost('gcaptcha_enable') != NULL,
			'gcaptcha_secret_key' => $this->request->getPost('gcaptcha_secret_key'),
			'gcaptcha_site_key' => $this->request->getPost('gcaptcha_site_key'),
			'suggestions_first_column' => $this->request->getPost('suggestions_first_column'),
			'suggestions_second_column' => $this->request->getPost('suggestions_second_column'),
			'suggestions_third_column' => $this->request->getPost('suggestions_third_column'),
			'giftcard_number' => $this->request->getPost('giftcard_number'),
			'derive_sale_quantity' => $this->request->getPost('derive_sale_quantity', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) != NULL,
			'multi_pack_enabled' => $this->request->getPost('multi_pack_enabled') != NULL,
			'include_hsn' => $this->request->getPost('include_hsn') != NULL,
			'category_dropdown' => $this->request->getPost('category_dropdown') != NULL
		];

		$this->module->set_show_office_group($this->request->getPost('show_office_group') != NULL);

		if($batch_save_data['category_dropdown'] == 1)
		{
			$definition_data['definition_name'] = 'ospos_category';
			$definition_data['definition_flags'] = 0;
			$definition_data['definition_type'] = 'DROPDOWN';
			$definition_data['definition_id'] = CATEGORY_DEFINITION_ID;
			$definition_data['deleted'] = 0;

			$this->attribute->save_definition($definition_data, CATEGORY_DEFINITION_ID);
		}
		else if($batch_save_data['category_dropdown'] == NO_DEFINITION_ID)
		{
			$this->attribute->delete_definition(CATEGORY_DEFINITION_ID);
		}

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @return void
	 */
	public function postCheckNumberLocale(): void
	{
		$number_locale = $this->request->getPost('number_locale');
		$save_number_locale = $this->request->getPost('save_number_locale');

		$fmt = new NumberFormatter($number_locale, NumberFormatter::CURRENCY);
		if($number_locale != $save_number_locale)
		{
			$currency_symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
			$currency_code = $fmt->getTextAttribute(NumberFormatter::CURRENCY_CODE);
			$save_number_locale = $number_locale;
		}
		else
		{
			$currency_symbol = empty($this->request->getPost('currency_symbol')) ? $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL) : $this->request->getPost('currency_symbol');
			$currency_code = empty($this->request->getPost('currency_code')) ? $fmt->getTextAttribute(NumberFormatter::CURRENCY_CODE) : $this->request->getPost('currency_code');
		}

		if($this->request->getPost('thousands_separator') == 'false')
		{
			$fmt->setAttribute(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, '');
		}

		$fmt->setSymbol(NumberFormatter::CURRENCY_SYMBOL, $currency_symbol);
		$number_local_example = $fmt->format(1234567890.12300);

		echo json_encode([
			'success' => $number_local_example != FALSE,
			'save_number_locale' => $save_number_locale,
			'number_locale_example' => $number_local_example,
			'currency_symbol' => $currency_symbol,
			'currency_code' => $currency_code,
		]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveLocale(): void
	{
		$exploded = explode(":", $this->request->getPost('language'));
		$batch_save_data = [
			'currency_symbol' => $this->request->getPost('currency_symbol'),
			'currency_code' => $this->request->getPost('currency_code'),
			'language_code' => $exploded[0],
			'language' => $exploded[1],
			'timezone' => $this->request->getPost('timezone'),
			'dateformat' => $this->request->getPost('dateformat'),
			'timeformat' => $this->request->getPost('timeformat'),
			'thousands_separator' => !empty($this->request->getPost('thousands_separator', FILTER_SANITIZE_NUMBER_INT)),
			'number_locale' => $this->request->getPost('number_locale'),
			'currency_decimals' => $this->request->getPost('currency_decimals', FILTER_SANITIZE_NUMBER_INT),
			'tax_decimals' => $this->request->getPost('tax_decimals', FILTER_SANITIZE_NUMBER_INT),
			'quantity_decimals' => $this->request->getPost('quantity_decimals', FILTER_SANITIZE_NUMBER_INT),
			'country_codes' => $this->request->getPost('country_codes'),
			'payment_options_order' => $this->request->getPost('payment_options_order'),
			'date_or_time_format' => $this->request->getPost('date_or_time_format', FILTER_SANITIZE_NUMBER_INT),
			'cash_decimals' => $this->request->getPost('cash_decimals', FILTER_SANITIZE_NUMBER_INT),
			'cash_rounding_code' => $this->request->getPost('cash_rounding_code'),
			'financial_year' => $this->request->getPost('financial_year', FILTER_SANITIZE_NUMBER_INT)
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode(['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveEmail(): void
	{
		$password = '';

		if(check_encryption())
		{
			$smtp_pass = $this->encrypter->encrypt($this->request->getPost('smtp_pass'));
			if(!empty($smtp_pass))
			{
				$password = $this->encrypter->encrypt($this->request->getPost('smtp_pass'));
			}
		}

		$batch_save_data = [
			'protocol' => $this->request->getPost('protocol'),
			'mailpath' => $this->request->getPost('mailpath'),
			'smtp_host' => $this->request->getPost('smtp_host'),
			'smtp_user' => $this->request->getPost('smtp_user'),
			'smtp_pass' => $password,
			'smtp_port' => $this->request->getPost('smtp_port', FILTER_SANITIZE_NUMBER_INT),
			'smtp_timeout' => $this->request->getPost('smtp_timeout', FILTER_SANITIZE_NUMBER_INT),
			'smtp_crypto' => $this->request->getPost('smtp_crypto')
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveMessage(): void
	{
		$password = '';

		if(check_encryption())
		{
			$password = $this->encrypter->encrypt($this->request->getPost('msg_pwd'));
		}

		$batch_save_data = [
			'msg_msg' => $this->request->getPost('msg_msg'),
			'msg_uid' => $this->request->getPost('msg_uid'),
			'msg_pwd' => $password,
			'msg_src' => $this->request->getPost('msg_src')
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode(['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/*
	 * This function fetches all the available lists from Mailchimp for the given API key
	 */
	private function _mailchimp(string $api_key = ''): array	//TODO: Hungarian notation
	{
		$this->mailchimp_lib = new Mailchimp_lib(['api_key' => $api_key]);

		$result = [];

		$lists = $this->mailchimp_lib->getLists();
		if($lists !== FALSE)
		{
			if(is_array($lists) && !empty($lists['lists']) && is_array($lists['lists']))
			{
				foreach($lists['lists'] as $list)
				{
					$result[$list['id']] = $list['name'] . ' [' . $list['stats']['member_count'] . ']';
				}
			}
		}

		return $result;
	}

	/**
	 * AJAX call from mailchimp config form to fetch the Mailchimp lists when a valid API key is inserted
	 *
	 * @return void
	 */
	public function postCheckMailchimpApiKey(): void
	{
		// load mailchimp lists associated to the given api key, already XSS cleaned in the private function
		$lists = $this->_mailchimp($this->request->getPost('mailchimp_api_key'));
		$success = count($lists) > 0;

		echo json_encode ([
			'success' => $success,
			'message' => lang('Config.mailchimp_key_' . ($success ? '' : 'un') . 'successfully'),
			'mailchimp_lists' => $lists
		]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveMailchimp(): void
	{
		$api_key = '';
		$list_id = '';

		if(check_encryption())	//TODO: Hungarian notation
		{
			$api_key_unencrypted = $this->request->getPost('mailchimp_api_key');
			if(!empty($api_key_unencrypted))
			{
				$api_key = $this->encrypter->encrypt($api_key_unencrypted);
				$api_key_unencrypted = '';
			}

			$list_id_unencrypted = $this->request->getPost('mailchimp_list_id');
			if(!empty($list_id_unencrypted))
			{
				$list_id = $this->encrypter->encrypt($list_id_unencrypted);
				$list_id_unencrypted = '';
			}
		}

		$batch_save_data = ['mailchimp_api_key' => $api_key, 'mailchimp_list_id' => $list_id];

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode(['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	public function getStockLocations(): void
	{
		$stock_locations = $this->stock_location->get_all()->getResultArray();

		echo view('partial/stock_locations', ['stock_locations' => $stock_locations]);
	}

	public function getDinnerTables(): void
	{
		$dinner_tables = $this->dinner_table->get_all()->getResultArray();

		echo view('partial/dinner_tables', ['dinner_tables' => $dinner_tables]);
	}

	public function ajax_tax_categories(): void
	{
		$tax_categories = $this->tax->get_all_tax_categories()->getResultArray();

		echo view('partial/tax_categories', ['tax_categories' => $tax_categories]);
	}

	public function getCustomerRewards(): void
	{
		$customer_rewards = $this->customer_rewards->get_all()->getResultArray();

		echo view('partial/customer_rewards', ['customer_rewards' => $customer_rewards]);
	}

	private function _clear_session_state(): void	//TODO: Hungarian notation
	{
		$this->sale_lib->clear_sale_location();
		$this->sale_lib->clear_table();
		$this->sale_lib->clear_all();
		$this->receiving_lib = new Receiving_lib();
		$this->receiving_lib->clear_stock_source();
		$this->receiving_lib->clear_stock_destination();
		$this->receiving_lib->clear_all();
	}

	public function postSaveLocations(): void
	{
		$this->db->transStart();

		$not_to_delete = [];
		foreach($this->request->getPost(NULL) as $key => $value)
		{
			if(strstr($key, 'stock_location'))
			{
				// save or update
				foreach ($value as $location_id => $location_name)
				{
					$location_data = ['location_name' => $location_name];
					if($this->stock_location->save_value($location_data, $location_id))
					{
						$location_id = $this->stock_location->get_location_id($location_name);
						$not_to_delete[] = $location_id;
						$this->_clear_session_state();
					}
				}
			}
		}

		// all locations not available in post will be deleted now
		$deleted_locations = $this->stock_location->get_all()->getResultArray();

		foreach($deleted_locations as $location => $location_data)
		{
			if(!in_array($location_data['location_id'], $not_to_delete))
			{
				$this->stock_location->delete($location_data['location_id']);
			}
		}

		$this->db->transComplete();

		$success = $this->db->transStatus();

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveTables(): void
	{
		$this->db->transStart();

		$dinner_table_enable = $this->request->getPost('dinner_table_enable') != NULL;

		$this->appconfig->save(['dinner_table_enable' => $dinner_table_enable]);

		if($dinner_table_enable)
		{
			$not_to_delete = [];
			foreach($this->request->getPost(NULL) as $key => $value)	//TODO: Not sure if this is the best way to filter the array
			{
				if(strstr($key, 'dinner_table') && $key != 'dinner_table_enable')
				{
					$dinner_table_id = preg_replace("/.*?_(\d+)$/", "$1", $key);
					$not_to_delete[] = $dinner_table_id;

					// save or update
					$table_data = ['name' => $value];
					if($this->dinner_table->save_value($table_data, $dinner_table_id))
					{
						$this->_clear_session_state();	//TODO: Remove hungarian notation.
					}
				}
			}

			// all tables not available in post will be deleted now
			$deleted_tables = $this->dinner_table->get_all()->getResultArray();

			foreach($deleted_tables as $dinner_tables => $table)
			{
				if(!in_array($table['dinner_table_id'], $not_to_delete))
				{
					$this->dinner_table->delete($table['dinner_table_id']);
				}
			}
		}

		$this->db->transComplete();

		$success = $this->db->transStatus();

		echo json_encode (['success' => $success,'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveTax(): void
	{
		$this->db->transStart();

		$batch_save_data = [
			'default_tax_1_rate' => parse_tax($this->request->getPost('default_tax_1_rate', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)),
			'default_tax_1_name' => $this->request->getPost('default_tax_1_name'),
			'default_tax_2_rate' => parse_tax($this->request->getPost('default_tax_2_rate', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)),
			'default_tax_2_name' => $this->request->getPost('default_tax_2_name'),
			'tax_included' => $this->request->getPost('tax_included') != NULL,
			'use_destination_based_tax' => $this->request->getPost('use_destination_based_tax') != NULL,
			'default_tax_code' => $this->request->getPost('default_tax_code'),
			'default_tax_category' => $this->request->getPost('default_tax_category'),
			'default_tax_jurisdiction' => $this->request->getPost('default_tax_jurisdiction'),
			'tax_id' => $this->request->getPost('tax_id', FILTER_SANITIZE_NUMBER_INT)
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		$this->db->transComplete();

		$success &= $this->db->transStatus();

		$message = lang('Config.saved_' . ($success ? '' : 'un') . 'successfully');

		echo json_encode (['success' => $success, 'message' => $message]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveRewards(): void
	{
		$this->db->transStart();

		$customer_reward_enable = $this->request->getPost('customer_reward_enable') != NULL;

		$this->appconfig->save(['customer_reward_enable' => $customer_reward_enable]);

		if($customer_reward_enable)
		{
			$not_to_delete = [];
			$array_save = [];
			foreach($this->request->getPost(NULL) as $key => $value)
			{
				if(strstr($key, 'customer_reward') && $key != 'customer_reward_enable')
				{
					$customer_reward_id = preg_replace("/.*?_(\d+)$/", "$1", $key);
					$not_to_delete[] = $customer_reward_id;
					$array_save[$customer_reward_id]['package_name'] = $value;
				}
				elseif(strstr($key, 'reward_points'))
				{
					$customer_reward_id = preg_replace("/.*?_(\d+)$/", "$1", $key);
					$array_save[$customer_reward_id]['points_percent'] = $value;
				}
			}

			if(!empty($array_save))
			{
				foreach($array_save as $key => $value)
				{
					// save or update
					$package_data = ['package_name' => $value['package_name'], 'points_percent' => $value['points_percent']];
					$this->customer_rewards->save_value($package_data, $key);	//TODO: reflection exception
				}
			}

			// all packages not available in post will be deleted now
			$deleted_packages = $this->customer_rewards->get_all()->getResultArray();

			foreach($deleted_packages as $customer_rewards => $reward_category)
			{
				if(!in_array($reward_category['package_id'], $not_to_delete))
				{
					$this->customer_rewards->delete($reward_category['package_id']);
				}
			}
		}

		$this->db->transComplete();

		$success = $this->db->transStatus();

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveBarcode(): void
	{
		$batch_save_data = [
			'barcode_type' => $this->request->getPost('barcode_type'),
			'barcode_width' => $this->request->getPost('barcode_width', FILTER_SANITIZE_NUMBER_INT),
			'barcode_height' => $this->request->getPost('barcode_height', FILTER_SANITIZE_NUMBER_INT),
			'barcode_font' => $this->request->getPost('barcode_font'),
			'barcode_font_size' => $this->request->getPost('barcode_font_size', FILTER_SANITIZE_NUMBER_INT),
			'barcode_first_row' => $this->request->getPost('barcode_first_row'),
			'barcode_second_row' => $this->request->getPost('barcode_second_row'),
			'barcode_third_row' => $this->request->getPost('barcode_third_row'),
			'barcode_num_in_row' => $this->request->getPost('barcode_num_in_row', FILTER_SANITIZE_NUMBER_INT),
			'barcode_page_width' => $this->request->getPost('barcode_page_width', FILTER_SANITIZE_NUMBER_INT),
			'barcode_page_cellspacing' => $this->request->getPost('barcode_page_cellspacing', FILTER_SANITIZE_NUMBER_INT),
			'barcode_generate_if_empty' => $this->request->getPost('barcode_generate_if_empty') != NULL,
			'allow_duplicate_barcodes' => $this->request->getPost('allow_duplicate_barcodes') != NULL,
			'barcode_content' => $this->request->getPost('barcode_content'),
			'barcode_formats' => json_encode($this->request->getPost('barcode_formats'))
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveReceipt(): void
	{
		$batch_save_data = [
			'receipt_template' => $this->request->getPost('receipt_template'),
			'receipt_font_size' => $this->request->getPost('receipt_font_size', FILTER_SANITIZE_NUMBER_INT),
			'print_delay_autoreturn' => $this->request->getPost('print_delay_autoreturn', FILTER_SANITIZE_NUMBER_INT),
			'email_receipt_check_behaviour' => $this->request->getPost('email_receipt_check_behaviour'),
			'print_receipt_check_behaviour' => $this->request->getPost('print_receipt_check_behaviour'),
			'receipt_show_company_name' => $this->request->getPost('receipt_show_company_name') != NULL,
			'receipt_show_taxes' => ($this->request->getPost('receipt_show_taxes') != NULL),
			'receipt_show_tax_ind' => ($this->request->getPost('receipt_show_tax_ind') != NULL),
			'receipt_show_total_discount' => $this->request->getPost('receipt_show_total_discount') != NULL,
			'receipt_show_description' => $this->request->getPost('receipt_show_description') != NULL,
			'receipt_show_serialnumber' => $this->request->getPost('receipt_show_serialnumber') != NULL,
			'print_silently' => $this->request->getPost('print_silently') != NULL,
			'print_header' => $this->request->getPost('print_header') != NULL,
			'print_footer' => $this->request->getPost('print_footer') != NULL,
			'print_top_margin' => $this->request->getPost('print_top_margin', FILTER_SANITIZE_NUMBER_INT),
			'print_left_margin' => $this->request->getPost('print_left_margin', FILTER_SANITIZE_NUMBER_INT),
			'print_bottom_margin' => $this->request->getPost('print_bottom_margin', FILTER_SANITIZE_NUMBER_INT),
			'print_right_margin' => $this->request->getPost('print_right_margin', FILTER_SANITIZE_NUMBER_INT)
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function postSaveInvoice(): void
	{
		$batch_save_data = [
			'invoice_enable' => $this->request->getPost('invoice_enable') != NULL,
			'sales_invoice_format' => $this->request->getPost('sales_invoice_format'),
			'sales_quote_format' => $this->request->getPost('sales_quote_format'),
			'recv_invoice_format' => $this->request->getPost('recv_invoice_format'),
			'invoice_default_comments' => $this->request->getPost('invoice_default_comments'),
			'invoice_email_message' => $this->request->getPost('invoice_email_message'),
			'line_sequence' => $this->request->getPost('line_sequence'),
			'last_used_invoice_number' => $this->request->getPost('last_used_invoice_number', FILTER_SANITIZE_NUMBER_INT),
			'last_used_quote_number' => $this->request->getPost('last_used_quote_number', FILTER_SANITIZE_NUMBER_INT),
			'quote_default_comments' => $this->request->getPost('quote_default_comments'),
			'work_order_enable' => $this->request->getPost('work_order_enable') != NULL,
			'work_order_format' => $this->request->getPost('work_order_format'),
			'last_used_work_order_number' => $this->request->getPost('last_used_work_order_number', FILTER_SANITIZE_NUMBER_INT),
			'invoice_type' => $this->request->getPost('invoice_type')
		];

		$success = $this->appconfig->batch_save($batch_save_data);

		// Update the register mode with the latest change so that if the user
		// switches immediately back to the register the mode reflects the change
		if($success == TRUE)
		{
			if($this->config['invoice_enable'])
			{
				$this->sale_lib->set_mode($this->config['default_register_mode']);
			}
			else
			{
				$this->sale_lib->set_mode('sale');
			}
		}

		echo json_encode (['success' => $success, 'message' => lang('Config.saved_' . ($success ? '' : 'un') . 'successfully')]);
	}

	/**
	 * @throws ReflectionException
	 */
	public function remove_logo(): void
	{
		$success = $this->appconfig->save(['company_logo' => '']);

		echo json_encode (['success' => $success]);
	}
}
