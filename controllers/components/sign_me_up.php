<?php

class SignMeUpComponent extends Object {

	public $components = array('Session', 'Email', 'Auth');
	public $defaults = array(
		'activation_field' => 'activation_code',
		'useractive_field' => 'active',
		'username_field' => 'username',
		'email_field' => 'email',
	);
	public $helpers = array('Form', 'Html');
	public $name = 'SignMeUp';
	public $uses = array('SignMeUp');

	public function initialize(&$controller, $settings = array()) {
		$this->settings = array_merge($this->defaults, $settings);
		$this->controller = &$controller;
		$this->Auth->allow('register', 'activate');
	}

	private function __setUpEmailParams($user) {
		if (Configure::load('sign_me_up') === false) {
			die ('Could not load sign me up config');
		}

		if (Configure::read('SignMeUp')) {
			$email_settings = Configure::read('SignMeUp');
			foreach ($email_settings as $key => $setting) {
				$this->Email->{$key} = $setting;
			}
		}

		extract($this->settings);
		preg_match_all('/%(\w+?)%/', $this->Email->subject, $matches);
		foreach ($matches[1] as $match) {
			if (!empty($user[$match])) {
				$this->Email->subject = str_replace('%'.$match.'%', $user[$match], $this->Email->subject);
			}
		}

		$this->Email->to = $user[$username_field].' <'.$user[$email_field].'>';
		$this->controller->set(compact('user'));
	}

	public function register() {
		$this->__isLoggedIn();
		if (!empty($this->controller->data)) {
			extract($this->settings);
			$model = $this->controller->modelClass;
			$this->controller->loadModel($model);
			$this->controller->{$model}->set($this->controller->data);
			if ($this->controller->{$model}->validates()) {
				if (!empty($activation_field)) {
					$this->controller->data[$model][$activation_field] = $this->controller->{$model}->generateActivationCode($this->controller->data);
				}
				if ($this->controller->{$model}->save($this->controller->data, false)) {
					//If an activation field is supplied send out an email
					if (!empty($activation_field)) {
						$this->__sendActivationEmail($this->controller->data[$model]);
						$this->controller->redirect(array('action' => 'activate'));
					} else {
						$this->__sendWelcomeEmail($this->controller->data[$model]);
					}
					$this->controller->redirect($this->Auth->loginAction);
				}
			}
		}
	}

	private function __isLoggedIn() {
		if ($this->Auth->user()) {
			$this->controller->redirect($this->Auth->loginRedirect);
		}
	}

	private function __setTemplate($template) {
		if (!file_exists(ELEMENTS.'email/'.$this->Email->sendAs.'/'.$template.'.ctp')) {
			$this->log('SignMeUp Error "Template Not Found": '.ELEMENTS.'email/'.$this->Email->sendAs.'/'.$template.'.ctp');
		} else {
			$this->Email->template = $template;
			return true;
		}
	}

	protected function __sendActivationEmail($userData) {
		$this->__setUpEmailParams($userData);
		if ($this->__setTemplate(Configure::read('SignMeUp.activation_template'))) {
			if ($this->Email->send()) {
				return true;
			}
		}
	}

	protected function __sendWelcomeEmail($userData) {
		$this->__setUpEmailParams($userData);
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
			if (!empty($activation_code) || !empty($this->controller->data)) {
				$model = $this->controller->modelClass;
				$this->controller->loadModel($model);

				if (!empty($this->controller->data)) {
					$activation_code = $this->controller->data[$model][$activation_field];
				}

				$inactive_user = $this->controller->{$model}->find('first', array('conditions' => array($activation_field => $activation_code), 'recursive' => -1));
				if (!empty($inactive_user)) {
					$this->controller->{$model}->id = $inactive_user[$model]['id'];
					if (!empty($useractive_field)) {
						$data[$model][$useractive_field] = true;
					}
					$data[$model][$activation_field] = null;
					if ($this->controller->{$model}->save($data)) {
						$this->Session->setFlash('Thank you '.$inactive_user[$model][$username_field].', your account is now active');
						$this->controller->redirect($this->Auth->loginAction);
					}
				} else {
					$this->Session->setFlash('Sorry, that code is incorrect.');
				}
			}
		}
	}

}