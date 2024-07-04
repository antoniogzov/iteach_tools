<?php
include_once 'Connection.php';
class Queries extends data_conn
{
    private $conn;
    public function __construct()
    {
        $this->conn = $this->dbConn();
    }

    public function getData($stmt)
    {
        $results = array();

        try {

            $query = $this->conn->query($stmt);

            while ($row = $query->fetch(PDO::FETCH_OBJ)) {
                $results[] = $row;
            }
        } catch (Exception $e) {
            echo 'Exception -> ' . $query;
            var_dump($e->getMessage());
        }

        return $results;
    }
    public function InsertData($stmt)
    {
        $results = array();

        try {

            if($this->conn->query($stmt)){
                $last_id = $this->conn->lastInsertId();
                $results['status'] = 'success';
                $results['last_id'] = $last_id;
            }

            
        } catch (Exception $e) {
            echo 'Exception -> ' . $stmt;
            var_dump($e->getMessage());
        }

        return $results;
    }
    
}
