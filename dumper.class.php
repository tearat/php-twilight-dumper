<?php

class Dumper 
{
    private $host = "";
    private $username = "";
    private $password = "";
    public $database = "";
    private $link;
    
    public $table_names = [];
    
    public $sql = '';
    public $rows = [];
    public $rows_summary = 0;
    
    public $error = false;
    
    public function __construct($username, $password, $database, $host='localhost')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->link = mysqli_connect($this->host, $this->username, $this->password, $this->database);
        
        $this->table_names = $this->get_tables();
    }
    
    public function generate()
    {
        $this->sql = '';
        $this->rows = [];
        $this->rows_summary = 0;
        
        if (!$this->link){
            $this->error = true;
            return false;
        }
        
        // prefix

        $this->sql .= 
            "SET NAMES utf8;
            SET time_zone = '+05:00';
            SET foreign_key_checks = 0;
            SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
            USE `$this->database`;
            SET NAMES utf8mb4;\n";

        // create tables and inserts into

        foreach($this->table_names as $table_name){
            $this->create_table($table_name);
        }
    }
    
    private function get_tables(){
        $sql_work = "SHOW TABLES;";
        $query = mysqli_query($this->link, $sql_work);
        
        while ($row = mysqli_fetch_assoc($query))
        {
            $tables_in = "Tables_in_$this->database";
            $data[] = $row[$tables_in];
        }
        
        return $data;
    }
    
    private function create_table($table_name)
    {
        $this->sql .= "DROP TABLE IF EXISTS `$table_name`;\n";
        
        $sql_work = "SHOW COLUMNS FROM `$table_name`";
        $query = mysqli_query($this->link, $sql_work);
        
        $columns_raw = [];
        $columns = [];
        $columns_titles = [];
        
        while ($row = mysqli_fetch_assoc($query))
        {
            $columns_raw[] = $row;
            $columns_titles[] = $row[Field];
            $new_column = "$row[Field] $row[Type] ";
            if ($row[Type] == 'text'){
                $new_column .= ' COLLATE utf8mb4_unicode_ci ';
            }
            if ( substr($row[Type], 0, 7) == 'varchar' ){
                $new_column .= ' COLLATE utf8mb4_unicode_ci ';
            }
            if ($row[Null] == 'NO'){
                $new_column .= ' NOT NULL ';
            }
            if ($row[Extra] == 'auto_increment'){
                $new_column .= ' AUTO_INCREMENT ';
            }
            $columns[] = $new_column;
        }
        
        $this->sql .= 
            " CREATE TABLE `$table_name` (" .
            implode(", ", $columns) .
            ", PRIMARY KEY (`id`) )" . 
            " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
        
        $this->filling_table($table_name, $columns_raw);
    }
    
    private function filling_table($table_name, $columns_raw)
    {
        $sql_select = "SELECT * FROM `$table_name`";

        $query = mysqli_query($this->link, $sql_select);
        
        if (mysqli_num_rows($query) == 0){
            return false;
        }
        
        $this->rows[$table_name] = 0;
        while ($row = mysqli_fetch_assoc($query))
        {
            $this->rows[$table_name]++;
            $this->rows_summary++;
            $data[] = $row;
        }
        
        $columns_titles_string = $columns_raw;
        
        foreach($columns_titles_string as &$column){
            $field = $column['Field'];
            $column = "`$field`";
        }
        
        $columns_titles_string = implode(", ", $columns_titles_string);
        
        $this->sql .= "INSERT INTO `$table_name` ($columns_titles_string) VALUES ";
        
        // Items
        
        $items = [];
        
        foreach($data as $key => $value){
            $values = [];
            foreach($columns_raw as $column){
                $new_value = $value[$column['Field']];
                $new_value = mysqli_real_escape_string($this->link, $new_value);
                if ( substr($column['Type'], 0, 3) != "int" ){
                    $new_value = "'$new_value'";
                }
                $values[] = $new_value;
            }
            $new_item = "(" . implode(", ", $values) . ")";
                                      
            $items[] = $new_item;
        }
        
        $this->sql .= implode(", ", $items);
        
        // Dublicates
        
        $this->sql .= " ON DUPLICATE KEY UPDATE ";
        
        $dublicated = [];
        foreach($columns_raw as $column){
            $field = $column['Field'];
            $dublicated[] = "`$field` = VALUES(`$field`)";
        }
        
        $this->sql .= implode(", ", $dublicated) . ";\n ";
    }
    
    public function save()
    {
        if(!$this->sql){
            return false;
        }
        
        // Creating SQL file
    
        date_default_timezone_set('Asia/Yekaterinburg');
        $date = date('Y-m-d H-i');
        $file = "$this->database ".$date.".sql";
        file_put_contents($file, $this->sql);

        // Saving SQL file

        if (file_exists($file)) 
        {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }
            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // читаем файл и отправляем его пользователю
            readfile($file);
            unlink($file);
            exit;
            return true;
        } else {
            return false;
        }
    }
    
    public function upload($file)
    {
        error_reporting(0); 

        $f = fopen($file["tmp_name"], 'rt') or die('file not opened');
        
        while (!feof($f)){
            $line = fgets($f);
            mysqli_query($this->link, $line);
        }
        
        fclose($f);
        
        return true;
    }
}