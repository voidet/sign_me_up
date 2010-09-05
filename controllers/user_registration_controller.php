<?php

class UserRegistrationController extends AppController {

	public $helpers = array('Form', 'Html');
	public $name = 'UserRegistration';
	public $uses = array('SignMeUp.UserRegistration');

	public function beforeFilter() {
		$this->Auth->allow('register');
		return parent::beforeFilter();
	}

	public function register() {
		if (!empty($this->data)) {
			$this->UserRegistration->set($this->data);
			if ($this->UserRegistration->validates()) {
				$this->data['UserRegistration']['activation_code'] = $this->UserRegistration->generateActivationCode();
				$this->UserRegistration->save($this->data, false);
			}
		}
	}

}

?>