<?php

class SignMeUpComponent extends Object {

	public $components = array('Session', 'Email', 'Auth');
	public $defaults = array(
		'activation_field' => 'activation_code',
		'useractive_field' => 'active',
	);
	public $helpers = array('Form', 'Html');
	public $name = 'SignMeUp';
	public $uses = array('SignMeUp');

	function initialize(&$controller, $settings = array()) {
		$this->settings = array_merge($this->defaults, $settings);
		$this->controller = &$controller;
		if ($this->Auth->user()) {
			$this->controller->redirect($this->Auth->loginRedirect);
		} else {
			$this->Auth->allow('register', 'activate');
		}
	}

	private function __setUpEmailParams($user) {
		if (Configure::load('sign_me_up') === false) {
			die ('Could not load sign me up config');
		}
		$this->Email->delivery = 'mail';
		$this->Email->from = Configure::read('SignMeUp.from_email');
		if (Configure::read('SignMeUp.email_layout')) {
			$this->Email->layout = Configure::read('SignMeUp.email_layout');
		}
		$this->Email->sendAs = Configure::read('SignMeUp.type');
		$this->Email->subject = str_replace('%username%', $user['username'], Configure::read('SignMeUp.subject'));
		$this->Email->to = $user['username'].' <'.$user['email'].'>';
		$this->Email->xMailer = Configure::read('SignMeUp.xMailer');
		$this->controller->set(compact('user'));
	}

	public function register() {
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

	protected function __sendActivationEmail($userData) {
		$this->__setUpEmailParams($userData);
		$this->Email->template = Configure::read('SignMeUp.activation_template');
		if ($this->Email->send()) {
			return true;
		}
	}

	protected function __sendWelcomeEmail($userData) {
		$this->__setUpEmailParams($userData);
		$this->Email->template = Configure::read('SignMeUp.welcome_template');
		if ($this->Email->send()) {
			return true;
		}
	}

	public function activate() {
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
						$this->Session->setFlash('Thank you '.$inactive_user[$model]['username'].', your account is now active');
						$this->controller->redirect($this->Auth->loginAction);
					}
				} else {
					$this->Session->setFlash('Sorry, that code is incorrect.');
				}
			}
		}
	}

}

?>