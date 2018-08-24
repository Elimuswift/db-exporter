<?php

namespace Elimuswift\DbExporter;


class SeederHelper
{
    
    public function saveData($table, $item) {
        try {
            DB::table($table)->insert($item);
        } catch (\Exception $e) {
            if(isset($item['id'])) {
                DB::table($table)->where('id', $item['id'])->update($item);
            }
        }
    }   
}
