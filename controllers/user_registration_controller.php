<?php

class UserRegistrationController extends AppController {

	public $helpers = array('Form', 'Html');
	public $name = 'UserRegistration';
	public $uses = array('SignMeUp.UserRegistration');

	public function beforeFilter() {
		$this->Auth->allow('register', 'activate');
		return parent::beforeFilter();
	}

	public function register() {
		if (!empty($this->data)) {
			$this->UserRegistration->set($this->data);
			if ($this->UserRegistration->validates()) {
				$this->data['UserRegistration']['activation_code'] = $this->UserRegistration->generateActivationCode();
				if ($this->UserRegistration->save($this->data, false)) {
					$this->redirect(array('action' => 'activate'));
				}
			}
		}
	}

	public function activate($activation_code = '') {
		if (!empty($activation_code) || !empty($this->data)) {
			if (!empty($this->data)) {
				$activation_code = $this->data['UserRegistration']['activation_code'];
			}
			$inactive_user = $this->UserRegistration->find('first', array('conditions' => array('activation_code' => $activation_code), 'recursive' => -1));
			if (!empty($inactive_user)) {
				$inactive_user['UserRegistration']['active'] = true;
				$inactive_user['UserRegistration']['activation_code'] = null;
				if ($this->UserRegistration->save($inactive_user)) {
					$this->Session->setFlash('Thank you '.$inactive_user['UserRegistration']['username'].', your account is now active');
					$this->redirect($this->Auth->loginAction);
				}
			}
		}
	}

}

?>