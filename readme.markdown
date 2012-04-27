#CakePHP Sign Me Up Plugin

Sign Me Up is a CakePHP plugin that takes out 99% of the work needed to develop a user registration, activation & forgotten passwords system. The plugin is easily installed and settings/methods can be easily overwritten to provide custom validation etc.

##Installation
Install the plugin:

	cd myapp/app/Plugin/
	git clone git@github.com:voidet/sign_me_up.git sign_me_up

To attach the plugin to a particular model (User/Member/Pimp) simply add in the plugin's component in your chosen controller & model:

	class UsersController extends AppController {

		public $components = array('SignMeUp.SignMeUp');

		public function beforeFilter() {
			$this->Auth->allow(array('login', 'forgotten_password', 'register', 'activate'));
			parent::beforeFilter();
		}

And in the User model app/Model/User.php

	class User extends AppModel {

		public $actsAs = array('SignMeUp.SignMeUp');

Next up create the register, activate & forgotten password methods in your controller via:

	public function register() {
		$this->SignMeUp->register();
	}

	public function activate() {
		$this->SignMeUp->activate();
	}

	public function forgotten_password() {
		$this->SignMeUp->forgottenPassword();
	}

Sign Me Up also comes with 3 elements for your views, which don't have to be used. However in your views feel free to use the elements to create the Registration, Activation and Forgotten Password forms as:

	app/View/Users/register.ctp
		<?php echo $this->element('register', array(), array('plugin' => 'SignMeUp')); ?>

	app/View/Users/activate.ctp
		<?php echo $this->element('activate', array(), array('plugin' => 'SignMeUp')); ?>

	app/View/Users/forgotten_password_.ctp
		<?php echo $this->element('forgotten_password', array(), array('plugin' => 'SignMeUp')); ?>

Currently Forgotten Passwords are based on the user's email address entered into the form. If there is any request for this to be based on another field I will review.

Next up the plugin requires that you have a config file in 'app/Config/sign_me_up.php'. SignMeUp configuration allows you to overwrite all default CakePHP email parameters by simply specifying the elements in the SignMeUp configuration array i.e change email sending to HTML format via setting the sendAs to HTML or change the email layout with 'layout' => 'myLayout'. The only thing that you would need to diverge from the Email Component settings with is the welcome and activate templates. You can set them with welcome_template and activation_template elements:

	<?php

	$config['SignMeUp'] = array(
		'activation_field' => 'activation_code',
		'useractive_field' => 'active',
		'login_after_activation' => false,
		'welcome_subject' => 'Welcome',
		'activation_subject' => 'Please Activate Your Account',
		'password_reset_field' => 'password_reset',
		'username_field' => 'username',
		'email_field' => 'email',
		'email_layout' => 'default',
 		'password_field' => 'password',
		'activation_template' => 'activate',
		'welcome_template' => 'welcome',
		'password_reset_template' => 'forgotten_password',
		'password_reset_subject' => 'Password Reset Request',
		'new_password_template' => 'recovered_password',
		'new_password_subject' => 'Your new Password'
	);

SignMeUp 2.0 uses CakeEmail, so you will need to add in your email settings into app/Config/email.php under the $signMeUp config:

	public $signMeUp = array(
		'transport' => 'Mail',
		'from' => 'me@me.com',
		//'charset' => 'utf-8',
		//'headerCharset' => 'utf-8',
	);

Also note you can include fields in the subject line from your user model. Simply specify the field name you want placed in the subject line with %field_name%. Apart from that the only other things required is that you set up the email layout & views, examples being:

app/views/layouts/email/text/default.ctp

	<?php echo $content_for_layout; ?>

app/views/elements/email/text/activate.ctp

	Welcome <?php echo $user['username']; ?>,

	In order to get started please click on the following link to activate your account:

	<?php echo Router::url(array('action' => 'activate', 'activation_code' => $user['activation_code']), true)."\n"; ?>

	We look forward to seeing you!
	Regards,
	MyDomain.com Staff

app/views/elements/email/text/welcome.ctp

	Welcome <?php echo $user['username']; ?>,

	Thanks for registering! See you inside :)

	We look forward to seeing you!
	Regards,
	MyDomain.com Staff

app/views/elements/email/text/forgotten_password.ctp

	Hi <?php echo $user['username']; ?>,

	Someone (hopefully you) has requested a password reset on your account. In order to reset your password please click on the link below:

	<?php echo $this->Html->link('Reset your password', Router::url(array('action' => 'forgotten_password', 'password_reset' => $user['password_reset']), true)); ?>

	Regards,
	MyDomain.com Staff

app/views/element/email/text/new_password.ctp

	Hello <?php echo $user['username']; ?>,

	A new password has been generated hopefully by you on the MyDomain.com website. Your new password, which you should change immediately is:

	<?php echo $password; ?>

	We look forward to seeing you!
	Regards,
	MyDomain.com Staff

##The Schema

In order to set up your users table for activation, registration, or forgotten passwords, please refer to the configuration above. For example if you want to have the forgotten password functionality, you need to have a field in your DB called password_reset, or whatever you choose in your configuration. Both password reset fields, or activation fields should be varchar with a length of 40.

##Example Routes

	Router::connect('/register', array('controller' => 'users', 'action' => 'register'));
	Router::connect('/activate', array('controller' => 'users', 'action' => 'activate'));
	Router::connect('/activate/:activation_code', array('controller' => 'users', 'action' => 'activate'), array('pass' => 'activation_code'));
	Router::connect('/forgotten_password/:password_reset', array('controller' => 'users', 'action' => 'forgotten_password'), array('pass' => 'password_reset_code'));
	Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
	Router::connect('/logout', array('controller' => 'users', 'action' => 'logout'));

##Note

Any extra validations in your model will be used instead of the ones included in Sign Me Up. So if you don't like the validations that come with the plugin, simply create your own in the model.