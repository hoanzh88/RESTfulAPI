<?php
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Curl.php';
require APPPATH . 'helpers/global_helper.php';

class Restapi extends REST_Controller {

    protected $_table ="";

    public function __construct() {
       parent::__construct();

       $this->load->database();

       $this->load->model(array('xxx','xxx'));   

       $this->load->helper(array('form'));

       $this->load->helper('url');

       $this->load->library('form_validation');

       $this->load->library('session');
    }

	public function project_get(){

        $classid = $_GET['classId'];
        $registrationid = $_GET['registrationId'];
		$classprojects = '';
        $this->response($classprojects, REST_Controller::HTTP_OK);
    }

    public function login_post(){

        $json = file_get_contents('php://input');
		$obj = json_decode($json,true);
		$username = $obj['username'];
		$password = $obj['password'];

	    if ($username && $password){

	        $user = $this->Milapv_user->checkLogin($username, $password);

	        if ($user){
                $token = md5($username.$password);
                $SuccessLoginMsg = 'Data Matched';
                // $SuccessLoginJson = json_encode($SuccessLoginMsg);
                $data = array(
                    'msg'=> $SuccessLoginMsg,
                    'token'=> $token                
					);            

                // echo $token;
	        }else{
                $InvalidMSG = 'Invalid Username or Password! Please Try Again' ;
                $data = array(
                    'msg'=> $InvalidMSG
                );
                // echo $InvalidMSGJSon ;
	        }
        }
        $this->response($data, REST_Controller::HTTP_OK);
    }
}