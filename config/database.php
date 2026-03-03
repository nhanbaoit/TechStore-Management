<?php
class database{
    public $host = "localhost";
    public $username = "root";
    public $password = "";
    public $database = "techstore-managament";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    
    }

    public function select($query) {
        $result = $this->conn->query($query);
        $data = [];
        if($result && $result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function execute($query) {
        return $this->conn->query($query);
    }

     public function __destruct() {
        $this->conn->close();
    }
}
?>