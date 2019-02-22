<?php defined('SYSPATH') or die('No direct script access.');

class Ajax_Object
{
    public static function create() { return new Ajax_Object; }
    public function as_array()
    { 
        return array(
            'eval_before' => $this->eval_before,
            'eval_after' => $this->eval_after,
            'target' => $this->target,
            'action' => $this->action,
            'content' => $this->content,
        );
    }
    
    protected $allowed_actions = 
            array('inject','replace','before','after','prepend','append', 'raw');
    
    public $eval_before;
    public $eval_after;
    public $target;
    public $action = 'inject';
    public $content;
    
    public function eval_before($eval)
    {
        $this->eval_before = str_replace(array("\r", "\n"), '', preg_replace('!\s+!', ' ', $eval));;
        return $this;
    }
    public function eval_after($eval)
    {
        $this->eval_after = str_replace(array("\r", "\n"), '', preg_replace('!\s+!', ' ', $eval));;
        return $this;
    }
    public function target($target)
    {
        $this->target = $target;
        return $this;
    }
    public function action($action)
    {
        if(! in_array($action, $this->allowed_actions)){
            throw new Kohana_Exception('Nieprawidłowy typ akcji ajax');
        }
        $this->action = $action;
        return $this;
    }
    public function content($content)
    {
        /* Usuwamy wielo-spacje oraz znaczniki końca linii z zawartości */
        $this->content = str_replace(array("\r", "\n"), '', preg_replace('!\s+!', ' ', $content));
     
        return $this;
    }
}