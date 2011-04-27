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

Sign Me Up also comes with 3 elements for your views, which don't have to be used. However in your views feel free to use the elements to create the Registration, Activation and Forgotten Password forms as:

	app/views/users/register.ctp
		<?php echo $this->element('register', array('plugin' => 'SignMeUp')); ?>

	app/views/users/activate.ctp
		<?php echo $this->element('activate', array('plugin' => 'SignMeUp')); ?>

	app/views/users/forgotten_password_.ctp
		<?php echo $this->element('forgotten_password', array('plugin' => 'SignMeUp')); ?>

Next up the plugin requires that you have a config file in 'app/config/sign_me_up.php'. SignMeUp configuration allows you to overwrite all default CakePHP email parameters by simply specifying the elements in the SignMeUp configuration array i.e change email sending to HTML format via setting the sendAs to HTML or change the email layout with 'layout' => 'myLayout'. The only thing that you would need to diverge from the Email Component settings with is the welcome and activate templates. You can set them with welcome_template and activation_template elements:

	<?php

	$config['SignMeUp'] = array(
		'from' => 'ExampleDomain.com <admin@exampledomain.com>',
		'layout' => 'default',
		'subject' => 'Welcome to ExampleDomain.com %username% using email address %email%',
		'sendAs' => 'text',
		'activation_template' => 'activate',
		'welcome_template' => 'welcome',
		'password_reset_template' => 'forgotten_password',
		'password_reset_subject' => 'Password reset from MyDomain.com',
		'new_password_template' => 'new_password',
		'new_password_subject' => 'Your new password from MyDomain.com',
		'xMailer' => 'ExampleDomain.com Email',
	);

Also note you can include fields in the subject line from your user model. Simply specify the field name you want placed in the subject line with %field_name%. Apart from that the only other things required is that you set up the email layout & views, examples being:

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

