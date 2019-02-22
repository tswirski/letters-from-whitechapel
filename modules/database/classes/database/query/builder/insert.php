<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Insert extends Kohana_Database_Query_Builder_Insert 
{

    /**
    * Ustawia dane do zapytania - skrot do columns()->values()
    *
    * @param array $data
    * @return Database_Query_Builder_Insert
    */
    public function insert_row(array $data)
    {
        if( ! $this->_table)
            throw new Kohana_Exception('You must first set table name');

            return $this
                ->columns(array_keys($data))
                ->values(array_values($data));
    }
    
    /**
     * Pozwala wstawić wiele wierszy w jednym zapytaniu.
     * @param array $data Tablica-Tablic. Pierwszy wymiar to tablica numeryczna, drugi to tablica asocjacyjna.
     * @return \Database_Query_Builder_Insert
     * @throws Kohana_Exception 
     */
    public function insert_rows(array $data)
    {
        if( ! $this->_table)
            throw new Kohana_Exception('You must first set table name');

        $this->columns(array_keys($data[0]));
        
        foreach($data as $d)
        {
            $this->values(array_values($d));
        }
        return $this;
    }
        
}
