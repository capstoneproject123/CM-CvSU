<?php

require_once 'config.php';

class User_forms extends Database{
    protected $tableName = "complaint_inquiry";

    //function to add complaint/inquiry user forms
    public function add($data)
{
   
    if(!empty($data)){
        $fileds=$placeholder=[];
        foreach($data as $field => $value){
            $fileds[]=$field;
            $placeholder[]=":{$field}";
        }
    }
    
    $sql="INSERT INTO {$this->tableName} (". implode(',', $fileds).") VALUES (". implode(',', $placeholder).")";

    

    $stmt = $this->conn->prepare($sql);
    try{
        $this->conn->beginTransaction();
        $stmt->execute($data);
        $lastInsertedId=$this->conn->lastInsertId();
        $this->conn->commit();
        return $lastInsertedId;
    }catch(PDOException $e){
        echo "Error:".$e->getMessage();
        $stmt = $this->conn->rollback();
    }
   
}


    //function to get rows
    public function getRows($start=0, $limit=10){
        $sql = "SELECT * FROM {$this->tableName} ORDER BY DESC LIMIT {$start},{$limit}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        if($stmt->rowCount()>0){
            $results=$stmt->fetchAll(PDO::FETCH_ASSOC);
            
        }else{
            $results=[];
        }
        return $results;
    }
    
    //function to get single row

    public function getRow($field,$value){
        $sql = "SELECT * FROM {$this->tableName} WHERE {$field}=:{$field}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        if($stmt->rowCount()>0){
            $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
            
        }else{
            $result=[];
        }
        return $result;
    }

    //function to count number of rows
    public function getCount(){
        $sql = "SELECT count(*) as pcount FROM {$this->tableName}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result['pcount'];
    }

    // function to upload evidences
    public function uploadEvidence($file){
        if(!empty($file)){
            $fileTempPath=$file['tmp_name'];
            $fileName=$file['name'];
            $fileType=$file['type'];
            $fileNameCmps=explode('.',$fileName);
            $fileExtension=strtolower(end($fileNameCmps));
            $newFileName=md5(time().$fileName) . '.'. $fileExtension;
            $allowedExtn=["png","jpg","jpeg","pdf","doc","docx","mp4","mp3"];

            if(in_array($fileExtension, $allowedExtn)){
                $uploadFileDir=getcwd().'/uploads/';
                $destFilePath=$uploadFileDir . $newFileName;
                if(move_uploaded_file($fileTempPath, $destFilePath)){
                    return $newFileName;
                }
            }
        }
    }

    // function to update
    

    // function to delete


    // function to search
}

?>