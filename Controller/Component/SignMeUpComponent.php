<?php

App::uses('Component', 'RequestHandlerComponent');

class SignMeUpComponent extends Component {

	public $components = array('Session', 'Email', 'Auth', 'RequestHandler');
	public $defaults = array(
		'activation_field' => 'activation_code',
		'useractive_field' => 'active',
		'welcome_subject' => 'Welcome',
		'activation_subject' => 'Please Activate Your Account',
		'password_reset_field' => 'password_reset',
		'username_field' => 'username',
		'email_field' => 'email',
		'password_field' => 'password',
		'activation_template' => 'activate',
		'welcome_template' => 'welcome',
		'password_reset_template' => 'forgotten_password',
		'password_reset_subject' => 'Password Reset Request',
		'new_password_template' => 'recovered_password',
		'new_password_subject' => 'Your new Password'
	);
	public $helpers = array('Form', 'Html');
	public $name = 'SignMeUp';
	public $uses = array('SignMeUp');

	function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->settings = $settings;
	}

	public function initialize(&$controller) {
		$this->__loadConfig();
		$settings = array_merge($this->settings, Configure::read('SignMeUp'));
		$this->settings = array_merge($this->defaults, $settings);
		$this->requestHandler = new CakeRequest();
		$this->data = $this->requestHandler->data(null);
		$this->controller = $controller;
	}

	private function __loadConfig() {
		if (Configure::load('sign_me_up', 'default', false) === false) {
			die('Could not load sign me up config');
		}
	}

	private function __setUpEmailParams($user) {
		$this->__loadConfig();
		if (Configure::read('SignMeUp')) {
			foreach ($this->settings as $key => $setting) {
				$this->Email->{$key} = $setting;
			}
		}

		extract($this->settings);
		if (empty($user[$username_field])) {
			$this->Email->to = $user[$email_field].' <'.$user[$email_field].'>';
		} else {
			$this->Email->to = $user[$username_field].' <'.$user[$email_field].'>';
		}
		$this->controller->set(compact('user'));
	}

	private function __parseEmailSubject($action = '', $user = array()) {
		$subject = $this->Email->{$action.'_subject'};
		preg_match_all('/%(\w+?)%/', $subject, $matches);
		$foundMatch = false;
		foreach ($matches[1] as $match) {
			if (!empty($user[$match])) {
				$foundMatch = true;
				$this->Email->subject = str_replace('%'.$match.'%', $user[$match], $subject);
			}
		}

		if ($foundMatch === false) {
			$this->Email->subject = $subject;
		}
	}

	public function register() {
		$this->__isLoggedIn();
		if (!empty($this->data)) {
			extract($this->settings);
			$model = $this->controller->modelClass;
			$this->controller->loadModel($model);
			$this->controller->{$model}->set($this->data);

			if (CakePlugin::loaded('Mongodb')) {
				$this->controller->{$model}->Behaviors->attach('Mongodb.SqlCompatible');
			}

			if ($this->controller->{$model}->validates()) {
				if (!empty($activation_field)) {
					$this->data[$model][$activation_field] = $this->controller->{$model}->generateActivationCode($this->data);
				} elseif (!empty($useractive_field)) {
					$this->data[$model][$useractive_field] = true;
				}

				if ($this->controller->{$model}->save($this->data, false)) {
					//If an activation field is supplied send out an email
					if (!empty($activation_field)) {
						$this->__sendActivationEmail($this->data[$model]);
						if (!$this->controller->request->is('ajax')) {
							$this->controller->redirect(array('action' => 'activate'));
						} else {
							return true;
						}
					} else {
						$this->__sendWelcomeEmail($this->data[$model]);
					}
					if (!$this->controller->request->is('ajax')) {
						$this->controller->redirect($this->Auth->loginAction);
					} else {
						return true;
					}
				}
			}
		}
	}

	private function __isLoggedIn() {
		if ($this->Auth->user()) {
			if (!$this->controller->request->is('ajax')) {
				$this->controller->redirect($this->Auth->loginRedirect);
			}
		}
	}

	private function __setTemplate($template) {
		if (!file_exists(APP.'View/Emails/'.$this->Email->sendAs.'/'.$template.'.ctp')) {
			die('SignMeUp Error "Template Not Found": '.APP.'View/Emails/'.$this->Email->sendAs.'/'.$template.'.ctp');
		} else {
			$this->Email->template = $template;
			return true;
		}
	}

	protected function __sendActivationEmail($userData) {
		$this->__setUpEmailParams($userData);
		$this->__parseEmailSubject('activation', $userData);
		if ($this->__setTemplate(Configure::read('SignMeUp.activation_template'))) {
			if ($this->Email->send()) {
				return true;
			}
		}
	}

	protected function __sendWelcomeEmail($userData) {
		$this->__setUpEmailParams($userData);
		$this->__parseEmailSubject('welcome', $userData);
		if ($this->__setTemplate(Configure::read('SignMeUp.welcome_template'))) {
			if ($this->Email->send()) {
				return true;
			}
		}
	}

	public function activate() {
		$this->__isLoggedIn();
		extract($this->settings);
		//If there is no activation field specified, don't bother with activation
		if (!empty($activation_field)) {

			//Test for an activation code in the parameters
			if (!empty($this->controller->params[$activation_field])) {
				$activation_code = $this->controller->params[$activation_field];
			}

			//If there is an activation code supplied, either in _POST or _GET
			if (!empty($activation_code) || !empty($this->data)) {
				$model = $this->controller->modelClass;
				$this->controller->loadModel($model);

				if (!empty($this->data)) {
					$activation_code = $this->data[$model][$activation_field];
				}

				$inactive_user = $this->controller->{$model}->find('first', array('conditions' => array($activation_field => $activation_code), 'recursive' => -1));
				if (!empty($inactive_user)) {
					$this->controller->{$model}->id = $inactive_user[$model][$this->controller->{$model}->primaryKey];
					if (!empty($useractive_field)) {
						$data[$model][$useractive_field] = true;
					}
					$data[$model][$activation_field] = null;
					if ($this->controller->{$model}->save($data)) {
						$this->__sendWelcomeEmail($inactive_user['User']);
						if (!$this->controller->request->is('ajax')) {
							$user = '';
							if (!empty($inactive_user[$model][$username_field])) {
								$user = ' '.$inactive_user[$model][$username_field];
							}
							$this->Session->setFlash('Thank you'.$user.', your account is now active');
							$this->controller->redirect($this->Auth->loginAction);
						} else {
							return true;
						}
					}
				} else {
					$this->Session->setFlash('Sorry, that code is incorrect.');
				}
			}
		}
	}

	public function forgottenPassword() {
		extract($this->settings);
		$model = $this->controller->modelClass;
		if (!empty($this->data[$model])) {
			$data = $this->data[$model];
		}

		//User has code to reset their password
		if (!empty($this->controller->params[$password_reset_field])) {
			$this->__generateNewPassword($model);
		} elseif (!empty($password_reset_field) && !empty($data['email'])) {
			$this->__requestNewPassword($data, $model);
		}
	}

	private function __generateNewPassword($model = '') {
		extract($this->settings);
		$user = $this->controller->{$model}->find('first', array(
			'conditions' => array($password_reset_field => $this->controller->params[$password_reset_field]),
			'recursive' => -1
		));

		if (!empty($user)) {
			$password = substr(Security::hash(String::uuid(), null, true), 0, 8);
			$user[$model][$password_field] = Security::hash($password, null, true);
			$user[$model][$password_reset_field] = null;
			$this->controller->set(compact('password'));
			if ($this->controller->{$model}->save($user) && $this->__sendNewPassword($user[$model])) {
				if (!$this->controller->request->is('ajax')) {
					$this->Session->setFlash('Thank you '.$user[$model][$username_field].', your new password has been emailed to you.');
					$this->controller->redirect($this->Auth->loginAction);
				} else {
					return true;
				}
			}
		}
	}

	private function __sendNewPassword($user = array()) {
		$this->__setUpEmailParams($user);
		if ($this->__setTemplate(Configure::read('SignMeUp.new_password_template'))) {
			$this->Email->subject = $this->Email->new_password_subject;
			if ($this->Email->send()) {
				return true;
			}
		}
	}

	private function __requestNewPassword($data = array(), $model = '') {
		extract($this->settings);
		$this->controller->loadModel($model);
		$user = $this->controller->{$model}->find('first', array('conditions' => array('email' => $data['email']), 'recursive' => -1));
		if (!empty($user)) {
			$user[$model][$password_reset_field] = md5(String::uuid());

			if ($this->controller->{$model}->save($user) && $this->__sendForgottenPassword($user[$model])) {
				if (!$this->controller->request->is('ajax')) {
					$this->Session->setFlash('Thank you. A password recovery email has now been sent to '.$data['email']);
					$this->controller->redirect($this->Auth->loginAction);
				} else {
					return true;
				}
			}
		} else {
			$this->controller->{$model}->invalidate('email', 'No user found with email: '.$data['email']);
		}
	}

	private function __sendForgottenPassword($user = array()) {
		$this->__setUpEmailParams($user);
		if ($this->__setTemplate(Configure::read('SignMeUp.password_reset_template'))) {
			$this->Email->subject = $this->Email->password_reset_subject;
			if ($this->Email->send()) {
				return true;
			}
		}
	}

}