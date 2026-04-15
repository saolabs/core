<?php

/**
 * @author DoanLN
 * @date 2019-07-16
 *
 */

namespace Saola\Core\Mailer;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Tiện ích gửi mail
 * @method static static from($email, $name = null) fake email gui di
 * @method static static to(string|array $email, $name = null) set email nhan
 * @method static static cc(string|array $email, $name = null) set cc
 * @method static static bcc(string|array $email, $name = null) set bcc
 * @method static static replyTo(string $email, $name = null) set dia chi reply
 * @method static static subject(string $subject) set subject
 * @method static static body(string $blade) set blade view 
 * @method static static message(string $message) set message
 * @method static static data($data = []) data truyen vao view
 * @method static static attach($files = []) file dinh kem
 * @method static bool send(string|array $to = null, string $dubject = null, string $body = null, array $data = [], string|array $attachments = []) gui đi
 * 
 * @method $this to(string|array $email, $name = null) fake email gui di
 * @method $this cc(string|array$email, $name = null) set dia chi emai nguoi nhan
 * @method $this bcc(string|array $email, $name = null) set bcc
 * @method $this replyTo(string $email, string $name = null) set dia chi nguoio nhn tra loi
 * @method $this subject(string $subject) set subject
 * @method $this body(string $blade) set blade view
 * @method $this message(string $message) message noi dung
 * @method $this data($data = []) set data truyen vao view
 * @method $this attach($files = []) file dinh kem
 * @method bool send(string|array $to = null, string $dubject = null, string $body = null, array $data = [], string|array $attachments = []) gui đi
 * @method bool sendAfter(int $time = 1) gui sau n phut
 * @method bool queue(int $time = 1) gui sau n phut
 * @method bool beforeSend() Thuc hiện hành dộng trước khi gửi
 * 
 * @method $this *($value) set giá trị
 *
 */
class Email
{
	protected $__subject = null;
	protected $__body = null;
	protected $__data = [];
	protected $__attachments = null;
	protected $addressData = [
		'from' => [],
		'to' => [],
		'cc' => [],
		'bcc' => [],
		'replyTo' => []
	];

	protected $__canSend__ = true;


	protected $config = [];

	protected static $mailConfig = [];

	protected static $__oneTimeData = [];

	/**
	 * khoi tao
	 */
	public function __construct()
	{
		$this->__checkConfig();
	}

	protected static function __checkStaticConfig()
	{
		if (!static::$mailConfig) {
			$config = [
				'default' => config('mail.default'),
				'mailers' => [
					'smtp' => [
						'transport' => 'smtp',
						'host' => config('mail.mailers.smtp.host'),
						'port' => config('mail.mailers.smtp.port'),
						'encryption' => config('mail.mailers.smtp.encryption'),
						'username' => config('mail.mailers.smtp.username'),
						'password' => config('mail.mailers.smtp.password'),
						'timeout' => null,
					],

					'ses' => [
						'transport' => 'ses',
					],

					'mailgun' => [
						'transport' => 'mailgun',
					],

					'postmark' => [
						'transport' => 'postmark',
					],

					'sendmail' => [
						'transport' => 'sendmail',
						'path' => config('mail.mailers.sendmail.path',  '/usr/sbin/sendmail -bs -i'),
					],

					'log' => [
						'transport' => 'log',
						'channel' => config('mail.mailers.log.channel'),
					],

					'array' => [
						'transport' => 'array',
					],

					'failover' => [
						'transport' => 'failover',
						'mailers' => [
							'smtp',
							'log',
						],
					],
				],


				'from' => [
					'address' => config('mail.from.address'),
					'name' => config('mail.from.address'),
				],

				'markdown' => [
					'theme' => 'default',

					'paths' => config('mail.markdown.paths')
				],
				'queue' => [
					'enabled' => in_array(config('mail.queue.enabled'), ['off', 'no', 'OFF', 'false', 'FALSE', false]) ? 'OFF' : 'ON'
				]
			];

			static::$mailConfig = $config;
			Config::set('mail', $config);
		}
		return static::$mailConfig;
	}

	protected function __checkConfig()
	{
		if (!$this->config) {
			$this->config = static::__checkStaticConfig();
		}
		return $this->config;
	}
	/**
	 * thêm địa chỉ email
	 *
	 * @param string $type
	 * @param array|string $email
	 * @param string $name
	 * @return $this
	 */
	public function addAddress($type = 'to', $email = null, $name = null)
	{
		if ($email && array_key_exists($type, $this->addressData)) {
			if (is_array($email)) {
				foreach ($email as $key => $val) {
					if (is_numeric($key)) {
						if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
							$this->addressData[$type][$val] = 'Guest';
						}
					} else {
						if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
							$this->addressData[$type][$val] = $key;
						} elseif (filter_var($key, FILTER_VALIDATE_EMAIL)) {
							$this->addressData[$type][$key] = $val;
						}
					}
				}
			} else {
				if ($name) {
					$this->addressData[$type][$email] = $name;
				} else {
					$this->addressData[$type][] = $email;
				}
			}
		}
		return $this;
	}

	/**
	 * gọi hàm gì đó trong message
	 *
	 * @param mixed $message
	 * @param string $method
	 * @param array|string $info
	 * @return mixed
	 */
	public function callMessageMethod($message, $method, $info)
	{
		if (is_string($info) && filter_var($info, FILTER_VALIDATE_EMAIL)) {
			call_user_func_array([$message, $method], [$info, 'Guest']);
		} elseif (is_array($info)) {
			foreach ($info as $key => $val) {
				if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
					call_user_func_array([$message, $method], (!is_numeric($key)) ? [$val, $key] : [$val]);
				} elseif (filter_var($key, FILTER_VALIDATE_EMAIL)) {
					call_user_func_array([$message, $method], [$key, $val]);
				}
			}
		}
		return $message;
	}

	/**
	 * gửi mail
	 *
	 * @param string $body
	 * @param array $var
	 * @return void
	 */
	public function _sendMail($body = null, $vars = [])
	{
		if (!$this->__canSend__) return false;
		$this->__checkConfig();
		if (method_exists($this, 'beforeSend')) {
			$s = $this->beforeSend();
			if ($s === false) return false;
		}
		if (static::$__oneTimeData) {
			$vars = array_merge(static::$__oneTimeData, $vars);
			static::$__oneTimeData = [];
		}
		if (!$body) $body = $this->__body;
		Mail::send($body, $vars, function ($message) {
			$data = $this->addressData;
			foreach ($data as $key => $value) {
				$this->callMessageMethod($message, $key, $value);
			}
			$message->subject($this->__subject);
			if ($this->__attachments) {
				$files = $this->__attachments;
				foreach ($files as $file) {
					$message->attach($file);
				}
			}
		});
	}


	/**
	 * gửi mail
	 *
	 * @param string|array $to địa chỉ / thông tin người nhận
	 * @param string $subject Chủ đề
	 * @param string $body blade view
	 * @param array $data sữ liệu được dùng trong mail
	 * @param array $attachments file đính kèm
	 * @return bool
	 */
	protected function _send($to = null, $subject = null, $body = '', $data = [], $attachments = null)
	{
		if ($subject) {
			$this->__subject = $subject;
		}
		if (!$body) $body = $this->__body;
		$var = array_merge($this->__data, static::$__oneTimeData, $data);
		static::$__oneTimeData = [];
		if (is_string($to) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
			$this->addAddress('to', $to);
		} elseif (is_array($to)) {
			foreach ($to as $key => $val) {
				if (is_numeric($key)) {
					if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
						$this->addAddress('to', $val);
					}
				} elseif (strtolower($key) == '@cc') { //neu co CC
					$this->addAddress('cc', $val);
				} elseif (strtolower($key) == '@bcc') { // neu co BCC
					$this->addAddress('bcc', $val);
				} else {
					if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
						$this->addAddress('to', $val, $key);
					} elseif (filter_var($key, FILTER_VALIDATE_EMAIL)) {
						$this->addAddress('to', $key, $val);
					}
				}
			}
		}
		$this->_sendMail($body, $var);
		return true;
	}

	protected function _subject($subject = null)
	{
		$this->__subject = $subject;
		return $this;
	}

	protected function _body($body = null)
	{
		$this->__body = $body;
		return $this;
	}

	protected function _data($data = null)
	{
		$this->__data = $data;
		return $this;
	}

	protected function _message($message = null)
	{
		$this->__data['message'] = $message;
		return $this;
	}

	protected function _attach($files = null)
	{
		if ($files) {
			if (is_array($files)) {
				foreach ($files as $i => $file) {
					if (is_file($file)) $this->__attachments[] = $file;
				}
			} elseif (is_file($files)) $this->__attachments[] = $files;
		}
		return $this;
	}

	protected function _queue(int $time = 1)
	{
		$this->__checkConfig();
		if (config('mail.queue.enabled') == 'OFF')
			return $this->send();
		if (is_numeric($time) && $time >= 0) {
			$body = view($this->__body, $this->__data)->render();
			$this->__data = ['body' => $body];
			$this->__body = 'mails.queue';
			$emailJob = (new Job($this))->delay(Carbon::now()->addMinutes($time));
			dispatch($emailJob);
			return true;
		} else {
			return false;
		}
	}

	protected function _sendAfter(int $time = 1)
	{
		// Config::set('mail', static::$config);
		return $this->_queue($time);
	}

	public function __call($method, $params)
	{
		if (array_key_exists($method, $this->addressData)) {
			return $this->addAddress($method, ...$params);
		} elseif (method_exists($this, '_' . $method)) {
			return call_user_func_array([$this, '_' . $method], $params);
		} else {
			if (preg_match('/^[A-z0-9_]+$/i', $method)) {
				$this->__data[$method] = $params[0] ?? '';
			}
		}
		return $this;
	}
	public static function __callStatic($method, $params)
	{
		$mail = new static();
		return call_user_func_array([$mail, $method], $params);
	}
}
