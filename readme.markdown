#CakePHP Sign Me Up Plugin

Sign Me Up is a CakePHP plugin that takes out 99% of the work needed to develop a user registration & activation system. The plugin is easily installed and settings/methods can be easily overwritten to provide custom validation etc.

##Installation
Install the plugin:

	cd myapp/app/plugins/
	git clone git@github.com:voidet/sign_me_up.git sign_me_up

To attach the plugin to a particular model (User/Member/Pimp) simply add in the plugin's component in your chosen controller & model:

	class UsersController extends AppController {

		public $components = array('SignMeUp.SignMeUp');


	class User extends AppModel {

		public $actsAs = array('SignMeUp.SignMeUp');

Next up create the register & activate methods in your controller via:

	public function register() {
		$this->SignMeUp->register();
	}

	public function activate() {
		$this->SignMeUp->activate();
	}

Sign Me Up also comes with 2 elements for your views, which don't have to be used. However in your views feel free to use the elements to create the Registration and Activation forms as:

app/views/users/register.ctp
	<?php echo $this->element('register', array('plugin' => 'SignMeUp')); ?>

app/views/users/activate.ctp
	<?php echo $this->element('activate', array('plugin' => 'SignMeUp')); ?>

Next up the plugin requires that you have a config file in 'app/config/sign_me_up.php', examples of what can be changed are:

	<?php

	$config['SignMeUp'] = array(
		'from_email' => 'Mydomain.com <admin@mydomain.com>',
		'email_layout' => 'default',
		'subject' => 'Welcome to Mydomain.com %username%',
		'template' => 'welcome',
		'type' => 'text',
		'xMailer' => 'Mydomain.com Email',
	);

Apart from that the only other things required is that you set up the email layout & views, examples being:

app/views/layouts/email/text/default.ctp
	<?php echo $content_for_layout; ?>

app/views/elements/email/text/welcome.ctp
	Welcome <?php echo $user['username']; ?>,

	In order to get started please click on the following link to activate your account:

	<?php echo Router::url(array('action' => 'activate', 'activation_code' => $user['activation_code']), true)."\n"; ?>

	We look forward to seeing you!
	Regards,
	MyDomain.com Staff

##Note

Any extra validations in your model will be used instead of the ones included in Sign Me Up. So if you don't like the validations that come with the plugin, simply create your own in the model.

