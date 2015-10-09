<?php

/* Require Slim and plugins */
require 'Slim/Slim.php';

/* Register autoloader and instantiate Slim */
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["status"] = false;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echo json_encode($response);
        $app->stop();
    }
}
function login($name,$password)
{
	$filePath = 'Config/serviceconfig.txt';
	$jsonInPHP = json_decode(file_get_contents($filePath));
	$count = count($jsonInPHP->Machines);
	$flag = false; 
	for ($r = 0; $r < $count; $r++){

    // look for the entry we are trying to find
    if ($jsonInPHP->Machines[$r]->name == $name && $jsonInPHP->Machines[$r]->password == $password){

			$flag = true;
            break;
			
    }
  }
  if($flag)
  {
	  return true;
  }
  else
  {
	  return false;
  }
}


function VerifyPin($user_id,$mypin)
{
	$filePath = 'Users/'.$user_id.'_1.txt';
	$jsonInPHP = json_decode(file_get_contents($filePath));
	$r = 0;
	$pin = $jsonInPHP->User[$r ]->pin;
	if($pin != $mypin)
	{
		return false;
	}
	else
	{
		return true;
	}
}

function verifyUser($user_id)
{
	
	$filePath = 'Users/'.$user_id.'_1.txt';
	if(!file_exists($filePath))
	{
		return false;
	}
	$jsonInPHP = json_decode(file_get_contents($filePath));
	$count = count($jsonInPHP->User);
	$flag = false; 
	for ($r = 0; $r < $count; $r++){

    // look for the entry we are trying to find
    if ($jsonInPHP->User[$r]->user_id == $user_id){

			$flag = true;
            break;
			
    }
  }
  if($flag)
  {
	  return true;
  }
  else
  {
	  false;
  }
}

function checkBalance($user_id)
{
	$filePath = 'Users/'.$user_id.'_1.txt';
	$jsonInPHP = json_decode(file_get_contents($filePath));
	$r = 0;
	$bal = $jsonInPHP->User[$r ]->balance;
	return $bal;
}

function changePin($user_id,$newPin)
{
	$filePath = 'Users/'.$user_id.'_1.txt';
	$jsonInPHP = json_decode(file_get_contents($filePath));
	$r = 0;
	$jsonInPHP->User[$r ]->pin= $newPin;
	file_put_contents($filePath, json_encode($jsonInPHP));

	return true;
}

function cashWithDraw($user_id,$amount)
{
	$filePath = 'Users/'.$user_id.'_1.txt';
	$filePath1 = 'Users/'.$user_id.'_2.txt';
	if(!file_exists($filePath1))
	{
		return false;
	}
	$jsonInPHP = json_decode(file_get_contents($filePath));
	$miniTransaction = json_decode(file_get_contents($filePath1),true);
	if(filesize($filePath1) == 0)
	{
		$len = 1;
	}
	else
	{
		$len = count($miniTransaction) + 1;
	}
		
	$r = 0;
	if($amount<$jsonInPHP->User[$r ]->balance)
	{
		$jsonInPHP->User[$r ]->balance= $jsonInPHP->User[$r ]->balance - $amount;
		file_put_contents($filePath, json_encode($jsonInPHP));
		$item = 'statement' .$len;
		$currtime = time();
		$dateforjson = date('Y-m-d H:i:s', $currtime);
	 	
		$OldMiniCount= 2;
		$newMiniCount = 1;
		if($len-1 == 5)
		{
			unset($miniTransaction['statement1']);
			for($i=1;$i<5;$i++)
			{
				$OldMiniStateMent = 'statement'.$OldMiniCount;
				$OldMiniCount = $OldMiniCount + 1;
				$NewMiniStateMent = 'statement'.$newMiniCount;
				$newMiniCount = $newMiniCount + 1;
				$temp = $miniTransaction[$OldMiniStateMent];
				$NewminiTransaction[$NewMiniStateMent] = $temp;
			}
			$NewminiTransaction['statement5'] = array("amount"=>$jsonInPHP->User[$r ]->balance,"date"=>$dateforjson);
			file_put_contents($filePath1, json_encode($NewminiTransaction));
		}
		else
		{
			$miniTransaction[$item] = array("amount"=>$jsonInPHP->User[$r ]->balance,"date"=>$dateforjson);
			file_put_contents($filePath1, json_encode($miniTransaction));
		}
		return true;
	}
	else
	{
		return false;
	}
}

function miniStatement($user_id)
{
	$filePath1 = 'Users/'.$user_id.'_2.txt';
	if(!file_exists($filePath1))
	{
		
		return null;
	}
		$filePath1 = 'Users/'.$user_id.'_2.txt';
		$miniTransaction = json_decode(file_get_contents($filePath1),true);
		return $miniTransaction;
	
}

/* Routes */

// Home route
$app->get('/', function(){
    echo 'Home - My Slim Application';
});



$app->post('/login', function() use ($app){
	$app->response()->header("Content-Type", "application/json");
	$name = $app->request->post('name');
	$password = $app->request->post('password');
	verifyRequiredParams(array('name', 'password'));
    
    $status = login($name,$password);
	
	if($status)
	{
		echo json_encode(array(
				'status' =>   true,
				'Message'=>'Login Successfull'
				));
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
	
    
    
});





$app->post('/user', function() use ($app){
	$app->response()->header("Content-Type", "application/json");
	$name = $app->request->post('name');
	$password = $app->request->post('password');
	$user_id = $app->request->post('userId');
	verifyRequiredParams(array('name', 'password','userId'));
    
    $status = login($name,$password);
	
	if($status)
	{
		$status = verifyUser($user_id);
		if($status)
		{
			echo json_encode(array(
				'status' =>   true,
				'Message'=>'Pin is Correct'
				));
			
		}
		else
		{
			echo json_encode(array(
				'status' =>   false,
				'Message'=>'Pin is not Correct'
				));
		}
	
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
	
    
    
});

$app->post('/pin', function() use ($app){
	$app->response()->header("Content-Type", "application/json");
	$name = $app->request->post('name');
	$password = $app->request->post('password');
	$user_id = $app->request->post('userId');
	$pin = $app->request->post('pin');
	verifyRequiredParams(array('name', 'password','userId','pin'));
    
    $status = login($name,$password);
	
	if($status)
	{
		$status = verifyUser($user_id);
		if($status)
		{
			$status = VerifyPin($user_id,$pin);
			
			if($status)
			{
				echo json_encode(array(
				'status' =>   true,
				'Message'=>'Pin is correct'
				));
			}
			else
			{
				echo json_encode(array(
				'status' =>   false,
				'Message'=>'Pin is not correct'
				));
			}
			
			
		}
		else
		{
			echo json_encode(array(
				'status' =>   false,
				'Message'=>'User is not Correct'
				));
		}
	
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
	
    
    
});


// Get a single car
$app->get('/balance/:name/:password/:user_id/:pin', function($name,$password,$user_id,$pin) use ($app) {
    $app->response()->header("Content-Type", "application/json");
     $status = login($name,$password);
	
	if($status)
	{
		$status = verifyUser($user_id);
		if($status)
		{
			$status = VerifyPin($user_id,$pin);
			
			if($status)
			{
				$balance = checkBalance($user_id);
				echo json_encode(array(
				'status' =>   true,
				'Balance'=>$balance
				));
			}
			else
			{
				echo json_encode(array(
				'status' =>   false,
				'Message'=>'Pin is not correct'
				));
			}
			
			
		}
		else
		{
			echo json_encode(array(
				'status' =>   false,
				'Message'=>'User is not Correct'
				));
		}
	
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
});

$app->post('/changepin', function() use ($app){
	$app->response()->header("Content-Type", "application/json");
	$name = $app->request->post('name');
	$password = $app->request->post('password');
	$user_id = $app->request->post('userId');
	$pin = $app->request->post('pin');
	$newpin = $app->request->post('changepin');
	
    verifyRequiredParams(array('name', 'password','userId','pin','changepin'));
	
    $status = login($name,$password);
	
	if($status)
	{
		$status = verifyUser($user_id);
		if($status)
		{
			$status = VerifyPin($user_id,$pin);
			
			if($status)
			{
				$status = changePin($user_id,$newpin);
				echo json_encode(array(
				'status' =>   true,
				'Message'=>'Pin changed successfully'
				));
				
			}
			else
			{
				echo json_encode(array(
				'status' =>   false,
				'Message'=>'Pin is not correct'
				));
			}
			
			
		}
		else
		{
			echo json_encode(array(
				'status' =>   false,
				'Message'=>'User is not Correct'
				));
		}
	
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
	
    
    
});

$app->post('/cashwithdraw', function() use ($app){
	$app->response()->header("Content-Type", "application/json");
	$name = $app->request->post('name');
	$password = $app->request->post('password');
	$user_id = $app->request->post('userId');
	$pin = $app->request->post('pin');
	$amount = $app->request->post('amount');
	
    verifyRequiredParams(array('name', 'password','userId','pin','amount'));
    $status = login($name,$password);
	
	if($status)
	{
		$status = verifyUser($user_id);
		if($status)
		{
			$status = VerifyPin($user_id,$pin);
			
			if($status)
			{
				$status = cashWithDraw($user_id,$amount);
				if($status)
				{
					echo json_encode(array(
					'status' =>   true,
					'Message'=>'Case withdraw successfull'
					));
				}
				else
				{
					echo json_encode(array(
					'status' =>   false,
					'Message'=>'Case withdraw not successfull'
					));
				}
				
			}
			else
			{
				echo json_encode(array(
				'status' =>   false,
				'Message'=>'Pin is not correct'
				));
			}
			
			
		}
		else
		{
			echo json_encode(array(
				'status' =>   false,
				'Message'=>'User is not Correct'
				));
		}
	
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
	
    
    
});

// Get a single car
$app->get('/ministatement/:name/:password/:user_id/:pin', function($name,$password,$user_id,$pin) use ($app) {
    $app->response()->header("Content-Type", "application/json");
     $status = login($name,$password);
	
	if($status)
	{
		$status = verifyUser($user_id);
		if($status)
		{
			$status = VerifyPin($user_id,$pin);
			
			if($status)
			{
				$miniStatement = miniStatement('2007');
				if($miniStatement !=null)
				{
					echo json_encode(array(
					'status' =>   true,
					'ministatement'=>$miniStatement
					));
				}
				else
				{
					echo json_encode(array(
					'status' =>   false,
					'Message'=>'No mini statement or file not exist'
					));
				}
			}
			else
			{
				echo json_encode(array(
				'status' =>   false,
				'Message'=>'Pin is not correct'
				));
			}
			
			
		}
		else
		{
			echo json_encode(array(
				'status' =>   false,
				'Message'=>'User is not Correct'
				));
		}
	
	}
	else
	{
		echo json_encode(array(
				'status' =>   false,
				'Message'=>'Login not Successfull'
				));
	}
});

/* Run the application */
$app->run();