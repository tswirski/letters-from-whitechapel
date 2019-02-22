<?php defined('SYSPATH') or die('No direct script access.');

abstract class Database_Query_Builder_Where extends Kohana_Database_Query_Builder_Where {
    
   /**
	* You can now pass an array. Example: array('column_name' => 'ASC');
	*/
    public function order_by($column, $direction = NULL)
    {
        if(is_array($column))
        {
            foreach($column as $c => $d)
            {
                $this->order_by($c, $d);
            }
        }
        else
        {
            return parent::order_by($column, $direction);
        }
    }
}
