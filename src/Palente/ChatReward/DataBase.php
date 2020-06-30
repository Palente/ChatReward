<?php
/**
 * Crée avec PHPStorm
 * Filename: DataBase.php
 * User: Adel
 * Date: 04/06/2020
 * Time: 21:04
 */


namespace Palente\ChatReward;


class DataBase
{
    private $plugin, $db;

    public function __construct(ChatReward $caller)
    {
        $this->plugin = $caller;
        $this->db = new \SQLite3($this->plugin->getDataFolder()."players.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS players_data (id INTEGER PRIMARY KEY AUTOINCREMENT,name VARCHAR(25),level INT,xp INT, blacklisted INT(1))");
    }

    public function exists(string $name): bool
    {
        return count($this->getData($name)) != 0;
    }

    public function create(string $name) : void
    {
        $name = strtolower($name);
        $this->db->exec("INSERT INTO players_data (name, level, xp, blacklisted) VALUES ('$name', 0, 0, 0)");
    }

    public function getData(string $name) : array
    {
        $rq = $this->db->query("SELECT * FROM players_data WHERE name = '".strtolower($name)."'");
        $rslt = $rq->fetchArray();
        return ($rslt === false ? [] : $rslt);
    }

    public function getLevel(string $name) : ?int
    {
        if(!$this->exists($name))return null;
        $rq = $this->db->query("SELECT level FROM players_data WHERE name = '" . strtolower($name)."'");
        return $rq->fetchArray()[0];
    }

    public function setLevel(string $name, int $level) : void
    {
        $this->db->exec("UPDATE players_data SET level = $level WHERE name='".strtolower($name)."'");
    }

    public function getPoints(string $name) : ?int{
        if(!$this->exists($name))return null;
        $rq = $this->db->query("SELECT xp FROM players_data WHERE name = '".strtolower($name)."'");
        return $rq->fetchArray()[0];
    }

    public function setPoints(string $name, int $xp) : void
    {
        $this->db->exec("UPDATE players_data SET xp = $xp WHERE name='".strtolower($name)."'");
    }

    /*
     * BLACKLIST FUNCTIONS
     */

    public function getBlacklisted() : array{
        $rq = $this->db->query("SELECT name FROM players_data WHERE blacklisted = 1");
        $rslt = $rq->fetchArray();
        return ($rslt === false ? [] : $rslt);
    }
    public function isBlacklisted(string $name) : bool{
        $rq = $this->db->query("SELECT blacklisted FROM players_data WHERE name='".strtolower($name)."'");
        return ($rq->fetchArray()[0] == 1);
    }
    public function addBlacklist(string $name) : void{
        $this->db->exec("UPDATE players_data SET blacklisted = 1 WHERE name='".strtolower($name)."'");
    }

    public function removeBlacklist(string $name){
        $this->db->exec("UPDATE players_data SET blacklisted = 0 WHERE name='".strtolower($name)."'");
    }
}