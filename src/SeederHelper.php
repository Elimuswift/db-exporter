<?php

namespace Elimuswift\DbExporter;

trait SeederHelper
{
    
    public function saveData($table, $item) 
    {
    	// If have primary id
    	if(isset($item['id'])) 
    	{
    		if(\DB::table($table)->where('id', $item['id'])->count() > 0) 
    		{
    			\DB::table($table)->where('id', $item['id'])->update($item);
    		} 
    		else 
    		{
    			\DB::table($table)->insert($item);
    		}
        } 
        else 
        {
        	$ids = collect($item)->filter(function($item, $key) {
        		return str_contains($key, '_id');
        	})->keys()->values();

        	// If there isnt any column with _id, so check that every column matches
        	if($ids->count() <= 0) {
        		$ids = collect($item)->keys()->values();
        	}
        	$object = \DB::table($table);
        	foreach ($ids as $id) {
        		$object = $object->where($id, $item[$id]);
        	}

        	// save or update
        	if($object->count() > 0) 
    		{
    			$object->update($item);
    		} 
    		else 
    		{
    			$object->insert($item);
    		}
        	
        }
    }   
}
