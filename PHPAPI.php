<?php

/*   #API o' Magic # 
   [Partially Commented Version] */
   
   
/*

[Request List]:

	- 'Login' - Authenticates requesting user and returns all information stored in the database associated with
				   that user.
				   
    - 'GetContacts' - Returns requesting user's Contact List.
	
	- 'GetId' - Returns requesting user's database Id.
	
	- 'AddContact' - Adds a contact to the requesting user's contact list.
	
	- 'NewUser' - Adds a new user to the messenger app.

[Error List]:

	- Forbidden (403) - Thrown when a user's credentials fail to Authenticate.

	- Precondition Failed (412) - Thrown when an API call lacks essential post information.

*/   

class Conversation {

	public $Contact;
	public $Messages;
	public $ConversationId;
	
	function __construct($par1, $par2, $par3) {
		$this->Messages = $par1;
		$this->ConversationId = $par2;
		$this->Contact = $par3;
	}
}

class PossibleObject {
	
	public $ConversationName;
	public $ContactName;
	
	function __construct($par1, $par2) {
		
		$this->ConversationName = $par1;
		$this->ContactName = $par2;
		
	}
	
}

class HttpResponse {

	//Error
	const STATUS_PRECONDITION_FAILED = 412;
	const STATUS_FORBIDDEN = 403;
	const STATUS_INTERNAL_SERVER_ERROR = 500;
	const STATUS_UNPROCESSABLE_ENTITY = 422;
	const STATUS_CONFLICT = 409;
	const STATUS_SERVICE_ERROR = 503;
	const STATUS_BAD_REQUEST = 400;
	
	//Success
	const STATUS_OK = 200;
	const STATUS_CREATED = 201;
	const STATUS_ACCEPTED = 202;
	const STATUS_UNAUTHORIZED = 401;
	
	public $Status;
	public $Response;
	
	function __construct($par1, $par2) {
		$this->Status = $par1;
		$this->Response = $par2;
	}
	
}

/*
$TempUser['Id'] = "3";
$TempUser['Username'] = "Warpeddemon" ;
$TempUser['Password'] =  "pass";
$TempUser['Status'] = "User";
$TempUser['Banned'] = "false";
$TempUser['Contacts'] = "[{}]";  

  
$Person2['Username'] = "RightBehindu";
$Person2['Id'] = "1"; 
*/
//$Person3['Username'] = "bob";
//$Person3['Id'] = "2";
  /* 
 // TEST CHUNK #1 
 
$_POST['Username'] = "Warpeddemon";
$_POST['Password'] = "pass";
$_POST['Message'] = "HELLO THAR";
$_POST['Request'] = "AcceptFriendRequest";
$_POST['Contact'] = $Person2;  

// TEST CHUNK #2 
$_POST['Username'] = "RightBehindu";
$_POST['Password'] = "yellow12";
$_POST['Request'] = "SendFriendRequest";
$_POST['Contact'] = '{"Id":"1","Username":"WarpedDemon"}';
$_POST['Message'] = "Hhi";  
*/
 
if($_POST) {
	//If attempting to access sensitive data which requires user credentials.
	if(!isset($_POST['Username']) || !isset($_POST['Password']) || !isset($_POST['Request'])) {
		//If the base expected POST variables are not met, throw error 412.
		echo ThrowPrecondition();
		return;
	}
	
	$Request = $_POST['Request'];
	$User = AuthenticateUser($_POST['Username'], $_POST['Password']);
	
	//If the user is returned as false, throw a forbidden 403 error.
	if($User === false) {
		ThrowUnauthorized();
		return;
	}

	
	// API EVENT REQUESTS  //
	
	if($Request === "Login") {
		echo json_encode($User);
		return;
	}
	
	if($Request === "NewUser") {
		if(!isset($_POST['NewUser'])) {
			ThrowPrecondition();
			return;
		} else {
			if($User['Status'] === "Admin") {
				AddNewUser($_POST['Username'], $_POST['Password'], $_POST['NewUser']);
			} else {
				ThrowForbidden();
				return;
			}
			return;
		}
	}
	
	if($Request === "GetContacts") {
		echo json_encode($User['Contacts']);
		return;
	} 
	
	if($Request === "GetId") {
		echo json_encode($User['Id']);
		return;
	}
	
	if($Request === "AddContact") {
		if(!isset($_POST['NewContact'])) {
			//If the new contact to add has not been defined.
			ThrowPrecondition();
			return;
		} else {
			$result = AddContact($_POST['Username'], $_POST['Password'], $_POST['NewContact']);
			if($result) {
				echo true;
				return;
			} else {
				ThrowService();
				return;
			}
		}
	}
	
	if($Request === "NewConversation") {
		if(!isset($_POST['Contact'])) {
			ThrowPrecondition();
			return;
		}
		CreateNewConversation($User, $_POST['Contact']);
		return;
	}
	
	if($Request === "AddContactsTemp") {
		run($_POST['Username'], $_POST['Password']);
	}
	
	
	if($Request === "NewMessage") {
		if(!isset($_POST['Message']) || !isset($_POST['Contact'])) {
			ThrowPrecondition();
			return;
		}
		$_POST['Contact'] = json_decode($_POST['Contact']);
		SendMessage($User, $_POST['Message'], $_POST['Contact']);
		return;
	}
	
	if($Request === "FriendRequest") {
		if(!isset($_POST['Contact'])) {
			ThrowPrecondition();
			return;
		}
		
		$result = SendFriendRequest($User, $_POST['Contact']);
		if($result) {
			echo json_encode(new HttpResponse(HttpResponse.STATUS_ACCEPTED, "true"));
		} else {
			return;	
		}
	}
	
	if($Request === "AcceptFriendRequest") {
		if(!isset($_POST['Contact'])) {
			ThrowPrecondition();
			return;
		}
		AcceptFriendRequest($User, $_POST['Contact']);
		return;
	}
	
	if($Request === "GetConversations") {
		GetConversationList($User);
		return;
	}
	
	if($Request === "GetFriendRequests") {
		GetFriendRequests($User);
		return;
	}
	
	ThrowBadRequest();
	return;
} else {
	
	echo "This is API, wut r u doin...";
	
}

	// API FUNCTIONS //

//Accepts a friend request for a user from a parsed contact.
function AcceptFriendRequest($User, $Contact) {
	
	$db = CreateConnectionObject();
	if($db === false) { ThrowService(); return; }
	
	$CurrentFriendRequests = json_decode($User['FriendRequests'], true);
	
	if($CurrentFriendRequests === null) {
		ThrowInternal();
		return;
	}
	for($i = 0; $i < count($CurrentFriendRequests); $i++) {
		$request = $CurrentFriendRequests[$i];
		if($request['Username'] === $Contact['Username']) {
			$NewFriendRequests = $CurrentFriendRequests;
			array_splice($NewFriendRequests, $i, 1);
			$result = UpdateUserRequests($User, $NewFriendRequests, $db);
			AddContact($User['Username'], $User['Password'], $Contact);
			if(!$result) {
				//If there was no update.
				ThrowInternal();
				ThrowService();
				return;
			} else {
				echo "\nAccepted!";
				return;
			}
		}
	}
	ThrowInternal();
	return false;
}

//Updates a users friend requests with a parsed friend request. Used in SendFriendRequest.
function UpdateUserRequests($User, $FriendRequests, $db) {

	
	$stmt = "UPDATE logins SET FriendRequests=:friends WHERE Username=:username AND Id=:id";
	$query = $db->prepare($stmt);
	$query->execute(array('friends'=>json_encode($FriendRequests), 'username'=>$User['Username'],'id'=>$User['Id']));
	
	$result = $query->rowCount();
	
	if($result > 0) {
		return true;
	} else {
		return false;
	}
}
	
//Sends a friend request from a user to a parsed contact.
function SendFriendRequest($User, $Contact) {
	
	$db = CreateConnectionObject();
	if($db === false) { ThrowService(); return; }

	if(isFriend($User, $Contact, $db)) {
		echo json_encode(new HttpResponse(HttpResponse.STATUS_BAD_REQUEST, "false"));
		return false;
	}
	
	if(FriendRequestAlreadySent($User, $Contact, $db)) {
		echo json_encode(new HttpResponse(HttpResponse.STATUS_BAD_REQUEST, "false"));
		return false;
	}
	
	$newFriendRequest = array();
	$newFriendRequest['Id'] = $User['Id'];
	$newFriendRequest['Username'] = $User['Username'];
	$newFriendList = json_decode($User['FriendRequests'], true);
	
	if($newFriendList === null) {
		$newFriendList = array();
	} 
	array_push($newFriendList, $newFriendRequest);
	$stmt = "UPDATE logins SET FriendRequests=:friends WHERE Username=:contact;";
	$query = $db->prepare($stmt);
	$query->execute(array('friends'=>json_encode($newFriendList), 'contact'=>$Contact['Username']));
	$result = $query->rowCount();
	if($result > 0) {
		echo "Successfully updated " . $result . " rows!";
		return true;
	} else {
		echo json_encode(new HttpResponse(HttpResponse.STATUS_BAD_REQUEST, "false"));
		return false;
	}
}	

//Returns whether or not a user already has sent a friend request to a parsed contact.
function FriendRequestAlreadySent($User, $Contact, $db) {
	$stmt = "SELECT * FROM logins WHERE Username=:contact_username";
	
	$query = $db->prepare($stmt);
	$query->execute(array('contact_username'=>$Contact['Username']));
	
	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	

	$TempFriendRequests = json_decode($result[0]['FriendRequests'], true);
	if($TempFriendRequests !== null) {
		foreach($TempFriendRequests as $request) {
			if($request['Username'] === $Contact['Username']) {
				return true;
			}
		}
	}
	return false;
}

//Sends a message from a user to a parsed contact.
function SendMessage($User, $Message, $Contact) {
	
	$db = CreateConnectionObject();
	if($db === false) { ThrowService(); return false; }

	if(!IsFriend($User, $Contact, $db)) {
		echo "Missing Requirements - User is not a friend!";
		return;
	}
	$tableName = CreateConversationString($User['Username'], $Contact->Username);

	if(!ConversationExistsFromString($tableName, $db)) {
		CreateNewConversation($User, $Contact);
	}
	$stmt = "INSERT INTO " . $tableName . " (sender, message) VALUES (:sender, :message);";
	$query = $db->prepare($stmt);
	$query->execute(array('sender'=>$User['Username'], 'message'=>$Message));
	
	$result = $query->rowCount();
	echo $result;
	if($result > 0) {
		return true;
	} else {
		ThrowService();
		return false;
	}
}

function RemoveContact($User, $Contact) {
	$db = CreateConnectionObject();
	if($db === false) { ThrowService(); return false; }
	
	if(!IsFriend($User, $Contact, $db)) {
		ThrowInternal();
	}
	
	
}

//Returns whether or not a user is friends with a parsed contact.
function IsFriend($User, $Contact, $db) {
	$ExistingContactList = GetContacts($User['Username'], $User['Password']);
	$ContactList = json_decode($ExistingContactList, true);
	for($i = 0; $i < count($ContactList); $i++) {

		if($ContactList[$i]['Username'] === $Contact->Username) {
			return true;
		}
	}
	return false;

}

//Creates a new conversation between are user and a parsed contact.
function CreateNewConversation($user, $contact) {
	
	$db = CreateConnectionObject();
	//$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	if(ConversationExists($user, $contact, $db)) {
		ThrowConflict();
		return false;
	}
	$tableName = CreateConversationString($user['Username'], $contact->Username);
	$stmt = "CREATE TABLE " . $tableName . " (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
    sender VARCHAR(30) NOT NULL,
    message VARCHAR(30) NOT NULL,
    sent_time TIMESTAMP
    );";
					

	$query = $db->exec($stmt);
	echo "Conversation Made!";
}

//Returns whether or not a conversation exists between a user and a parsed contact.
function ConversationExists($user, $contact, $db) {
	$tableName = CreateConversationString($user['Username'], $contact->Username);
	$stmt = "SHOW TABLES LIKE '" . $tableName . "';";
	$query = $db->prepare($stmt);
	$query->execute();
	
	$result = $query->rowCount();
	if($result > 0) {
	
		return true;
	} else {
		return false;
	}
} 

//Returns whether or not a conversation exists based of a preconstructed conversation name string.
function ConversationExistsFromString($string, $db) {
	$string = strtolower($string);
	$stmt = "SHOW TABLES LIKE '" . $string . "';";
	$query = $db->prepare($stmt);
	$query->execute();
	$result = $query->rowCount();
	if($result > 0) {
		return true;
	} else {
		return false;
	}
}

//Adds a new user to the logins database. Can only be called by Admin status accounts.
function AddNewUser($username, $password, $User) {
	$db = CreateConnectionObject();
	if($db === false) { 
		ThrowService();
		return false;
	}
	
	$stmt = "INSERT INTO logins (Username, Password, Status, Banned, Contacts)
			      VALUES (:username, :password, :status, :banned, :contact);";
	$query = $db->prepare($stmt);
	$query->execute(array('username'=>$User['Username'], 'password'=>$User['Password'], 'status'=>$User['Status'], 'banned'=>'false', 'contact'=>'[]'));
	
	$result = $query->rowCount();
	echo "Added " . $result . " users!";
	
}

//Adds a contact to the requesting user.
function AddContact($username, $password, $Contact) {
	$CurrentContacts = GetContacts($username, $password);
	$ContactList = json_decode($CurrentContacts, true);
	
	if($ContactList === null) {
		$ContactList = array();
	}
	
	array_push($ContactList, $Contact);
	
	$db = CreateConnectionObject();
	if($db === false) { 
		ThrowService();
		return false;
	}

	$stmt = "UPDATE logins SET Contacts=:contacts WHERE Username=:username AND Password=:password;";
	$query = $db->prepare($stmt);
	$query->execute(array("contacts"=>json_encode($ContactList), "username"=>$username, "password"=>$password));
	
	$result = $query->rowCount();
	if($result > 0) {
		echo "Successfully updated " . $result . " rows!";
		return true;
	} else {
		return false;
	}
	
}

//Returns a list of contacts for the requesting user.
function GetContacts($username, $password) {
	$db = CreateConnectionObject();
	if($db === false) { 
		ThrowService();
		return false;
	}
	
	$stmt = "SELECT * FROM logins WHERE Username=:username AND Password=:password;";
	$query = $db->prepare($stmt);
	$query->execute(array("username"=>$username, "password"=>$password));
	
	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	if(isset($result[0])) {
		return $result[0]['Contacts'];
	} else {
		ThrowForbidden();
	}
}

//Returns a list of Friend Requests for a given user.
function GetFriendRequests($User) {
	$db = CreateConnectionObject();
	if($db === false) { 
		ThrowService();
		return false;
	}
	
	$stmt = "SELECT * FROM logins WHERE Username=:username AND Password=:password;";
	$query = $db->prepare($stmt);
	$query->execute(array("username"=>$User['Username'], "password"=>$User['Password']));
	
	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	if(isset($result[0])) {
		echo json_encode($result[0]['FriendRequests']);
	} else {
		ThrowForbidden();
	}
}

//Returns a list of conversations for a given user.
function GetConversationList($User) {
	$Contacts = json_decode(GetContacts($User['Username'], $User['Password']), true);
	//echo json_encode($Contacts);
	$PossibleConversations = array();
	
	foreach($Contacts as $contact) {
		$convoName = CreateConversationString($User['Username'], $contact['Username']);
		$PossibleConversationObject = new PossibleObject($convoName, $contact['Username']);
		array_push($PossibleConversations, $PossibleConversationObject);
	}
	
	
	
	$db = CreateConnectionObject();
	if($db === false) { ThrowService(); return; }
	
	$ConversationList = array();
	
	foreach($PossibleConversations as $conversationObject) {
		if(ConversationExistsFromString($conversationObject->ConversationName, $db)) {
			$conversationString = strtolower($conversationObject->ConversationName);
			
			$stmt = "SELECT * FROM ". $conversationString .";";
			$query = $db->prepare($stmt);
			$query->execute();
			
			$result = $query->fetchAll(PDO::FETCH_ASSOC);
			
			$Conversation = new Conversation($result, $conversationString, $conversationObject->ContactName);
			array_push($ConversationList, $Conversation);
		}
	}
	
	//ThrowDefaultSuccess($ConversationList);
	echo json_encode($ConversationList);
}

//Validates user credentials and returns the user.
function AuthenticateUser($username, $password) {

	$db = CreateConnectionObject();
	if($db === false) { 
		ThrowService();
		return false;
	}
	
	$stmt = "SELECT * FROM logins WHERE Username=:username AND Password=:password;";
	$query = $db->prepare($stmt);
	$query->execute(array("username"=>$username, "password"=>$password));
	
	$results = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if(isset($results[0])) {
		return $results[0];
	} else {
		return false;
	}
	
}

//Creates the database object used in many other api functions.
function CreateConnectionObject() {
	try {
		$dsn = "mysql:host=223.27.22.124;dbname=04student_AppDatabaseMessaging;charset=utf8;";
		$db = new PDO($dsn, 'PrinceWarped', 'yellow12');
	}
	catch(PDOException $e) {
		//If the database object cannot be instantiated.
		echo $e->getMessage();
		return false;
	}
	
	if(isset($db)) {
		if($db == null) {
			return false;
		}
		return $db;
	} else {
		return false;
	}
}


// Other Functions //

//Returns a the correct format for a conversation table name between two usernames.
function CreateConversationString($nameOne, $nameTwo) {
	$index = 0;
	while($nameOne[$index] === $nameTwo[$index]) {
		$index++;

		if($index > strlen($nameOne)-1) {			
			return "conversation_" . $nameOne . "_" . $nameTwo;
		}
		if( $index > strlen($nameTwo)-1) {
			return "conversation_" . $nameTwo . "_" . $nameOne;
		}
	}
	
	if(strtolower($nameOne[$index]) < strtolower($nameTwo[$index])) {

		return "conversation_" . strtolower($nameOne) . "_" . strtolower($nameTwo);
	} else {
		return "conversation_" . strtolower($nameTwo) . "_" . strtolower($nameOne);
	}
}

// Error Throwing Functions //

function ThrowPrecondition() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_PRECONDITION_FAILED, "Precondition Failed (412).");
	echo json_encode($HttpResponse);
}

function ThrowForbidden() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_FORBIDDEN, "Forbidden. Insufficient Permissions (403).");
	echo json_encode($HttpResponse);
}

function ThrowUnauthorized() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_UNAUTHORIZED, "Forbidden. Authentication Failed (401).");
	echo json_encode($HttpResponse);
}

function ThrowService() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_SERVICE_ERROR,  "Service Unavailable (503) - Server Unavailable.");
	echo json_encode($HttpResponse);
	
}

function ThrowConflict() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_CONFLICT,  "Conflict (409) - Resource already exists.");
	echo json_encode($HttpResponse);
}

function ThrowInternal() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_CONFLICT,  "Internal Server Error (500).");
	echo json_encode($HttpResponse);
	return;
}

function ThrowDefaultSuccess($Response) {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_OK, $Response);
	echo json_encode($HttpResponse);
}

function ThrowSuccess($Status, $Response) {
	$HttpResponse = new HttpResponse($Status, $Response);
	echo json_encode($HttpResponse);
}

function ThrowBadRequest() {
	$HttpResponse = new HttpResponse(HttpResponse::STATUS_BAD_REQUEST, "Bad Request (400)");
	echo json_encode($HttpResponse);
}
// Test Functions //

function run($username, $password) {
	//This function is purely a test function to add a couple of contacts to a new user whom doesn't have any yet.
 
	$Person['Username'] = "bob";
	$Person['Id'] = "10"; 
	 $Person2['Username'] = "bill";
	$Person2['Id'] = "11"; 
	$Person3['Username'] = "baxter";
	$Person3['Id'] = "12"; 
	$TempContacts = array();
	array_push($TempContacts, $Person);
	array_push($TempContacts, $Person2);
	array_push($TempContacts, $Person3);


	$db = CreateConnectionObject();
	
	$stmt = "UPDATE logins SET Contacts=:contact WHERE Username=:username AND Password=:password;"; 

	$query = $db->prepare($stmt);
	$query->execute(array("contact"=> json_encode($TempContacts), "username"=>$username, "password"=>$password));

	echo "ran";
}

?>