<?php defined('BASEPATH') OR exit('No direct script access allowed');
require_once 'adapter/IHistoryAdapter.php';
require_once 'MGradebookIlaweb.php';

class HistoryIlaweb implements IHistoryAdapter{
    
    private $__gradebook;
    
    public function __construct(){
        $gradebook = new MGradebookIlaweb();
        $this->__gradebook = $gradebook;
    }
    	
    public function getHistoryClasses($studentCode, $moodleClassesCodes = '')
	{
	    return $this->__gradebook->getHistoryClasses($studentCode, $moodleClassesCodes);
	}
	
	public function getHistoryClassDetails($studentCode, $classCode){
	    return $this->__gradebook->getHistoryClassDetails($studentCode, $classCode);
	}
	

}