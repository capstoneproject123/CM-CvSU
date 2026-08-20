<?php

class Database{

    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "complaint_inquiry_db";
    protected $conn;

    public function __construct(){
        try{
            $dsn="mysql:host={$this->host}; dbname={$this->database}; charset=utf8";
            $options=array(PDO::ATTR_PERSISTENT);
            $this->conn=new PDO($dsn,$this->user, $this->password,$options);
        }catch(PDOException $e){
            echo "Connection Error".$e->getMessage();
        }
    }

}
?>

<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "complaint_inquiry_db";

$conn = new mysqli($host, $user, $password, $database);


if ($conn->connect_error){
    die("Connection failed: ". $conn->connect_error);
}
?>
