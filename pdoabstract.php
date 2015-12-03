<?php
/**
 * Project:     PHPPDO
 * File:        pdoabstract.php
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * For questions, help, comments, discussion, etc.,
 * visit <http://devuni.com>
 *
 * @link http://devuni.com/
 * @Copyright 2007, 2008, 2009 Nikolay Ananiev.
 * @author Nikolay Ananiev <admin at devuni dot com>
 */

abstract class abstractPDO
{
    const PARAM_BOOL                    = 5;
    const PARAM_NULL                    = 0;
    const PARAM_INT                     = 1;
    const PARAM_STR                     = 2;
    const PARAM_LOB                     = 3;
    const PARAM_STMT                    = 4;
    const PARAM_INPUT_OUTPUT            = -2147483648;
    const FETCH_LAZY                    = 1;
    const FETCH_ASSOC                   = 2;
    const FETCH_NAMED                   = 11;
    const FETCH_NUM                     = 3;
    const FETCH_BOTH                    = 4;
    const FETCH_OBJ                     = 5;
    const FETCH_BOUND                   = 6;
    const FETCH_COLUMN                  = 7;
    const FETCH_CLASS                   = 8;
    const FETCH_INTO                    = 9;
    const FETCH_FUNC                    = 10;
    const FETCH_GROUP                   = 65536;
    const FETCH_UNIQUE                  = 196608;
    const FETCH_KEY_PAIR                = 12;
    const FETCH_CLASSTYPE               = 262144;
    const FETCH_SERIALIZE               = 524288;
    const FETCH_PROPS_LATE              = 1048576;
    const ATTR_AUTOCOMMIT               = 0;
    const ATTR_PREFETCH                 = 1;
    const ATTR_TIMEOUT                  = 2;
    const ATTR_ERRMODE                  = 3;
    const ATTR_SERVER_VERSION           = 4;
    const ATTR_CLIENT_VERSION           = 5;
    const ATTR_SERVER_INFO              = 6;
    const ATTR_CONNECTION_STATUS        = 7;
    const ATTR_CASE                     = 8;
    const ATTR_CURSOR_NAME              = 9;
    const ATTR_CURSOR                   = 10;
    const ATTR_DRIVER_NAME              = 16;
    const ATTR_ORACLE_NULLS             = 11;
    const ATTR_PERSISTENT               = 12;
    const ATTR_STATEMENT_CLASS          = 13;
    const ATTR_FETCH_CATALOG_NAMES      = 15;
    const ATTR_FETCH_TABLE_NAMES        = 14;
    const ATTR_STRINGIFY_FETCHES        = 17;
    const ATTR_MAX_COLUMN_LEN           = 18;
    const ATTR_DEFAULT_FETCH_MODE       = 19;
    const ATTR_EMULATE_PREPARES         = 20;
    const ERRMODE_SILENT                = 0;
    const ERRMODE_WARNING               = 1;
    const ERRMODE_EXCEPTION             = 2;
    const CASE_NATURAL                  = 0;
    const CASE_LOWER                    = 2;
    const CASE_UPPER                    = 1;
    const NULL_NATURAL                  = 0;
    const NULL_EMPTY_STRING             = 1;
    const NULL_TO_STRING                = 2;
    const FETCH_ORI_NEXT                = 0;
    const FETCH_ORI_PRIOR               = 1;
    const FETCH_ORI_FIRST               = 2;
    const FETCH_ORI_LAST                = 3;
    const FETCH_ORI_ABS                 = 4;
    const FETCH_ORI_REL                 = 5;
    const CURSOR_FWDONLY                = 0;
    const CURSOR_SCROLL                 = 1;
    const ERR_NONE                      = '00000';
    const PARAM_EVT_ALLOC               = 0;
    const PARAM_EVT_FREE                = 1;
    const PARAM_EVT_EXEC_PRE            = 2;
    const PARAM_EVT_EXEC_POST           = 3;
    const PARAM_EVT_FETCH_PRE           = 4;
    const PARAM_EVT_FETCH_POST          = 5;
    const PARAM_EVT_NORMALIZE           = 6;
    
    // MySQL constants
    const MYSQL_ATTR_USE_BUFFERED_QUERY = 1000;
    const MYSQL_ATTR_LOCAL_INFILE       = 1001;
    const MYSQL_ATTR_INIT_COMMAND       = 1002;
    const MYSQL_ATTR_READ_DEFAULT_FILE  = 1003;
    const MYSQL_ATTR_READ_DEFAULT_GROUP = 1004;
    const MYSQL_ATTR_MAX_BUFFER_SIZE    = 1005;
    const MYSQL_ATTR_DIRECT_QUERY       = 1006;
    
    // Postgresql constants
    const PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT = 1000;
    
    abstract public function __construct($dsn, $username = '', $password = '', $driver_options = array());
    abstract public function beginTransaction();
    abstract public function commit();
    abstract public function errorCode();
    abstract public function errorInfo();
    abstract public function exec($statement);
    abstract public function getAttribute($attribute);
    abstract public function lastInsertId($name = '');
    abstract public function prepare($statement, $driver_options = array());
    abstract public function query($statement, $mode = 0, $param = '', $ctorargs = array());
    abstract public function quote($string, $parameter_type = 0);
    abstract public function rollBack();
    abstract public function setAttribute($attribute, $value);
}


function phppdo_drivers()
{
    return PDO::getAvailableDrivers();
}

class PDO extends abstractPDO
{
    private $path;
    private $driver;
    private $driver_name;

    public function __construct($dsn, $username = '', $password = '', $driver_options = array())
    {
        if(!is_array($driver_options)) $driver_options = array();
        $this->setup();

        $driver_dsn =& $this->parse_dsn($dsn);

        if($this->driver_name == 'uri')
        {
            $driver_dsn = $this->get_uri_dsn(key($driver_dsn));
        }

        $this->init_driver($driver_dsn, $username, $password, $driver_options);
    }

    public static function getAvailableDrivers()
    {
        if(func_num_args() > 0) return false;

        $result = array();
        if($handle = opendir(dirname(__FILE__) . '/drivers'))
        {
            while (false !== ($file = readdir($handle)))
            {
                if($file == '.' || $file == '..') continue;
                $driver = explode('_', $file);
                if(isset($driver[1])) continue;
                $driver = str_replace('.php', '', $driver[0]);
                if($driver == 'base') continue;

                $skip = false;
                switch($driver)
                {
                    case 'mysql':
                    case 'mysqli':
                        $driver = 'mysql';
                        $skip = in_array($driver, $result);
                        break;

                    case 'mssql':
                    case 'sybase':
                        if(PHP_OS == 'WINNT')
                        {
                            $driver = 'mssql';
                        }
                        else
                        {
                            $driver = 'dblib';
                        }

                        $skip = in_array($driver, $result);
                        break;
                }

                if($skip) continue;
                $result[] = $driver;
            }

            closedir($handle);
        }

        return $result;
    }

    public function beginTransaction()
    {
        return $this->driver->beginTransaction();
    }

    public function commit()
    {
        return $this->driver->commit();
    }

    public function errorCode()
    {
        if(func_num_args() > 0) return false;
        return $this->driver->errorCode();
    }

    public function errorInfo()
    {
        if(func_num_args() > 0) return false;
        return $this->driver->errorInfo();
    }

    public function exec($statement)
    {
        if(!$statement || func_num_args() != 1) return false;

        $driver = $this->driver;
        $result = $driver->exec($statement);

        if($result !== false)
        {
            //$driver->filter_result($result, $driver->driver_options[PDO::ATTR_STRINGIFY_FETCHES], $driver->driver_options[PDO::ATTR_ORACLE_NULLS]);
            $driver->clear_error();
        }
        else
        {
            $driver->set_driver_error(null, PDO::ERRMODE_SILENT, 'exec');
        }

        return $result;
    }

    public function getAttribute($attribute)
    {
        if(func_num_args() != 1 || !is_int($attribute)) return false;
        return $this->driver->getAttribute($attribute);
    }

    public function lastInsertId($name = '')
    {
        if(!is_string($name) || func_num_args() > 1) return false;

        $result = $this->driver->lastInsertId($name);
        $driver = $this->driver;

        if($result !== false)
        {
            $driver->filter_result($result, $driver->driver_options[PDO::ATTR_STRINGIFY_FETCHES], $driver->driver_options[PDO::ATTR_ORACLE_NULLS]);
        }

        return $result;
    }

    public function prepare($statement, $driver_options = array())
    {
        return $this->driver->prepare($statement, $driver_options);
    }

    public function query($statement, $mode = 0, $param = '', $ctorargs = array())
    {
        $st = $this->prepare($statement);
        if(!$st) return false;

        try
        {
            if(!$st->execute())
            {
                $this->driver->set_error_info($st->errorInfo());
                return false;
            }
        }
        catch(PDOException $e)
        {
            $this->driver->set_error_info($st->errorInfo());
            throw $e;
        }

        if(!$mode) return $st;
        if(!$st->setFetchMode($mode, $param, $ctorargs)) return false;
        return $st;
    }

    public function quote($string, $parameter_type = -1)
    {
        if(!func_num_args() || is_array($string) || is_object($string)) return false;
        return $this->driver->quote($string, $parameter_type);
    }

    public function rollBack()
    {
        return $this->driver->rollback();
    }

    public function setAttribute($attribute, $value)
    {
        if(func_num_args() != 2) return false;
        return $this->driver->setAttribute($attribute, $value);
    }


    // pgsql specific
    public function pgsqlLOBCreate()
    {
        return $this->driver->pgsqlLOBCreate();
    }

    public function pgsqlLOBOpen($oid)
    {
        return $this->driver->pgsqlLOBOpen($oid);
    }

    public function pgsqlLOBUnlink($oid)
    {
        return $this->driver->pgsqlLOBUnlink($oid);
    }


    // private
    private function load($file)
    {
        return include_once($this->path . '/' . $file);
    }

    private function setup()
    {
        $this->path = dirname(__FILE__);

        // load pdo exception and statement
        if(!class_exists('PDOException'))
        {
            $this->load('pdoexception.php');
        }

        if(!class_exists('PDOStatement'))
        {
            $this->load('pdostatementabstract.php');
        }

        $this->load('drivers/base.php');
        $this->load('drivers/base_statement.php');
    }

    private function get_uri_dsn($driver_dsn)
    {
        $uri_data =& $this->parse_uri($driver_dsn);
        switch($uri_data[0])
        {
            case 'file':
                if(false === ($dsn = file_get_contents($uri_data[1])))
                {
                    throw new PDOException('invalid data source name');
                }

                return $this->parse_dsn($dsn);
                break;

            default:
                throw new PDOException('invalid data source name');
                break;
        }
    }

    private function &parse_dsn(&$dsn)
    {
        $pos = strpos($dsn, ':');
        if($pos === false) throw new PDOException('invalid data source name');

        $this->driver_name = strtolower(trim(substr($dsn, 0, $pos)));
        if(!$this->driver_name) throw new PDOException('could not find driver');

        $driver_dsn = array();
        $d_dsn = trim(substr($dsn, $pos + 1));

        if($d_dsn)
        {
            $arr = explode(';', $d_dsn);

            foreach($arr as &$pair)
            {
                $kv = explode('=', $pair);
                $driver_dsn[strtolower(trim($kv[0]))] = isset($kv[1]) ? trim($kv[1]) : '';
            }
        }

        return $driver_dsn;
    }

    private function &parse_uri($dsn)
    {
        $pos = strpos($dsn, ':');
        if($pos === false) throw new PDOException('invalid data source name');

        $data = array(strtolower(trim(substr($dsn, 0, $pos))));
        $data[] = trim(substr($dsn, $pos + 1));

        return $data;
    }

    private function init_driver(&$dsn, &$username, &$password, &$driver_options)
    {
        if(isset($dsn['extension']) && $dsn['extension'])
        {
            $driver = strtolower($dsn['extension']);
        }
        else
        {
            $driver = $this->driver_name;
            switch($driver)
            {
                case 'mysql':
                    if(extension_loaded('mysqli'))
                        $driver = 'mysqli';
                    break;

                case 'dblib':
                case 'mssql':
                    if(extension_loaded('mssql'))
                        $driver = 'mssql';
                    else
                        $driver = 'sybase';
                    break;
            }
        }

        if(!@$this->load('drivers/' . $driver . '.php'))
        {
            throw new PDOException('could not find driver');
        }

        $driver_options[PDO::ATTR_DRIVER_NAME] = $this->driver_name;

        // load statement
        if(!class_exists('phppdo_' . $driver . '_statement'))
        {
            $this->load('drivers/' . $driver . '_statement.php');
        }

        if(!isset($driver_options[PDO::ATTR_STATEMENT_CLASS]))
        {
            $driver_options[PDO::ATTR_STATEMENT_CLASS] = array('phppdo_' . $driver . '_statement');
        }

        $class = 'phppdo_' . $driver;
        $this->driver = new $class($dsn, $username, $password, $driver_options);
    }
}
