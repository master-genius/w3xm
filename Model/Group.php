<?php
namespace Model;

use \Core\DB;

class Group {
    
    private $table = 'resource_group';


    public function groupList() {
        
        return DB::instance()->select($this->table, [
            'id', 'group_name'
        ],[
            'id[>]' => 0
        ]);
    }


}

