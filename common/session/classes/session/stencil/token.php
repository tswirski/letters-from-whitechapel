<?php defined('SYSPATH') or die('No direct script access.');


class Session_Stencil_Token extends Abstract_Session_Stencil
{
    /* @var string Token (losowy ciąg znaków cyfry+litery) dla wygenerowanego obrazka */
    public $token;
}