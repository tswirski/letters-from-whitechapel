<?php

class Controller_Activation extends Controller{
    public function action_verify(){
        return (new Controller_Production($this->request, $this->response))->action_index();
    }
}