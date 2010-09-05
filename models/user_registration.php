<?php

App::import('Model', 'User');

class UserRegistration extends User {

	public $name = 'UserRegistration';
	public $useTable = 'users';
	public $validate = array(
		'username' => array(
			'pattern' => array(
				'rule' => array('custom','/[a-zA-Z0-9\_\-]{4,30}$/i'),
				'message'=> 'Usernames must be 4 characters or longer with no spaces.'
			),
			'usernameExists' => array(
				'rule' => 'isUnique',
				'message' => 'Sorry, this username already exists'
			),
		),
		'email' => array(
			'validEmail' => array(
				'rule' => array('email', true),
				'message' => 'Please supply a valid & active email address'
			),
			'emailExists' => array(
				'rule' => 'isUnique',
				'message' => 'Sorry, this email address is already in use'
			),
		),
		'password1' => array(
			'match' => array(
				'rule' => array('confirmPassword', 'password1', 'password2'),
				'message' => 'Passwords do not match'
			),
			'minRequirements' => array(
				'rule' => array('minLength', 6),
				'message' => 'Passwords need to be at least 6 characters long'
			)
		),
	);

	public function confirmPassword($field, $password1, $password2) {
		if ($this->data['UserRegistration'][$password1] == $this->data['UserRegistration'][$password2]) {
			$this->data['UserRegistration']['password'] = $this->data['UserRegistration']['password1'];

			$UserModel = ClassRegistry::init('User');
			$this->data = $UserModel->hashPasswords($this->data, $this->alias);
			return true;
		}
	}

	public function generateActivationCode() {
		return Security::hash(serialize($this->data).microtime().rand(1,100), null, true);
	}

}

?>