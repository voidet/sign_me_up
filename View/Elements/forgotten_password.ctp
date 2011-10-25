<h2>Reset Your Password</h2>
<p>Please enter your email address below:</p>
<?php
echo $this->Form->create();
echo $this->Form->input('email');
echo $this->Form->end('Reset Password');