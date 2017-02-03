<?php

/**
 * class DB
 * @param Host
 * @param User
 * @param Password
 * @param Name
 * Adatbázis kezelő osztály
 */
class DB
{
    private $debug = true; //DEBUG mód (SQL hibák látszanak/nem)
    protected $mysqli; //MySQLi Objektum
    protected $last_query; //Utoljára futtatott Query
    /*---------------------------------------------------------------------------------------------*/
    /**
     * Class Constructor
     * Létrehozza A MySQL kapcsolatot (példányosítás a mysqli osztályból)
     */
    public function __construct()
    {
        $this->mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->mysqli->connect_errno) {
            print (utf8_decode("Nem sikerült kapcsolódni az adatbázishoz! Hibakód:") . $this->
                    mysqli->connect_errno) . "\n<br />";
            print (utf8_decode("Részletek: ") . $this->mysqli->connect_error);
            exit();
        }
        $this->mysqli->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
    }
    /*---------------------------------------------------------------------------------------------*/
    /**
     * Function query_result_to_array
     * @param result
     * @param bool $single_row
     * @return array //Assoc
     * Feldolgozza a MySQL Row Setet (resource tip) és visszatér egy asszocitív tömmbel, ahol az
     * tömb index (key) = mező neve és az érték (value) = rekord
     * Ha $singleRow értéke igaz, akkor csak 1 rekorddal tér vissza a tömb helyett
     */
    public function query_result_to_array($result, $single_row = false)
    {
        $return = array();

        while ($row = $result->fetch_assoc()) {
            array_push($return, $row);
        }

        if ($single_row === true) {
            if (count($return) > 0) {
                return $return[0];
            }
            return false;
        }

        return $return;
    }
    /*---------------------------------------------------------------------------------------------*/
    /**
     * Function select
     * @param $what
     * @param $table
     * @param string $where
     * @param array $limit
     * @param string $order
     * @param array $join
     * @return mysqli_result Lefutatt egy lekérdezést a táblában és a lekérdezés eredménye lesz a visszatérési érték
     * Lefutatt egy lekérdezést a táblában és a lekérdezés eredménye lesz a visszatérési érték
     * ami egy mysqli Objektum, ennek mezői tartalmazzák az eremdényt. A mezőnevek a tábla mezőnevei.
     * A what paraméterben a mezőneveket lehet megadni ami egy TÖMB(mezo1, mezo2, ..., *), STRING is lehet.
     * A limit paraméterben a A LIMIT alsó és felső értékét lehet megadni, string is lehet
     * Az order paraméterben az sql végi rendezéseket és hasonlókat lehet itt megadni stringben, a LIMIT-et nem
     * A Join paraméter egy tömb, amiben a kapcsolt táblákat lehet megadni illetve az összekapcsolt mezőket.
     * Pl.: TÖMB(tömb('a kijelölt tábla kacsolatmezője', 'kapcsolat tábla kapcsolatmezője', 'kapcsolat tábla neve'), ...)
     * @internal param $mezőnevek (ARRAY vagy STRING)
     * @internal param neve $tábla
     * @internal param $Feltétel (STRING)
     * @internal param $limit (MIXED)
     * @internal param $order (STRING)
     * @internal param $join (ARRAY)
     * @example array(array(TB_CIKK_NYELV, TB_CIKK, "id",TB_CIKK_NYELV, "id"))
     * eredmény: JOIN TB cikk_nyelv ON TB_CIKK.id = TB_cikknyelv.id
     */
    public function select($what, $table, $where = 1, $limit = array(), $order = "",
                           $join = array())
    {
        $mit = "";
        $kapcsolva = "";
        $korlat = "";
        if (is_array($what)) {
            foreach ($what as $k) {
                $mit .= ($mit != "") ? ", " : "";
                $mit .= $k;
            }
        } else {
            $mit = $what;
        }
        if (is_array($join)) {
            foreach ($join as $k) {
                if (is_array($k)) {
                    $kapcsolva .= " " . @$k[5] . " JOIN " . $k[3] . " ON " . $k[1] . "." . $k[2] .
                        "=" . $k[3] . "." . $k[4];
                } else {
                    $kapcsolva .= $k;
                }
            }
        } else {
            $kapcsolva = $join;
        }
        if (is_array($limit)) {
            foreach ($limit as $m) {
                $korlat .= ($korlat != "") ? ", " : "LIMIT ";
                $korlat .= $m;
            }
        } elseif (is_int($limit)) {
            $korlat = "LIMIT " . (string )$limit;
        } elseif (strlen($limit) > 0) {
            $korlat = "LIMIT $limit";
        }
        $query = "SELECT $mit FROM $table $kapcsolva WHERE $where $order $korlat";
        $result = $this->mysqli->query($query);
        if ($result) {
            return $result;
        } else {
            $this->error();
            return false;
        }
    }
    /*---------------------------------------------------------------------------------------------*/
    /**
     * Function insert
     * @param Adatok tömb
     * @param Tábla név
     * @return BOOL
     * Egy új rekordod ad hozzá a táblához
     * Az adatok tömbből (asszociatív) ami bemenő paraméter, a tömbindexex lesznek a mezők nevei
     *  az értékek pedig amik a mezőkbe kerülnek
     */
    public function insert($data, $table)
    {
        $columns = "";
        $values = "";
        foreach ($data as $column => $value) {
            $columns .= ($columns == "") ? "" : ", ";
            $columns .= "`" . $column . "`";
            $value = htmlentities($value, ENT_QUOTES, "UTF-8");
            if (is_string($value)) {
                $value = "'" . $value . "'";
            }
            $values .= ($values == "") ? "" : ", ";
            $values .= $value;
        }
        $query = "INSERT INTO $table ($columns) VALUES ($values)";
        if ($this->query($query)) {
            return true;
        } else {
            $this->error();
            return false;
        }
    }
    /*---------------------------------------------------------------------------------------------*/
    /**
     * Function delete
     * @param tábla neve (valami)
     * @param feltétel (asd=xy)
     * @return BOOL
     */
    public function delete($table, $where)
    {
        $query = "DELETE FROM $table WHERE $where";
        if ($this->query($query)) {
            return true;
        } else {
            $this->error();
            return false;
        }
    }

    /**
     * Function query
     * @param lekérdezés
     * MySQLi osztály query függvényét futtatja, és eseménynaplóban rögzíti a lekérdezést
     * @return bool
     */
    protected function query($query)
    {
        $this->last_query = $query;
        if ($this->mysqli->query($query)) {
            return true;
        }
        return false;
    }

    /**
     * Function error
     * MySQL hibát ír ki, ha DEBUG mód be van kapcsolva
     */
    public function error()
    {
        if ($this->debug) {
            echo "MySQL Hiba: " . mysqli_error($this->mysqli) . "<br />";
            echo "MySQL Lekérdezés: " . $this->last_query . "<br />";

        }
    }
    //...............................................................................................
    //## PROPERTYk kiolvasása ##
    //...............................................................................................
    /**
     * Function inserted_id
     * @return insert_id
     * Utoljára beszúrt autó incrementes mező id-jével tér vissza
     */
    function inserted_id()
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Function affected_rows
     * @return Módosított rekordok
     * Módosított rekordok száma (Update, Delete..)
     */
    function affected_rows()
    {
        return $this->mysqli->affected_rows;
    }
}

?>