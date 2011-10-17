<h2>Activate Your Account</h2>
<p>Please paste your activation code below:</p>
<?php
echo $this->Form->create();
echo $this->Form->input('activation_code');
echo $this->Form->end('Activate Account');
?>