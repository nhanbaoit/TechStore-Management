<?php
class database
{
    private $host;
    private $user;
    private $pass;
    private $db;
    public $conn;

    public function __construct()
    {

        if ($_SERVER['SERVER_NAME'] == 'localhost') {
            // LOCAL
            $this->host = 'localhost';
            $this->user = 'root';
            $this->pass = '';
            $this->db = 'techstore';
        } else {
            // HOSTING
            $this->host = 'sql102.infinityfree.com';
            $this->user = 'if0_41380568';
            $this->pass = '123456789Rudeus';
            $this->db = 'if0_41380568_tech_store';
        }

        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);

        if ($this->conn->connect_error) {
            die("DB lỗi: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }


    public function select($sql)
    {
        $result = $this->conn->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function count($sql)
    {
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_row();
            return $row[0];
        }
        return 0;
    }

    public function execute($sql)
    {
        return $this->conn->query($sql);
    }

    public function close()
    {
        $this->conn->close();
    }
}
?>