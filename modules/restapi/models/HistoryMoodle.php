<?php defined('BASEPATH') OR exit('No direct script access allowed');
require_once 'adapter/IHistoryAdapter.php';
require_once 'MGradebookMoodle.php';

class HistoryMoodle implements IHistoryAdapter{
    
    private $__gradebook;
    
    public function __construct(){
        $gradebook = new MGradebookMoodle();
        $this->__gradebook = $gradebook;
    }
    	
    public function getHistoryClasses($studentCode)
	{
	    return $listClasses = $this->__gradebook->getHistoryClasses($studentCode);
	}
	
	public function getHistoryClassDetails($studentCode, $classCode){
	    return $this->__gradebook->getHistoryClassDetails($studentCode, $classCode);
	}
	

}