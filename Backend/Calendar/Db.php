<?php
namespace Calendar;
use mysqli;
class Db
{
    const BEGIN = null;
    const COMMIT = true;
    const ROLLBACK = false;
    public $db;
    public $query;
    public function __construct($host, $user, $password, $database, $charset = 'utf8mb4')
    {
        $this->db = new mysqli($host, $user, $password, $database);
        $this->db->set_charset($charset);
    }
    public function __set($prop, $value)
    {
        $this->db->{$prop} = $value;
    }
    public function __get($prop)
    {
        return $this->db->{$prop};
    }
    public function __call($method, array $args)
    {
        return call_user_func_array([$this->db, $method], $args);
    }
    public function query()
    {
        $args = func_get_args();
        $num = func_num_args();
        $this->query = array_shift($args);
        $stmt = $this->db->prepare($this->query);
        $countPrepareParams = mb_substr_count($this->query, '?');
        if($countPrepareParams + 1 != $num){
            trigger_error("Expected {$countPrepareParams} of " . ($num - 1) . " parameters in the request '{$this->query}'", E_USER_ERROR);
            return false;
        }
        if($stmt){
            if($num > 1){
                $types = '';
                $data = [$types];
                $arr = [];
                foreach($args as $k => $arg){
                    $arr[] = is_array($arg) ? json_encode($arg) : $arg;
                }
                foreach($arr as $k => $arg){
                    switch(gettype($arg)){
                        case 'integer':
                            $types .= 'i';
                            break;
                        case 'double':
                            $types .= 'd';
                            break;
                        case 'string':
                            $types .= 's';
                            break;
                        default:
                            $types .= 'b';
                            break;
                    }
                    $data[] = &$arr[$k];
                }
                $data[0] = $types;
                call_user_func_array([$stmt, 'bind_param'], $data);
            }
            if($stmt->execute()){
                if($stmt->insert_id != 0){
                    return $stmt->insert_id;
                }
                if($stmt->affected_rows != -1){
                    return $stmt->affected_rows != 0;
                }
                return $stmt->get_result();
            }
        }
        return false;
    }
    public function transaction($status = null)
    {
        if(!$this->db){
            return false;
        }
        if($status === self::BEGIN){
            $this->db->autocommit(false);
            return $this->db->begin_transaction();
        } else if($status === self::COMMIT){
            $result = $this->db->commit();
            $this->db->autocommit(true);
            return $result;
        } else if($status === self::ROLLBACK){
            $result = $this->db->rollback();
            $this->db->autocommit(true);
            return $result;
        }
        return false;
    }
}
