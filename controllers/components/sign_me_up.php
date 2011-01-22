<?php

class SignMeUpComponent extends Object {

	public $components = array('Email', 'Auth');
	public $helpers = array('Form', 'Html');
	public $name = 'SignMeUp';
	public $uses = array('SignMeUp');

	function initialize(&$controller, $settings = array()) {
		$this->controller = &$controller;
		if ($this->Auth->user()) {
			$this->redirect($this->Auth->loginRedirect);
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
		$this->Email->sendAs = 'text';
		$this->Email->subject = str_replace('%username%', $user['username'], Configure::read('SignMeUp.subject'));
		$this->Email->to = $user['username'].' <'.$user['email'].'>';
		$this->Email->template = Configure::read('SignMeUp.template');
		$this->Email->xMailer = Configure::read('SignMeUp.xMailer');
		$this->controller->set(compact('user'));
	}

	public function register() {
		if (!empty($this->controller->data)) {
			$model = $this->controller->modelClass;
			$this->controller->loadModel($model);
			$this->controller->{$model}->set($this->controller->data);
			if ($this->controller->{$model}->validates()) {
				$this->data[$model]['activation_code'] = $this->controller->{$model}->generateActivationCode($this->controller->data);
				if ($this->controller->{$model}->save($this->controller->data, false) && $this->__sendSignupEmail($this->controller->data[$model])) {
					$this->controller->redirect(array('action' => 'activate'));
				}
			}
		}
	}

	protected function __sendSignupEmail($userData) {
		$this->__setUpEmailParams($userData);
		if ($this->Email->send()) {
			return true;
		}
	}

	public function activate($activation_code = '') {
		if (!empty($activation_code) || !empty($this->data)) {
			if (!empty($this->data)) {
				$activation_code = $this->data['UserRegistration']['activation_code'];
			}
			$inactive_user = $this->UserRegistration->find('first', array('conditions' => array('activation_code' => $activation_code), 'recursive' => -1));
			if (!empty($inactive_user)) {
				$this->UserRegistration->id = $inactive_user['UserRegistration']['id'];
				$data['UserRegistration']['active'] = true;
				$data['UserRegistration']['activation_code'] = null;
				if ($this->UserRegistration->save($data)) {
					$this->Session->setFlash('Thank you '.$inactive_user['UserRegistration']['username'].', your account is now active');
					$this->redirect($this->Auth->loginAction);
				}
			}
		}
	}

}

?>