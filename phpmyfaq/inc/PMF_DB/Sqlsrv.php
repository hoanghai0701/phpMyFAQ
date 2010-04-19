<?php
/**
 * The PMF_DB_Sqlsrv class provides methods and functions for SQL Server Driver for PHP from Microsoft.
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-02-18
 */

/**
 * PMF_DB_Sqlsrv
 *
 * @category  phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-02-18
 */
class PMF_DB_Sqlsrv implements PMF_DB_Driver 
{
    /**
     * The connection object
     *
     * @var PMF_DB_Driver
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var string
     */
    private $sqllog = '';
    
    /**
     * Connection options array
     * 
     * @var array
     */
    private $connectionOptions = array();

    /**
     * Tables
     *
     * @var array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param  string $host     A string specifying the name of the server to which a connection is being established
     * @param  string $user     Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param  string $passwd   Specifies the password associated with the User ID to be used when connecting with 
     *                          SQL Server Authentication
     * @param  string $database Specifies the name of the database in use for the connection being established
     * @return boolean true, if connected, otherwise false
     */
    public function connect($host, $user, $passwd, $database)
    {
        $this->setConnectionOptions($user, $passwd, $database);
        
        $this->conn = sqlsrv_connect($host, $this->connectionOptions);
        if (!$this->conn) {
            PMF_Db::errorPage(sqlsrv_errors());
            die();
        }
        return true;
    }
    
    /**
     * Sets the connection options
     *
     * @param  string $user     Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param  string $passwd   Specifies the password associated with the User ID to be used when connecting with 
     *                          SQL Server Authentication
     * @param  string $database Specifies the name of the database in use for the connection being established
     * @return void
     */
    private function setConnectionOptions($user, $passwd, $database)
    {
        $this->connectionOptions = array(
           'UID'          => $user,
           'PWD'          => $passwd,
           'Database'     => $database,
           'CharacterSet' => 'UTF-8');
    }
    
    /**
     * Sends a query to the database.
     *
     * @param  string $query Query
     * @return mixed
     */
    public function query($query)
    {
        return sqlsrv_query($this->conn, $query);
    }

    /**
     * Escapes a string for use in a query
     *
     * @param  string $string String
     * @return string
     */
    public function escapeString($string)
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  resource $result Resultset
     * @return resource
     */
    public function fetchObject($result)
    {
        return sqlsrv_fetch_object($result);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  resource $result Resultset
     * @return array
     */
    public function fetch_assoc($result)
    {
        return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Mysql
     */
    public function fetchAll($result)
    {
        $ret = array();
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }
        
        while ($row = $this->fetchObject($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    
    /**
     * Number of rows in a result
     *
     * @param  resource $result Resultset
     * @return integer
     */
    public function numRows($result)
    {
        return sqlsrv_num_rows($this->conn);
    }

    /**
     * Logs the queries
     *
     * @return integer
     */
    public function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * Generates a result based on search a search string.
     *
     * @param  string $table       Table for search
     * @param  array  $assoc       Associative array with columns for the resulset
     * @param  string $joinedTable Table to do a JOIN, e.g. for faqcategoryrelations
     * @param  array  $joinAssoc   Associative array with comlumns for the JOIN
     * @param  string $string      Search term
     * @param  array  $cond        Conditions
     * @param  array  $orderBy     ORDER BY columns
     * @return mixed
     */
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array())
    {
        $string = trim($string);
        $fields = '';
        $join   = '';
        $joined = '';
        $where  = '';

        foreach ($assoc as $field) {
            if (empty($fields)) {
                $fields = $field;
            } else {
                $fields .= ", ".$field;
            }
        }

        if (isset($joinedTable) && $joinedTable != '') {
            $joined .= ' LEFT JOIN '.$joinedTable.' ON ';
        }

        if (is_array($joinAssoc)) {
            foreach ($joinAssoc as $joinedFields) {
                $join .= $joinedFields.' AND ';
                }
            $joined .= PMF_String::substr($join, 0, -4);
        }

        $keys     = PMF_String::preg_split("/\s+/", $string);
        $numKeys  = count($keys);
        $numMatch = count($match);

        for ($i = 0; $i < $numKeys; $i++) {
            if (strlen($where) != 0 ) {
                $where = $where." OR";
            }
            $where = $where." (";
            for ($j = 0; $j < $numMatch; $j++) {
                if ($j != 0) {
                    $where = $where." OR ";
                }
                $where = $where.$match[$j]." LIKE '%".$keys[$i]."%'";
            }

            $where .= ")";
        }

        foreach ($cond as $field => $data) {
            if (empty($where)) {
                $where .= $field." = ".$data;
            } else {
                $where .= " AND ".$field." = ".$data;
            }
        }

        $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE";

        if (!empty($where)) {
            $query .= " (".$where.")";
        }

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        }

        $firstOrderBy = true;
        foreach ($orderBy as $field) {
            if ($firstOrderBy) {
                $query .= " ORDER BY ".$field;
                $firstOrderBy = false;
            } else {
                $query .= ", ".$field;
            }
        }

        return $this->query($query);
    }

    /**
     * This function returns the table status.
     *
     * @return array
     */
    public function getTableStatus()
    {
        $tables = array();
        $query  = "
            SELECT
                obj.name AS table_name,
                idx.rows AS table_rows
            FROM
                sysobjects obj, sysindexes idx
            WHERE
                    idx.id = OBJECT_ID(obj.name)
                AND idx.indid < 2
                AND obj.xtype = 'u'
            ORDER BY obj.name";
        $result = $this->query($query);

        while ($row = $this->fetchObject($result)) {
            if ('dtproperties' != $row->table_name) {
                $tables[$row->table_name] = $row->table_rows;
            }
        }

        return $tables;
    }

    /**
     * Returns the next ID of a table
     *
     * @param  string $table the name of the table
     * @param  string $id    the name of the ID column
     * @return int
     */
    public function nextID($table, $id)
    {
        $select = sprintf("
           SELECT 
               max(%d) as current_id 
           FROM 
               %s",
           $id,
           $table);
        
        $result = $this->query($select);
        $stmt   = sqlsrv_fetch($result);
        return (sqlsrv_get_field($stmt, 0) + 1);
    }

    /**
     * Returns the error string.
     *
     * This function returns the last error string.
     *
     * @access  public
     */
    public function error()
    {
        $errors = sqlsrv_errors();
        return $errors['SQLSTATE'] . ': ' . $errors['message'];
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function client_version()
    {
        $client_info = sqlsrv_client_info($this->conn);
        return $client_info['DriverODBCVer'] . ' ' . $client_info['DriverVer'];
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function server_version()
    {
        $server_info = sqlsrv_server_info($this->conn);
        return $server_info['SQLServerVersion'];
    }

    /**
     * Gets an array with all table names
     *
     * @return void
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query('SELECT name FROM sysobjects WHERE type = \'u\''.(('' == $prefix) ? '' : ' AND name LIKE \''.$prefix.'%\' ORDER BY name'));
        while ($row = $this->fetch_object($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }
    }

    /**
     * Closes the connection to the database.
     *
     * @return void
     */
    public function dbclose()
    {
        sqlsrv_close($this->conn);
    }
}