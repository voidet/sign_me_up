<?php

//Todo look into why cake2 appends querystring for its current route
echo $this->Form->create(null, array('url' => $this->here));
echo $this->Form->input('username');
echo $this->Form->input('email');
echo $this->Form->input('password1', array('label' => 'Password', 'type' => 'password'));
echo $this->Form->input('password2', array('label' => 'Confirm password', 'type' => 'password'));
echo $this->Form->end('Register');