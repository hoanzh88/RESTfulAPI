<?php defined('BASEPATH') OR exit('No direct script access allowed');



class Milapv_user extends CI_Model {



	protected $_table ="ilav2db_jos_users";

	

	public function __construct()	{

	  $this->load->database(); 

	}

	

	public function getById($id)

	{

		$this->db->select($id);

		$this->db->where('id', $id);

		return $this->db->get($this->_table)->row();

	}

	

	public function checkLogin($username, $password)

	{

	    $user = $this->getByUsername($username);

	    if (! $user || ($user && empty($user->email)))

	    {  

	        // Not found user OR User don't has an email

	        return false;

	    }

	    else

	    {

	        // Expected User: is exist and has email

	        $return = false;

	        $userObj = new stdClass();

	        $userObj->id = $user->id;

	        $userObj->username = $user->username;

	        $userObj->name = $user->name;

	        $userObj->block = $user->block;

	        $userObj->firstname = $user->firstname;

	        $userObj->block = $user->block;

	        $userObj->lastname = $user->lastname;

	        $userObj->endUserAgreement = $user->endUserAgreement;

	        	        

	        if ($user->password == md5($password))

	        {

	            // This is MD5 password only: md5($password)

	            $return = $userObj;

	        }

	        else

	        {

	            // This is MD5 password with salt: md5($userPassword.$userSalt);

	            $passArr = explode(":", $user->password);	            

	            //p($passArr);

	            if (count($passArr) > 1)

	            {

	                $userPassword = $passArr[0];

	                $salt = $passArr[1];

	                $pass_salt = $password.$salt;

	                if ($userPassword == md5($password.$salt)){

	                    $return = $userObj;

	                }

	            }	            

	        }	 

	        return $return;

	    }      

	}	

	

	public function getByUsername($username)

	{

	    $this->db->select();

	    $this->db->where('username', $username);

	    return $this->db->get($this->_table)->row();

	}

	

	public function update($data, $username){

	    $this->db->where('username', $username);

	    $this->db->update($this->_table, $data);

	    if ($this->db->affected_rows() === -1){

	        return false;

	    }

	    return true;

	}

	

	public function addToken($username){

		date_default_timezone_set('Asia/Ho_Chi_Minh');

	    if ($username){

	        $date = new Datetime();

	        $timestamp = strtotime($date->format('Y-m-d H:i:s'));	        

	        $token = md5($username.$timestamp);

	        $data = array(

	            'token' => $token,

	            'tokenTime' => $timestamp

			);

    	    $this->db->where('username', $username);

    	    $this->db->update($this->_table, $data);

    	    if ($this->db->affected_rows() > 0){

    	        return $token;

    	    }

	    }

	    return null;

	}

	

	public function checkToken($tokenKey){

	    date_default_timezone_set('Asia/Ho_Chi_Minh');

        $this->db->select('id, username, token, tokenTime');

        $this->db->where('token', $tokenKey);

        $data = $this->db->get($this->_table)->row();

        if ($data && $data->token){	            

            $token = $data->token;

            

            $timeFirst = $data->tokenTime;

            $timeSecond = strtotime("now");

            $differenceInSeconds = $timeSecond - $timeFirst;

            

            $maxLifeTime = 3 * 60 * 60;  // 3h to seconds

            	            	            

            // Step 1: Check token is correct 

            // Step 2: Validate token live limit 3 hours

            if ($tokenKey == $token && $differenceInSeconds <= $maxLifeTime){

                return $tokenKey;

            }else{

                return false;

            }

        }

        return false;

	}

	

	public function cleanToken($tokenKey){

	    $data = array(

	        'token' => NULL,

	        'tokenTime' => NULL

	    );

	    $this->db->where('token', $tokenKey);

	    $this->db->update($this->_table, $data);

	}

	

	public function updatePasswordByToken($data, $token){

	    $this->db->where('token', $token);

	    $this->db->update($this->_table, $data);

	    if ($this->db->affected_rows() === -1){

	        return false;

	    }

	    return true;

	}

	

	// For TESTING only

	/* public function testUser($username)

	{

	    $this->db->select()

		->where('username', $username);		

		return $this->db->get($this->_table)->row();

	}

	

	public function testLogin($username, $password)

	{

	    $this->db->select('id, name as fullname')

	    ->where(array(

	        'username' => $username,

	        'passwordMD5' => $password

	    ));

	    return $this->db->get($this->_table)->row();

	} */

	

}