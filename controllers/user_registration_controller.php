<?php

App::import('Controller', 'Users');

class UserRegistrationController extends UsersController {

	public $components = array('Email');
	public $helpers = array('Form', 'Html');
	public $name = 'UserRegistration';
	public $uses = array('SignMeUp.UserRegistration');

	public function beforeFilter() {
		parent::beforeFilter();
		if ($this->Auth->user()) {
			$this->redirect($this->Auth->loginRedirect);
		} else {
			$this->Auth->allow('register', 'activate');
		}
	}

	private function __setUpEmailParams() {
		$this->Email->delivery = 'mail';
		$this->Email->from = Configure::read('Site.email');
		if (Configure::read('Site.email_layout')) {
			$this->Email->layout = Configure::read('Site.email_layout');
		}
		$this->Email->sendAs = 'text';
	}

	public function register() {
		if (!empty($this->data)) {
			$this->UserRegistration->set($this->data);
			if ($this->UserRegistration->validates()) {
				$this->data['UserRegistration']['activation_code'] = $this->UserRegistration->generateActivationCode();
				if ($this->UserRegistration->save($this->data, false) && $this->__sendSignupEmail($this->data['UserRegistration'])) {
					$this->redirect(array('action' => 'activate'));
				}
			}
		}
	}

	protected function __sendSignupEmail($userData) {
		$this->__setUpEmailParams();
		parent::__sendSignupEmail($userData);
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